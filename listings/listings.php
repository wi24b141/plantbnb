<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$activeListings = array();
$errorMessage = '';

try {
    // NOTE: This query demonstrates an INNER JOIN between listings and users tables.
    // The JOIN is necessary because listing data is normalized across two tables.
    // We select from listings but need the username from the users table.
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

    // Conditional filtering: exclude current user's listings from browse view
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $sqlQuery .= "\n        AND listings.user_id != :current_user_id\n    ";
    }

    $sqlQuery .= "\n        ORDER BY listings.created_at DESC\n    ";

    // NOTE: PDO prepared statements separate SQL logic from user data, preventing
    // SQL injection attacks by treating parameters as data rather than executable code.
    $statement = $connection->prepare($sqlQuery);

    // Bind parameter with explicit type casting for additional security
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $currentUserId = intval($_SESSION['user_id']);
        $statement->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    }

    $statement->execute();

    // Fetch as associative array for easy access via column names
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