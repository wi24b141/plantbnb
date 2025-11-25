<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\listings.php
// Include the database configuration from db.php
require_once 'db.php';

// Initialize the variable to store active listings
$activeListings = [];

// Use a try-catch block to safely handle database connection errors
try {
    // SQL query that JOINs the listings and users tables
    // We select all columns from listings table and the username from users table
    // The INNER JOIN connects listings to users using the user_id foreign key
    // INNER JOIN ensures we only get listings where a corresponding user exists
    $sqlQuery = "
        SELECT 
            listings.*,
            users.username
        FROM listings
        INNER JOIN users ON listings.user_id = users.user_id
        WHERE listings.status = 'active'
        ORDER BY listings.created_at DESC
    ";

    // Prepare the statement to prevent SQL injection attacks
    // Prepared statements separate the SQL code from the data, making it impossible for attackers to inject malicious code
    $statement = $connection->prepare($sqlQuery);

    // Execute the prepared statement (no parameters needed since we're not using user input)
    $statement->execute();

    // Fetch all results as an associative array (key => value pairs)
    // PDO::FETCH_ASSOC returns each row as an array where column names are keys
    $activeListings = $statement->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Active Listings</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- Include the header to show login status -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-5">
        <!-- Header for the Recent Listings section -->
        <h2 class="mb-4">Recent Listings</h2>

        <!-- Check if there was a database error and display it -->
        <?php
            if (isset($errorMessage)) {
                echo "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "</div>";
            }
        ?>

        <!-- Check if we have any listings to display -->
        <?php
            if (count($activeListings) > 0) {
                // Create a responsive grid using Bootstrap's row-cols classes
                // row-cols-1 = 1 column on small screens (mobile-first)
                // row-cols-md-3 = 3 columns on medium screens and up (desktop)
                // g-4 = Gap (spacing) of 1.5rem between columns for touch-friendly spacing
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                // Loop through each active listing
                foreach ($activeListings as $listing) {
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
                    $badgeColor = ($safeListingType === 'offer') ? 'success' : 'warning';
                    
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
                    echo "    <div class=\"card-header bg-light\">";
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    echo "    </div>";
                    
                    // Card Body: Main content
                    echo "    <div class=\"card-body\">";
                    
                    // Display the listing title as the card title
                    echo "      <h5 class=\"card-title\">$safeTitle</h5>";
                    
                    // Display who posted this listing
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: $safeUsername</h6>";
                    
                    // Display location information
                    echo "      <p class=\"card-text\">";
                    echo "        <small>";
                    echo "          <strong>Location:</strong> $safeLocation<br>";
                    echo "          <strong>Available:</strong> $safeStartDate to $safeEndDate";
                    echo "        </small>";
                    echo "      </p>";
                    
                    echo "    </div>";
                    
                    // Card Footer: Action button
                    echo "    <div class=\"card-footer bg-white border-top-0\">";
                    
                    // Create a "View Details" link button that passes the listing ID as a URL parameter
                    // The listing ID is passed via GET parameter so listing-details.php can fetch the full information
                    echo "      <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-primary btn-sm w-100\">";
                    echo "        View Details";
                    echo "      </a>";
                    
                    echo "    </div>";
                    
                    // Close the card
                    echo "  </div>";
                    
                    // Close the column div
                    echo "</div>";
                }
                
                // Close the row div
                echo "</div>";
                
            } else {
                // Display a friendly message if there are no active listings
                echo "<div class=\"alert alert-info\" role=\"alert\">";
                echo "  <strong>No active listings found.</strong> Check back soon for new plant listings!";
                echo "</div>";
            }
        ?>
    </div>

    <!-- Load footer includes -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>