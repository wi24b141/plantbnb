<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$userID = intval($_SESSION['user_id']);


$favoritedListings = array();
$errorMessage = '';

try {
    
    
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

    
    $statement = $connection->prepare($sqlQuery);
    
    
    $statement->bindParam(':userID', $userID, PDO::PARAM_INT);
    
    $statement->execute();
    
    
    $favoritedListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    
    
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
            
            if ($errorMessage !== '') {
                echo "<div class=\"alert alert-danger\">";
                
                
                echo htmlspecialchars($errorMessage);
                
                echo "</div>";
            }
        ?>

        <!-- Favorited Listings Grid -->
        <?php
            
            if (count($favoritedListings) > 0) {
                
                
                
                
                
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                
                foreach ($favoritedListings as $listing) {
                    
                    
                    
                    $listingID = intval($listing['listing_id']);
                    $safeTitle = htmlspecialchars($listing['title']);
                    $safeUsername = htmlspecialchars($listing['username']);
                    $safeLocation = htmlspecialchars($listing['location_approx']);
                    $safeStartDate = htmlspecialchars($listing['start_date']);
                    $safeEndDate = htmlspecialchars($listing['end_date']);
                    $safeListingType = htmlspecialchars($listing['listing_type']);
                    
                    
                    if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                        $photoPath = $listing['listing_photo_path'];
                        $fullPhotoPath = "/plantbnb/" . $photoPath;
                        $safePhotoPath = htmlspecialchars($fullPhotoPath);
                    } else {
                        $safePhotoPath = null;
                    }
                    
                    
                    if ($safeListingType === 'offer') {
                        $badgeColor = 'success';
                    } else {
                        $badgeColor = 'warning';
                    }
                    
                    
                    echo "<div class=\"col\">";
                    
                    
                    echo "  <div class=\"card h-100\">";
                    
                    
                    if ($safePhotoPath !== null) {
                        echo "    <img src=\"" . $safePhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"height: 200px;\">";
                    } else {
                        
                        echo "    <div class=\"bg-light d-flex align-items-center justify-content-center\" style=\"height: 200px;\">";
                        echo "      <span class=\"text-muted\">No photo</span>";
                        echo "    </div>";
                    }
                    
                    
                    echo "    <div class=\"card-header bg-light\">";
                    echo "      <span class=\"badge bg-" . $badgeColor . "\">" . ucfirst($safeListingType) . "</span>";
                    echo "    </div>";
                    
                    
                    echo "    <div class=\"card-body\">";
                    
                    echo "      <h5 class=\"card-title\">" . $safeTitle . "</h5>";
                    echo "      <h6 class=\"card-subtitle mb-2 text-muted\">Posted by: " . $safeUsername . "</h6>";
                    
                    echo "      <p class=\"card-text\">";
                    echo "        <strong>Location:</strong> " . $safeLocation . "<br>";
                    echo "        <strong>Available:</strong> " . $safeStartDate . " to " . $safeEndDate;
                    echo "      </p>";
                    
                    
                    echo "      <div class=\"d-grid mb-2\">";
                    echo "        <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success\">View Details</a>";
                    echo "      </div>";
                    
                    
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
                
                echo "<div class=\"alert alert-info\">";
                echo "  <h4 class=\"alert-heading\">No favorites yet!</h4>";
                echo "  <p class=\"mb-0\">You haven't favorited any listings. Browse <a href=\"listings.php\" class=\"alert-link\">all listings</a> and click the ❤️ button to save your favorites!</p>";
                echo "</div>";
            }
        ?>

    </div>
</body>
</html>