<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$activeListings = array();
$errorMessage = '';

try {
    // NOTE: We check if someone is logged in first.
    // If they ARE logged in, we hide THEIR listings from the browse page.
    // If they are NOT logged in (guest), we show ALL active listings.
    
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        // LOGGED IN USER: Exclude their own listings
        
        // NOTE: This query uses an INNER JOIN to combine listings and users tables.
        // We need data from BOTH tables (listing details + username).
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
            AND listings.user_id != :current_user_id
            ORDER BY listings.created_at DESC
        ";
        
        // NOTE: Prepare the SQL query. This creates a "template" that keeps
        // the SQL code separate from user data. The :current_user_id is a placeholder.
        $statement = $connection->prepare($sqlQuery);
        
        // NOTE: Convert session user_id to integer to ensure it's a number.
        // This is extra protection against someone tampering with the session.
        $currentUserId = intval($_SESSION['user_id']);
        
        // NOTE: bindValue() fills in the :current_user_id placeholder with the actual number.
        // PDO::PARAM_INT tells the database "this is an integer, not text".
        // This prevents SQL injection because the value is treated as DATA, not CODE.
        $statement->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
        
        // Execute the query with the bound parameter
        $statement->execute();
        
    } else {
        // GUEST USER: Show all active listings
        
        // NOTE: This is the simpler query for guests. No placeholders needed
        // because we are not using any user input in this query.
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
            ORDER BY listings.created_at DESC
        ";
        
        // NOTE: Even though there are no placeholders, we still use prepare()
        // as a security best practice. It's the safe way to run queries.
        $statement = $connection->prepare($sqlQuery);
        
        // Execute the query (no parameters to bind)
        $statement->execute();
    }
    
    // NOTE: Fetch all results as an associative array.
    // FETCH_ASSOC means we access data using column names like $row['title']
    $activeListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    // NOTE: Graceful error handling prevents application crashes and provides
    // user feedback without exposing sensitive system information in production.
    $errorMessage = "Database error: " . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Listings</title>
</head>
<body>
    <!-- Main Container: Bootstrap's container class provides responsive fixed-width layout with automatic horizontal margins -->
    <div class="container mt-5">
        
        <h2 class="mb-4">Recent Listings</h2>

        <?php
            // Conditional navigation link for authenticated users
            if (isset($isLoggedIn) && $isLoggedIn) {
                echo '<div class="mb-3">';
                echo '  <a href="my-listings.php" class="btn btn-outline-primary">My Listings</a>';
                echo '</div>';
            }
        ?>

        <!-- Error Display Section -->
        <?php
            if ($errorMessage !== '') {
                // NOTE: htmlspecialchars() encodes special characters to HTML entities,
                // preventing Cross-Site Scripting (XSS) attacks by ensuring user input
                // is treated as text rather than executable HTML/JavaScript.
                echo "<div class=\"alert alert-danger\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Listings Grid Section -->
        <?php
            if (count($activeListings) > 0) {
                
                // NOTE: Bootstrap 5 grid system uses flexbox for responsive layouts.
                // row-cols-1 creates a single-column layout on mobile devices.
                // row-cols-md-3 switches to a three-column layout on medium screens and above.
                // g-4 applies a gutter (gap) of 1.5rem between columns for visual separation.
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                foreach ($activeListings as $listing) {
                    
                    // Data sanitization before output to prevent XSS attacks
                    $listingID = intval($listing['listing_id']);
                    $safeTitle = htmlspecialchars($listing['title']);
                    $safeUsername = htmlspecialchars($listing['username']);
                    $safeLocation = htmlspecialchars($listing['location_approx']);
                    $safeStartDate = htmlspecialchars($listing['start_date']);
                    $safeEndDate = htmlspecialchars($listing['end_date']);
                    $safeListingType = htmlspecialchars($listing['listing_type']);
                    
                    // Construct image path with appropriate fallback handling
                    if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                        $photoPath = $listing['listing_photo_path'];
                        $fullPhotoPath = "/plantbnb/" . $photoPath;
                        $safePhotoPath = htmlspecialchars($fullPhotoPath);
                    } else {
                        $safePhotoPath = null;
                    }
                    
                    // Badge color determination based on listing type
                    if ($safeListingType === 'offer') {
                        $badgeColor = 'success';
                    } else {
                        $badgeColor = 'warning';
                    }
                    
                    // Card markup begins here
                    echo "<div class=\"col\">";
                    
                    // h-100 ensures all cards in a row maintain equal height regardless of content length
                    echo "  <div class=\"card h-100\">";
                    
                    // Image display with fixed height for visual consistency
                    if ($safePhotoPath !== null) {
                        echo "    <img src=\"" . $safePhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"height: 200px;\">";
                    } else {
                        // Placeholder uses d-flex with align-items-center and justify-content-center
                        // to center content both vertically and horizontally within the container
                        echo "    <div class=\"bg-light d-flex align-items-center justify-content-center\" style=\"height: 200px;\">";
                        echo "      <span class=\"text-muted\">No photo</span>";
                        echo "    </div>";
                    }
                    
                    // Listing type badge in card header
                    echo "    <div class=\"card-header bg-light\">";
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    echo "    </div>";
                    
                    // Card body containing listing metadata
                    echo "    <div class=\"card-body\">";
                    echo "      <h5 class=\"card-title\">" . $safeTitle . "</h5>";
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: " . $safeUsername . "</h6>";
                    echo "      <p class=\"card-text\">";
                    echo "        <strong>Location:</strong> " . $safeLocation . "<br>";
                    echo "        <strong>Available:</strong> " . $safeStartDate . " to " . $safeEndDate;
                    echo "      </p>";
                    
                    // Action button: d-grid with flex-md-fill provides full-width button on mobile
                    // and flexible width on medium+ screens for consistent layout
                    echo "      <div class=\"d-grid gap-2 d-md-flex\">";
                    echo "        <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success flex-md-fill\">View Details</a>";
                    echo "      </div>";
                    
                    echo "    </div>";
                    echo "  </div>";
                    echo "</div>";
                    
                }
                
                echo "</div>";
                
            } else {
                // Empty state message
                echo "<div class=\"alert alert-info\">";
                echo "  No active listings found. Check back soon!";
                echo "</div>";
            }
        ?>

    </div> <!-- End of container -->
</body>
</html>