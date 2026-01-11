<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$userID = intval($_SESSION['user_id']);

// Initialize output variables to prevent undefined variable errors in view layer
$favoritedListings = array();
$errorMessage = '';

try {
    // NOTE: Three-way INNER JOIN retrieves favorites (user's selections), listings (plant details), and users (owner info)
    // JOIN logic: favorites.listing_id → listings.listing_id → users.user_id ensures referential integrity
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
        FROM favorites
        INNER JOIN listings ON favorites.listing_id = listings.listing_id
        INNER JOIN users ON listings.user_id = users.user_id
        WHERE favorites.user_id = :userID
        ORDER BY favorites.created_at DESC
    ";

    // NOTE: PDO prepared statements prevent SQL Injection by separating SQL logic from user data
    $statement = $connection->prepare($sqlQuery);
    
    // Bind parameter as integer to enforce type safety and prevent type juggling vulnerabilities
    $statement->bindParam(':userID', $userID, PDO::PARAM_INT);
    
    $statement->execute();
    
    // Fetch as associative array for easier access to column values by name
    $favoritedListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    // NOTE: Catching PDOException prevents application crash and allows graceful error display to user
    // In production, error details should be logged server-side rather than exposed to end users
    $errorMessage = "Database error: " . $error->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Responsive meta tag enables mobile-first Bootstrap grid system -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - PlantBnB</title>
</head>
<body>
    <!-- Bootstrap container centers content with responsive padding (max-width varies by breakpoint) -->
    <!-- mt-5 applies top margin using Bootstrap's spacing scale (1-5) -->
    <div class="container mt-5">
        
        <h2 class="mb-4">❤️ My Favorite Listings</h2>

        <!-- Error Display Section -->
        <?php
            // Display database errors using Bootstrap alert component for visual consistency
            if ($errorMessage !== '') {
                echo "<div class=\"alert alert-danger\">";
                
                // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) by encoding special HTML characters
                echo htmlspecialchars($errorMessage);
                
                echo "</div>";
            }
        ?>

        <!-- Favorited Listings Grid -->
        <?php
            // Conditional rendering based on query results
            if (count($favoritedListings) > 0) {
                
                // NOTE: Bootstrap's responsive grid uses row-cols-* classes for automatic column sizing
                // row-cols-1: Mobile (< 768px) displays 1 card per row for readability
                // row-cols-md-3: Medium+ screens (≥ 768px) display 3 cards per row for efficient space usage
                // g-4: Gutter spacing (1.5rem) prevents card overlap and improves visual separation
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                // Iterate through result set to dynamically generate listing cards
                foreach ($favoritedListings as $listing) {
                    
                    // NOTE: All user-generated content must be sanitized before output to prevent XSS attacks
                    // htmlspecialchars() encodes <, >, &, ", and ' to their HTML entity equivalents
                    $listingID = intval($listing['listing_id']);
                    $safeTitle = htmlspecialchars($listing['title']);
                    $safeUsername = htmlspecialchars($listing['username']);
                    $safeLocation = htmlspecialchars($listing['location_approx']);
                    $safeStartDate = htmlspecialchars($listing['start_date']);
                    $safeEndDate = htmlspecialchars($listing['end_date']);
                    $safeListingType = htmlspecialchars($listing['listing_type']);
                    
                    // Construct absolute image path for XAMPP environment (/plantbnb/ is the project root)
                    if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                        $photoPath = $listing['listing_photo_path'];
                        $fullPhotoPath = "/plantbnb/" . $photoPath;
                        $safePhotoPath = htmlspecialchars($fullPhotoPath);
                    } else {
                        $safePhotoPath = null;
                    }
                    
                    // Map listing type to Bootstrap contextual color classes for visual distinction
                    if ($safeListingType === 'offer') {
                        $badgeColor = 'success';
                    } else {
                        $badgeColor = 'warning';
                    }
                    
                    // Bootstrap column wrapper (auto-sized by row-cols-* classes on parent)
                    echo "<div class=\"col\">";
                    
                    // NOTE: h-100 ensures all cards in a row have equal height for consistent grid alignment
                    echo "  <div class=\"card h-100\">";
                    
                    // Display listing photo or placeholder with fixed height for uniform appearance
                    if ($safePhotoPath !== null) {
                        echo "    <img src=\"" . $safePhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"height: 200px;\">";
                    } else {
                        // d-flex with align-items-center and justify-content-center uses flexbox for perfect centering
                        echo "    <div class=\"bg-light d-flex align-items-center justify-content-center\" style=\"height: 200px;\">";
                        echo "      <span class=\"text-muted\">No photo</span>";
                        echo "    </div>";
                    }
                    
                    // Card header displays listing type badge using contextual color classes
                    echo "    <div class=\"card-header bg-light\">";
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    echo "    </div>";
                    
                    // Card body contains listing metadata and action buttons
                    echo "    <div class=\"card-body\">";
                    
                    echo "      <h5 class=\"card-title\">" . $safeTitle . "</h5>";
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: " . $safeUsername . "</h6>";
                    
                    echo "      <p class=\"card-text\">";
                    echo "        <strong>Location:</strong> " . $safeLocation . "<br>";
                    echo "        <strong>Available:</strong> " . $safeStartDate . " to " . $safeEndDate;
                    echo "      </p>";
                    
                    // d-grid makes button full-width for improved mobile usability
                    echo "      <div class=\"d-grid mb-2\">";
                    echo "        <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success\">View Details</a>";
                    echo "      </div>";
                    
                    // POST form prevents favorite removal via GET request (prevents CSRF via URL manipulation)
                    echo "      <form method=\"POST\" action=\"listing-unfavorite.php\">";
                    echo "        <input type=\"hidden\" name=\"listing_id\" value=\"" . $listingID . "\">";
                    echo "        <div class=\"d-grid\">";
                    echo "          <button type=\"submit\" class=\"btn btn-outline-danger\">❌ Remove from Favorites</button>";
                    echo "        </div>";
                    echo "      </form>";
                    
                    echo "    </div>";
                    echo "  </div>";
                    echo "</div>";
                    
                }
                
                echo "</div>";
                
            } else {
                // Display empty state message to guide user toward creating favorites
                echo "<div class=\"alert alert-info\">";
                echo "  <h4 class=\"alert-heading\">No favorites yet!</h4>";
                echo "  <p class=\"mb-0\">You haven't favorited any listings. Browse <a href=\"listings.php\" class=\"alert-link\">all listings</a> and click the ❤️ button to save your favorites!</p>";
                echo "</div>";
            }
        ?>

    </div>
</body>
</html>