<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// =============================================================================
// STEP 2: Initialize Variables
// =============================================================================
// WHY: We initialize variables at the top so they exist even if errors occur
// This prevents "undefined variable" errors in the HTML section below

$activeListings = array();  // Empty array to store listings from database
$errorMessage = '';         // Empty string to store any error messages

// =============================================================================
// STEP 3: Fetch Active Listings from Database
// =============================================================================
// WHY: We need to get all active listings from the database to display them

try {
    // Build the SQL query
    // WHY: We need data from TWO tables (listings and users)
    // The listings table has the plant info, users table has the username
    // We will exclude listings created by the currently logged in user
    $sqlQuery = "
        SELECT 
            listings.listing_id,
            listings.title,
            listings.location_approx,
            listings.start_date,
            listings.end_date,
            listings.listing_type,
            listings.listing_photo_path,
            users.username
        FROM listings
        INNER JOIN users ON listings.user_id = users.user_id
        WHERE listings.status = 'active'
    ";

    // If user is logged in, exclude their own listings from Browse
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $sqlQuery .= "\n        AND listings.user_id != :current_user_id\n    ";
    }

    // Always order by newest first
    $sqlQuery .= "\n        ORDER BY listings.created_at DESC\n    ";

    // Prepare the statement
    // WHY: Prepared statements protect against SQL injection attacks
    $statement = $connection->prepare($sqlQuery);

    // If we added the exclusion, bind the current user id parameter
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $currentUserId = intval($_SESSION['user_id']);
        $statement->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    }

    // Execute the query
    // WHY: This actually runs the query and gets results from database
    $statement->execute();

    // Fetch all results as an array
    // WHY: We need all the listings stored in a variable we can loop through
    // PDO::FETCH_ASSOC means each row is an array with column names as keys
    $activeListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    // If database error occurs, save the error message
    // WHY: We need to tell the user something went wrong
    $errorMessage = "Database error: " . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- WHY: This makes the page work on mobile phones by setting the width to device width -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Listings</title>
