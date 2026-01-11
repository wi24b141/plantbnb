<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';



$listing = null;              
$plants = [];                 
$errorMessage = '';           
$isFavorited = false;         






if (!isset($_GET['id'])) {
    
    header('Location: listings.php');
    exit();
}


if (!is_numeric($_GET['id'])) {
    
    header('Location: listings.php');
    exit();
}



$listingID = intval($_GET['id']);



try {
    
    
    
    
    
    
    
    $listingQuery = "
        SELECT 
            listings.*,
            users.username,
            users.profile_photo_path
        FROM listings
        LEFT JOIN users ON listings.user_id = users.user_id
        WHERE listings.listing_id = :listingID
    ";
    
    
    
    $listingStatement = $connection->prepare($listingQuery);
    
    
    
    $listingStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
    
    
    
    $listingStatement->execute();
    
    
    
    $listing = $listingStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$listing) {
        
        $errorMessage = "Listing not found. Please check the ID and try again.";
    } else {
        
        
        
        
        
        
        
        $plantsQuery = "
            SELECT 
                plant_id,
                plant_type,
                watering_needs,
                light_needs
            FROM plants
            WHERE listing_id = :listingID
            ORDER BY plant_id ASC
        ";
        
        
        $plantsStatement = $connection->prepare($plantsQuery);
        
        
        $plantsStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
        
        
        $plantsStatement->execute();
        
        
        
        $plants = $plantsStatement->fetchAll(PDO::FETCH_ASSOC);
        
        
        
        
        
        
        
        
        if ($isLoggedIn) {
            
            $favoriteCheckQuery = "
                SELECT favorite_id
                FROM favorites
                WHERE user_id = :userID
                AND listing_id = :listingID
            ";
            
            
            $favoriteCheckStatement = $connection->prepare($favoriteCheckQuery);
            
            
            $favoriteCheckStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
            $favoriteCheckStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
            
            
            $favoriteCheckStatement->execute();
            
            
            
            if ($favoriteCheckStatement->fetch()) {
                $isFavorited = true;
            }
        }
        
        
        
        
        
        
        
        $averageRating = 0;      
        $totalRatings = 0;       
        
        
        $authorUserID = intval($listing['user_id']);
        
        $ratingQuery = "
            SELECT 
                AVG(rating) as average_rating,
                COUNT(rating_id) as total_ratings
            FROM ratings
            WHERE rated_user_id = :authorUserID
        ";
        
        
        $ratingStatement = $connection->prepare($ratingQuery);
        
        
        $ratingStatement->bindParam(':authorUserID', $authorUserID, PDO::PARAM_INT);
        
        
        $ratingStatement->execute();
        
        
        $ratingResult = $ratingStatement->fetch(PDO::FETCH_ASSOC);
        
        
        if ($ratingResult && $ratingResult['total_ratings'] > 0) {
            
            $averageRating = round($ratingResult['average_rating'], 1);
            $totalRatings = intval($ratingResult['total_ratings']);
        }
    }

} catch (PDOException $error) {
    
    $errorMessage = "Database error: " . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Mobile responsive meta tag -->
    <!-- WHY: This makes the page scale correctly on mobile phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Details</title>
</head>
<body>
    <div class="container mt-4">
        
        <!-- =============================================================================
             SECTION 1: Back Button
             WHY: User needs a way to go back to the listings page
             ============================================================================= -->
        <div class="row mb-3">
            <!-- col-12 = full width on mobile, col-md-8 = narrower on desktop -->
            <!-- offset-md-2 = center the content on desktop by adding left margin -->
            <div class="col-12 col-md-8 offset-md-2">
                <a href="listings.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Listings
                </a>
            </div>
        </div>

        <!-- =============================================================================
             SECTION 2: Error Message Display (if any error occurred)
             ============================================================================= -->
        <?php
            
            if (!empty($errorMessage)) {
                
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- =============================================================================
             SECTION 3: Main Listing Content (only if listing exists)
             ============================================================================= -->
        <?php
            
            if ($listing) {
                
                
                
                
                
                
                
                $safeTitle = htmlspecialchars($listing['title']);
                $safeDescription = htmlspecialchars($listing['description']);
                $safeLocation = htmlspecialchars($listing['location_approx']);
                $safeStartDate = htmlspecialchars($listing['start_date']);
                $safeEndDate = htmlspecialchars($listing['end_date']);
                $safeListingType = htmlspecialchars($listing['listing_type']);
                $safeStatus = htmlspecialchars($listing['status']);
                $safeUsername = htmlspecialchars($listing['username']);
                $safeExperience = htmlspecialchars($listing['experience']);
                
                
                if (!empty($listing['price_range'])) {
                    $priceRange = htmlspecialchars($listing['price_range']);
                } else {
                    $priceRange = 'Not specified';
                }
                
                
                
                
                
                
                
                
                if (!empty($listing['profile_photo_path'])) {
                    
                    $profilePhotoPath = $listing['profile_photo_path'];
                    
                    
                    $profilePhotoPath = '../' . $profilePhotoPath;
                    
                    
                    $profilePhotoPath = htmlspecialchars($profilePhotoPath);
                } else {
                    
                    $profilePhotoPath = null;
                }
                
                
                
                
                
                
                if (!empty($listing['listing_photo_path'])) {
                    
                    $listingPhotoPath = $listing['listing_photo_path'];
                    
                    
                    $listingPhotoPath = '../' . $listingPhotoPath;
                    
                    
                    $listingPhotoPath = htmlspecialchars($listingPhotoPath);
                } else {
                    
                    $listingPhotoPath = null;
                }
                
                
                
                
                
                
                if (!empty($listing['care_sheet_path'])) {
                    
                    $careSheetPath = $listing['care_sheet_path'];
                    
                    
                    $careSheetPath = '../' . $careSheetPath;
                    
                    
                    $careSheetPath = htmlspecialchars($careSheetPath);
                } else {
                    
                    $careSheetPath = null;
                }
                
                
                
                
                
                
                
                
                if ($safeListingType === 'offer') {
                    $badgeColor = 'success';      
                    $badgeText = 'Offering';
                } else {
                    $badgeColor = 'warning';      
                    $badgeText = 'Looking For';
                }
                
                
                if ($safeStatus === 'active') {
                    $statusColor = 'info';        
                } else if ($safeStatus === 'completed') {
                    $statusColor = 'success';     
                } else {
                    $statusColor = 'secondary';   
                }
        ?>

            <!-- =============================================================================
                 CARD 1: Main Listing Information Card
                 ============================================================================= -->
            <div class="row mb-4">
                <!-- col-12 = full width on mobile, col-md-8 = 2/3 width on desktop -->
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        
                        <!-- Card Header: Badges showing listing type and status -->
                        <div class="card-header bg-light">
                            <!-- Type Badge: "Offering" or "Looking For" -->
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php echo $badgeText; ?>
                            </span>
                            <!-- Status Badge: "Active", "Completed", etc. -->
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo ucfirst($safeStatus); ?>
                            </span>
                        </div>

                        <!-- Listing Photo (if it exists) -->
                        <?php
                            if ($listingPhotoPath) {
                                
                                echo "<img src=\"" . $listingPhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\">";
                            }
                        ?>

                        <!-- Card Body: Main content -->
                        <div class="card-body">
                            
                            <!-- Listing Title -->
                            <h2 class="card-title mb-3">
                                <?php echo $safeTitle; ?>
                            </h2>

                            <!-- Listing Description -->
                            <div class="mb-4">
                                <h5 class="text-secondary">Description</h5>
                                <!-- nl2br converts line breaks to <br> tags -->
                                <p class="card-text">
                                    <?php echo nl2br($safeDescription); ?>
                                </p>
                            </div>

                            <!-- Care Sheet Download Section (only if PDF was uploaded) -->
                            <?php
                                if ($careSheetPath) {
                            ?>
                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <h6 class="mb-2">📄 Care Sheet Available</h6>
                                        <p class="mb-2">Download detailed plant care instructions (PDF)</p>
                                        <!-- Download button -->
                                        <!-- download attribute forces browser to download the file -->
                                        <a href="<?php echo $careSheetPath; ?>" download class="btn btn-primary btn-sm">
                                            Download PDF
                                        </a>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>

                            <!-- Key Details Section -->
                            <!-- WHY: We show location, dates, experience, and price in a grid -->
                            <div class="row mb-4">
                                
                                <!-- Location -->
                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Location</small>
                                    <p class="mb-0"><strong><?php echo $safeLocation; ?></strong></p>
                                </div>

                                <!-- Availability Dates -->
                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Availability</small>
                                    <p class="mb-0">
                                        <strong><?php echo $safeStartDate; ?></strong> to <strong><?php echo $safeEndDate; ?></strong>
                                    </p>
                                </div>

                                <!-- Experience Required -->
                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Experience Required</small>
                                    <p class="mb-0"><strong><?php echo $safeExperience; ?></strong></p>
                                </div>

                                <!-- Price Range -->
                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Price Range</small>
                                    <p class="mb-0"><strong><?php echo $priceRange; ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================================================
                 CARD 2: Author Information Card
                 ============================================================================= -->
            <div class="row mb-4">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Posted By</h5>
                            
                            <!-- Profile Photo (if it exists) -->
                            <?php
                                if ($profilePhotoPath) {
                                    
                                    echo "<img src=\"" . $profilePhotoPath . "\" alt=\"" . $safeUsername . "\" class=\"rounded-circle mb-3\" style=\"width: 60px; height: 60px;\">";
                                }
                            ?>

                            <!-- Username -->
                            <h6 class="mb-2">
                                <strong><?php echo $safeUsername; ?></strong>
                            </h6>
                            
                            <!-- =============================================================================
                                 User Rating Display
                                 WHY: Show the average rating this user has received from others
                                 ============================================================================= -->
                            <?php
                                
                                if ($totalRatings > 0) {
                                    
                                    
                                    
                                    
                                    
                                    
                                    $fullStars = floor($averageRating);
                                    
                                    
                                    
                                    
                                    $hasHalfStar = ($averageRating - $fullStars) >= 0.5;
                                    
                                    
                                    $starsDisplay = '';
                                    
                                    
                                    for ($i = 0; $i < $fullStars; $i = $i + 1) {
                                        $starsDisplay = $starsDisplay . '⭐';
                                    }
                                    
                                    
                                    if ($hasHalfStar) {
                                        $starsDisplay = $starsDisplay . '✨';
                                    }
                                    
                                    
                                    echo '<div class="mb-3">';
                                    echo '<div class="text-warning fw-bold">';
                                    echo $starsDisplay;
                                    echo ' ' . htmlspecialchars($averageRating) . ' / 5.0';
                                    echo '</div>';
                                    echo '<small class="text-muted">';
                                    echo 'Based on ' . htmlspecialchars($totalRatings) . ' rating';
                                    
                                    if ($totalRatings > 1) {
                                        echo 's';
                                    }
                                    echo '</small>';
                                    echo '</div>';
                                    
                                } else {
                                    
                                    echo '<div class="mb-3">';
                                    echo '<small class="text-muted">No ratings yet</small>';
                                    echo '</div>';
                                }
                            ?>
                            
                            <!-- Contact Button -->
                            <?php
                                
                                if ($isLoggedIn) {
                                    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $authorUserID) {
                                        
                                        echo '<button class="btn btn-secondary" disabled>It\'s your listing</button>';
                                    } else {
                                        
                                        echo '<a href="../users/message-conversation.php?user_id=' . intval($authorUserID) . '" class="btn btn-success">Contact Seller</a>';
                                    }
                                } else {
                                    
                                    echo '<a href="../users/login.php" class="btn btn-outline-secondary">Login to Contact</a>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============================================================================
                 SECTION 4: Plants List (only if there are plants)
                 ============================================================================= -->
            <?php
                
                if (count($plants) > 0) {
            ?>
                <div class="row mb-4">
                    <div class="col-12 col-md-8 offset-md-2">
                        <h4 class="mb-3">Plants Included</h4>

                        <!-- Plants Grid -->
                        <!-- col-12 = 1 plant per row on mobile, col-md-6 = 2 plants per row on desktop -->
                        <div class="row">
                            <?php
                                
                                foreach ($plants as $plant) {
                                    
                                    
                                    $safePlantType = htmlspecialchars($plant['plant_type']);
                                    $safeWateringNeeds = htmlspecialchars($plant['watering_needs']);
                                    $safeLightNeeds = htmlspecialchars($plant['light_needs']);
                            ?>
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="card">
                                        <!-- Plant Name Header -->
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <?php echo $safePlantType; ?>
                                            </h6>
                                        </div>

                                        <!-- Plant Care Requirements -->
                                        <div class="card-body">
                                            <!-- Watering Needs -->
                                            <div class="mb-3">
                                                <strong>💧 Watering Needs</strong>
                                                <p class="mb-0">
                                                    <?php echo $safeWateringNeeds; ?>
                                                </p>
                                            </div>

                                            <!-- Light Needs -->
                                            <div>
                                                <strong>☀️ Light Needs</strong>
                                                <p class="mb-0">
                                                    <?php echo $safeLightNeeds; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            ?>

            <!-- =============================================================================
                 SECTION 5: Action Buttons
                 ============================================================================= -->
            <div class="row mb-5">
                <div class="col-12 col-md-8 offset-md-2">
                    <!-- Favorite/Unfavorite Button -->
                    <!-- WHY: Only show this button if user is logged in -->
                    <?php
                        if ($isLoggedIn) {
                            
                            
                            if ($isFavorited) {
                                
                    ?>
                                <div class="mb-2">
                                    <!-- Form submits to listing-unfavorite.php -->
                                    <!-- WHY: We use POST method for security -->
                                    <form method="POST" action="listing-unfavorite.php">
                                        <!-- Hidden field: which listing to unfavorite -->
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        
                                        <!-- Hidden field: where to redirect after unfavoriting -->
                                        <!-- WHY: We want to come back to this same page after unfavoriting -->
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
                                        <!-- Submit button -->
                                        <!-- WHY: Red color indicates removal action -->
                                        <button type="submit" class="btn btn-outline-danger btn-lg d-block d-md-inline-block">
                                            ❤️ Remove from Favorites
                                        </button>
                                    </form>
                                </div>
                    <?php
                            } else {
                                
                    ?>
                                <div class="mb-2">
                                    <!-- Form submits to listing-favorite.php -->
                                    <!-- WHY: We use POST method for security -->
                                    <form method="POST" action="listing-favorite.php">
                                        <!-- Hidden field: which listing to favorite -->
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        
                                        <!-- Hidden field: where to redirect after favoriting -->
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
                                        <!-- Submit button -->
                                        <!-- WHY: Gray color for secondary action -->
                                        <button type="submit" class="btn btn-secondary">
                                            ♡ Add to Favorites
                                        </button>
                                    </form>
                                </div>
                    <?php
                            }
                        } else {
                            
                    ?>
                            <div class="mb-2">
                                <a href="login.php" class="btn btn-outline-secondary btn-lg d-block d-md-inline-block">
                                    ♡ Login to Favorite
                                </a>
                            </div>
                    <?php
                        }
                    ?>
                </div>
            </div>

        <?php
            }
        ?>
    </div>
</body>
</html>