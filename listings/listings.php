<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';







$activeListings = array();  
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
        FROM listings
        INNER JOIN users ON listings.user_id = users.user_id
        WHERE listings.status = 'active'
    ";

    
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $sqlQuery .= "\n        AND listings.user_id != :current_user_id\n    ";
    }

    
    $sqlQuery .= "\n        ORDER BY listings.created_at DESC\n    ";

    
    
    $statement = $connection->prepare($sqlQuery);

    
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
        $currentUserId = intval($_SESSION['user_id']);
        $statement->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    }

    
    
    $statement->execute();

    
    
    
    $activeListings = $statement->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    
    
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
            
            
            if ($errorMessage !== '') {
                
                
                echo "<div class=\"alert alert-danger\">";
                
                
                
                echo htmlspecialchars($errorMessage);
                
                echo "</div>";
            }
        ?>

        <!-- =================================================================== -->
        <!-- DISPLAY LISTINGS (if we have any)                                  -->
        <!-- =================================================================== -->
        <?php
            
            
            if (count($activeListings) > 0) {
                
                
                
                
                
                
                
                echo "<div class=\"row row-cols-1 row-cols-md-3 g-4\">";
                
                
                
                foreach ($activeListings as $listing) {
                    
                    
                    
                    
                    
                    
                    
                    
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
                    
                    
                    
                    
                    
                    
                    
                    
                    echo "      <div class=\"d-grid gap-2 d-md-flex\">";
                    echo "        <a href=\"listing-details.php?id=" . $listingID . "\" class=\"btn btn-success flex-md-fill\">View Details</a>";
                    echo "      </div>";
                    
                    echo "    </div>";  
                    
                    echo "  </div>";  
                    
                    echo "</div>";  
                    
                }  
                
                echo "</div>";  
                
            } else {
                
                
                
                
                echo "<div class=\"alert alert-info\">";
                echo "  No active listings found. Check back soon!";
                echo "</div>";
            }
        ?>

    </div>  <!-- Close container -->
</body>
</html>