</head>
<body>
    <!-- container = Bootstrap class that centers content and adds padding -->
    <!-- mt-5 = Margin Top 5 (adds space at the top of the page) -->
    <div class="container mt-5">
        
        <!-- mb-4 = Margin Bottom 4 (adds space below the heading) -->
        <h2 class="mb-4">Recent Listings</h2>

        <?php
            // If the user is logged in, show a link to their own listings page
            // WHY: The user wants their listings separated from Browse
            if (isset($isLoggedIn) && $isLoggedIn) {
                echo '<div class="mb-3">';
                echo '  <a href="my-listings.php" class="btn btn-outline-primary">My Listings</a>';
                echo '</div>';
            }
        ?>

        <!-- =================================================================== -->
        <!-- DISPLAY ERROR MESSAGE (if there was a database error)              -->
        <!-- =================================================================== -->
        <?php
            // Check if we have an error message to display
            // WHY: If the database query failed, we need to tell the user
            if ($errorMessage !== '') {
                // alert = Bootstrap class for colored notification boxes
                // alert-danger = Red color (for errors)
                echo "<div class=\"alert alert-danger\">";
                
                // Display the error message
                // WHY: htmlspecialchars() prevents XSS attacks by converting < > & to HTML entities
                echo htmlspecialchars($errorMessage);
                
                echo "</div>";
            }
        ?>

        <!-- =================================================================== -->
        <!-- DISPLAY LISTINGS (if we have any)                                  -->
        <!-- =================================================================== -->
        <?php
            // Check if we found any listings in the database
            // WHY: We only want to show the grid if there are listings to display
            if (count($activeListings) > 0) {
                
                // Start the Bootstrap grid container
                // WHY: Bootstrap uses a grid system to arrange cards in rows and columns
                // row = Creates a horizontal row for cards
                // row-cols-1 = On mobile phones, show 1 card per row (full width)
                // row-cols-md-3 = On desktop (medium screens and up), show 3 cards per row
                // g-4 = Gap 4 (adds spacing between cards so they don't touch)
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                // Loop through each listing one by one
                // WHY: We need to create one card for each listing in the database
                foreach ($activeListings as $listing) {
                    
                    // ---------------------------------------------------------
                    // EXTRACT AND SANITIZE DATA
                    // ---------------------------------------------------------
                    // WHY: We need to get data from the array and make it safe for HTML
                    // htmlspecialchars() converts dangerous characters like < > to safe HTML entities
                    // This prevents hackers from injecting malicious code (XSS attacks)
                    
                    $listingID = intval($listing['listing_id']);  // Convert to integer for safety
                    $safeTitle = htmlspecialchars($listing['title']);
                    $safeUsername = htmlspecialchars($listing['username']);
                    $safeLocation = htmlspecialchars($listing['location_approx']);
                    $safeStartDate = htmlspecialchars($listing['start_date']);
                    $safeEndDate = htmlspecialchars($listing['end_date']);
                    $safeListingType = htmlspecialchars($listing['listing_type']);
                    
                    // ---------------------------------------------------------
                    // BUILD THE IMAGE PATH
                    // ---------------------------------------------------------
                    // WHY: We need to create the correct URL for the browser to load the image
                    // The database stores the path like "uploads/listings/photo.jpg"
                    // We just need to add the /plantbnb/ prefix for XAMPP
                    
                    if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                        // Listing has a photo
                        // Get the path from database (already includes uploads/listings/)
                        $photoPath = $listing['listing_photo_path'];
                        
                        // Add the project folder prefix: /plantbnb/
                        // WHY: XAMPP serves from localhost/plantbnb/, so we need this prefix
                        $fullPhotoPath = "/plantbnb/" . $photoPath;
                        
                        // Make it safe for HTML output
                        $safePhotoPath = htmlspecialchars($fullPhotoPath);
                    } else {
                        // Listing has no photo
                        $safePhotoPath = null;
                    }
                    
                    // ---------------------------------------------------------
                    // DETERMINE BADGE COLOR
                    // ---------------------------------------------------------
                    // WHY: We want "offer" listings to be green and "need" listings to be orange
                    
                    if ($safeListingType === 'offer') {
                        $badgeColor = 'success';  // Green color in Bootstrap
                    } else {
                        $badgeColor = 'warning';  // Orange color in Bootstrap
                    }
                    
                    // ---------------------------------------------------------
                    // START BUILDING THE CARD HTML
                    // ---------------------------------------------------------
                    
                    // Each card is wrapped in a column div
                    // WHY: Bootstrap columns automatically size themselves based on row-cols classes
                    echo "<div class=\"col\">";
                    
                    // Start the card
                    // card = Bootstrap class for a rectangular box with border and padding
                    // h-100 = Height 100% (makes all cards in a row the same height)
                    echo "  <div class=\"card h-100\">";
                    
                    // ---------------------------------------------------------
                    // DISPLAY THE PHOTO (or placeholder)
                    // ---------------------------------------------------------
                    
                    if ($safePhotoPath !== null) {
                        // Listing has a photo, display it
                        // card-img-top = Bootstrap class that styles images at top of cards
                        // WHY: We set height to 200px so all images are the same size
                        echo "    <img src=\"" . $safePhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"height: 200px;\">";
                    } else {
                        // Listing has no photo, show placeholder text
                        // bg-light = Light gray background
                        // d-flex = Display flex (needed for centering)
                        // align-items-center = Centers content vertically
                        // justify-content-center = Centers content horizontally
                        echo "    <div class=\"bg-light d-flex align-items-center justify-content-center\" style=\"height: 200px;\">";
                        echo "      <span class=\"text-muted\">No photo</span>";
                        echo "    </div>";
                    }
                    
                    // ---------------------------------------------------------
                    // DISPLAY THE LISTING TYPE BADGE
                    // ---------------------------------------------------------
                    
                    // card-header = Bootstrap class for the top section of a card
                    // bg-light = Light gray background
                    echo "    <div class=\"card-header bg-light\">";
                    
                    // badge = Bootstrap class for small colored labels
                    // bg-success or bg-warning = Green or orange background
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    
                    echo "    </div>";
                    
                    // ---------------------------------------------------------
                    // DISPLAY THE MAIN CONTENT
                    // ---------------------------------------------------------
                    
                    // card-body = Bootstrap class for the main content area of a card
                    echo "    <div class=\"card-body\">";
                    
                    // Display the listing title
                    // card-title = Bootstrap class for card headings
                    echo "      <h5 class=\"card-title\">" . $safeTitle . "</h5>";
                    
                    // Display who posted the listing
                    // card-subtitle = Bootstrap class for secondary headings
                    // mb-2 = Margin bottom 2 (adds space below)
                    // text-muted = Gray text color
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: " . $safeUsername . "</h6>";
                    
                    // Display location and dates
                    // card-text = Bootstrap class for paragraph text in cards
                    echo "      <p class=\"card-text\">";
                    echo "        <strong>Location:</strong> " . $safeLocation . "<br>";
                    echo "        <strong>Available:</strong> " . $safeStartDate . " to " . $safeEndDate;
                    echo "      </p>";
                    
                    // Display the "View Details" button
                    // WHY: We need to link to the details page and pass the listing ID
                    // btn = Bootstrap button class
                    // btn-success = Green button
                    // w-100 = Width 100% (full width button, good for mobile)
                    // d-grid = Display grid (makes button full width)
                    // Buttons: stacked on mobile, single full-width button on md+ to match My Listings style
                    echo "      <div class=\"d-grid gap-2 d-md-flex\">";
                    echo "        <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success flex-md-fill\">View Details</a>";
                    echo "      </div>";
                    
                    echo "    </div>";  // Close card-body
                    
                    echo "  </div>";  // Close card
                    
                    echo "</div>";  // Close col
                    
                }  // End foreach loop
                
                echo "</div>";  // Close row
                
            } else {
                // No listings found in database
                // WHY: We need to tell the user there are no listings available
                
                // alert-info = Blue colored notification box
                echo "<div class=\"alert alert-info\">";
                echo "  No active listings found. Check back soon!";
                echo "</div>";
            }
        ?>

    </div>  <!-- Close container -->
</body>
</html>