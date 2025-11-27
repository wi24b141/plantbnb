<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\favoritelistings.php

// ============================================
// FAVORITE LISTINGS PAGE - PHP LOGIC (TOP)
// ============================================

// Start the session to access $_SESSION variables
// session_start() must be called before any HTML output
session_start();

// Include the database connection
require_once 'db.php';

// ============================================
// SECURITY CHECK: VERIFY USER IS LOGGED IN
// ============================================

// Check if user_id exists in the session
// If the user is not logged in, redirect to the login page immediately
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Store the user_id from the session for use in queries
// We use intval() to ensure it's an integer for extra safety
$userID = intval($_SESSION['user_id']);

// ============================================
// FETCH FAVORITED LISTINGS
// ============================================

// Initialize the variable to store favorited listings
// This array will hold all listings that the current user has favorited
$favoritedListings = [];

// Initialize error message variable
$errorMessage = '';

// Use a try-catch block to safely handle database errors
try {
    // SQL query that JOINs three tables: favorites, listings, and users
    // We need:
    // - favorites table to know which listings this user favorited
    // - listings table to get the listing details
    // - users table to get the username of who posted the listing
    // INNER JOIN ensures we only get listings that still exist and have valid users
    $sqlQuery = "
        SELECT 
            listings.*,
            users.username
        FROM favorites
        INNER JOIN listings ON favorites.listing_id = listings.listing_id
        INNER JOIN users ON listings.user_id = users.user_id
        WHERE favorites.user_id = :userID
        ORDER BY favorites.created_at DESC
    ";

    // Prepare the statement to prevent SQL injection attacks
    // Prepared statements separate the SQL code from the data
    // This makes it impossible for attackers to inject malicious code
    $statement = $connection->prepare($sqlQuery);

    // Bind the user_id parameter
    // PDO::PARAM_INT ensures the value is treated as an integer
    $statement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the prepared statement
    $statement->execute();

    // Fetch all results as an associative array (key => value pairs)
    // PDO::FETCH_ASSOC returns each row as an array where column names are keys
    $favoritedListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    // If a database error occurs, store the error message
    // In production, you should log this instead of displaying it
    $errorMessage = "Database error: " . $error->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - PlantBnB</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         FAVORITE LISTINGS PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <!-- This button allows users to easily navigate back -->
        <!-- col-12 = full width on mobile for easy touch target -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <!-- We show how many favorites the user has -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>❤️ My Favorite Listings</h2>
                <p class="text-muted">
                    You have <?php echo count($favoritedListings); ?> favorite listing<?php if (count($favoritedListings) !== 1) { echo 's'; } ?>
                </p>
            </div>
        </div>

        <!-- Check if there was a database error and display it -->
        <?php
            if (!empty($errorMessage)) {
                // Error alert - red background
                echo "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "</div>";
            }
        ?>

        <!-- Check if we have any favorited listings to display -->
        <?php
            if (count($favoritedListings) > 0) {
                // Create a responsive grid using Bootstrap's row-cols classes
                // row-cols-1 = 1 column on small screens (mobile-first)
                // row-cols-md-3 = 3 columns on medium screens and up (desktop)
                // g-4 = Gap (spacing) of 1.5rem between columns for touch-friendly spacing
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                // Loop through each favorited listing
                foreach ($favoritedListings as $listing) {
                    // Extract and sanitize each piece of data to prevent XSS attacks
                    // htmlspecialchars() converts special characters to HTML entities
                    // This prevents malicious JavaScript from running if user data contains code
                    $safeTitle = htmlspecialchars($listing['title']);
                    $safeUsername = htmlspecialchars($listing['username']);
                    $safeLocation = htmlspecialchars($listing['location_approx']);
                    $safeStartDate = htmlspecialchars($listing['start_date']);
                    $safeEndDate = htmlspecialchars($listing['end_date']);
                    $safeListingType = htmlspecialchars($listing['listing_type']);
                    $listingID = intval($listing['listing_id']);
                    
                    // Get the listing photo path and sanitize it
                    // This is the photo of the plant that was uploaded when creating the listing
                    // If no photo exists, this will be null or empty string
                    $listingPhotoPath = !empty($listing['listing_photo_path']) ? htmlspecialchars($listing['listing_photo_path']) : null;
                    
                    // Determine the badge color based on listing type
                    // 'offer' = green (success), 'need' = orange (warning)
                    if ($safeListingType === 'offer') {
                        $badgeColor = 'success';
                    } else {
                        $badgeColor = 'warning';
                    }
                    
                    // Start the column div for this card
                    // col class makes each card take full width on mobile, 1/3 width on desktop
                    echo "<div class=\"col\">";
                    
                    // Create a Bootstrap Card
                    // h-100 makes all cards the same height in a row (looks better)
                    // shadow-sm adds a subtle shadow for depth
                    echo "  <div class=\"card h-100 shadow-sm\">";
                    
                    // Listing Photo Section
                    // Display the plant photo if it exists
                    // This photo appears at the very top of the card
                    if ($listingPhotoPath) {
                        // Listing has a photo, display it using card-img-top class
                        // card-img-top is a Bootstrap class that styles images at the top of cards
                        // We use inline styles for responsive sizing:
                        // - height: 200px keeps all card images the same height for consistency
                        // - object-fit: cover crops the image nicely to fill the space without distortion
                        // - object-position: center centers the image in the cropped area
                        echo "    <img src=\"" . $listingPhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"height: 200px; object-fit: cover; object-position: center;\">";
                    } else {
                        // No photo uploaded, display a placeholder
                        // This ensures all cards have consistent layout even without photos
                        // bg-light = light gray background
                        // d-flex, align-items-center, justify-content-center = centers the text
                        echo "    <div class=\"bg-light d-flex align-items-center justify-content-center\" style=\"height: 200px;\">";
                        echo "      <span class=\"text-muted\">No photo available</span>";
                        echo "    </div>";
                    }
                    
                    // Card Header: Display the listing type as a badge
                    // bg-light = light gray background for the header section
                    echo "    <div class=\"card-header bg-light\">";
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    echo "    </div>";
                    
                    // Card Body: Main content
                    echo "    <div class=\"card-body\">";
                    
                    // Display the listing title as the card title
                    echo "      <h5 class=\"card-title\">$safeTitle</h5>";
                    
                    // Display who posted this listing
                    // mb-2 = margin-bottom for spacing
                    // text-muted = gray color for secondary information
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: $safeUsername</h6>";
                    
                    // Display location and date information
                    // We use <small> tag to make this text slightly smaller
                    echo "      <p class=\"card-text\">";
                    echo "        <small>";
                    echo "          <strong>Location:</strong> $safeLocation<br>";
                    echo "          <strong>Available:</strong> $safeStartDate to $safeEndDate";
                    echo "        </small>";
                    echo "      </p>";
                    
                    echo "    </div>";
                    
                    // Card Footer: Action buttons
                    // bg-white = white background for footer
                    // border-top-0 = removes the top border (cleaner look)
                    echo "    <div class=\"card-footer bg-white border-top-0\">";
                    
                    // Create a "View Details" link button
                    // w-100 = full width button (mobile-friendly)
                    // btn-sm = smaller button size to fit better in the card
                    // mb-2 = margin-bottom to create space between the two buttons
                    echo "      <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success btn-sm w-100 mb-2\">";
                    echo "        View Details";
                    echo "      </a>";
                    
                    // Create an "Unfavorite" button
                    // This is a separate form that will remove this listing from favorites
                    // We use method="POST" to send data securely
                    // action="listing-unfavorite.php" sends the request to a separate PHP file
                    echo "      <form method=\"POST\" action=\"listing-unfavorite.php\">";
                    
                    // Hidden input to pass the listing ID to the unfavorite script
                    // type=\"hidden\" means the user doesn't see this field
                    // We need to send the listing_id so listing-unfavorite.php knows which favorite to delete
                    echo "        <input type=\"hidden\" name=\"listing_id\" value=\"" . $listingID . "\">";
                    
                    // Unfavorite button
                    // btn-outline-danger = red outline (indicates removal action)
                    // w-100 = full width for easy touch on mobile
                    echo "        <button type=\"submit\" class=\"btn btn-outline-danger btn-sm w-100\">";
                    echo "          ❌ Remove from Favorites";
                    echo "        </button>";
                    
                    echo "      </form>";
                    
                    echo "    </div>";
                    
                    // Close the card
                    echo "  </div>";
                    
                    // Close the column div
                    echo "</div>";
                }
                
                // Close the row div
                echo "</div>";
                
            } else {
                // Display a friendly message if there are no favorited listings
                // alert-info = blue background for informational messages
                echo "<div class=\"alert alert-info\" role=\"alert\">";
                echo "  <h4 class=\"alert-heading\">No favorites yet!</h4>";
                echo "  <p class=\"mb-0\">You haven't favorited any listings. Browse <a href=\"listings.php\" class=\"alert-link\">all listings</a> and click the ❤️ button to save your favorites!</p>";
                echo "</div>";
            }
        ?>
    </div>

    <!-- Include the site footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>