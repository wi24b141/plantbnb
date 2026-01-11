<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize variables to prevent undefined variable errors
$listing = null;
$plants = [];
$errorMessage = '';
$isFavorited = false;

// Validate listing ID parameter from query string
// NOTE: Input validation is critical to prevent invalid database queries and potential injection attacks
if (!isset($_GET['id'])) {
    header('Location: listings.php');
    exit();
}

if (!is_numeric($_GET['id'])) {
    header('Location: listings.php');
    exit();
}

$listingID = intval($_GET['id']);

// NOTE: PDO exception handling ensures graceful degradation if database connection fails
try {
    
    /**
     * Query 1: Retrieve listing details with author information
     * Uses LEFT JOIN to include user data even if author record is missing
     * NOTE: Prepared statements with bound parameters prevent SQL injection attacks
     */
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
        /**
         * Query 2: Retrieve all plants associated with this listing
         * Returns empty array if no plants exist (fetchAll behavior)
         */
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
        
        /**
         * Query 3: Check if authenticated user has favorited this listing
         * Determines whether to display "Add" or "Remove" favorite button
         */
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
        
        /**
         * Query 4: Calculate average rating for listing author
         * Uses aggregate functions (AVG, COUNT) to compute reputation metrics
         */
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
    <!-- Viewport meta ensures responsive scaling on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listing Details</title>
</head>
<body>
    <!-- Main container with Bootstrap margin-top utility class -->
    <div class="container mt-4">
        
        <!-- Navigation: Back button to listings page -->
        <div class="row mb-3">
            <!-- Bootstrap grid: col-md-8 offset-md-2 centers content on medium+ screens -->
            <div class="col-12 col-md-8 offset-md-2">
                <a href="listings.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Listings
                </a>
            </div>
        </div>

        <!-- Error Alert Section -->
        <?php
            if (!empty($errorMessage)) {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                // NOTE: htmlspecialchars prevents XSS attacks by encoding HTML special characters
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Main Listing Display Section -->
        <?php
            if ($listing) {
                
                /**
                 * Sanitize all output data to prevent Cross-Site Scripting (XSS) attacks
                 * NOTE: htmlspecialchars encodes special characters, preventing malicious script injection
                 */
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
                
                /**
                 * Construct relative file paths for uploaded assets
                 * Database stores root-relative paths; adjust for current directory context
                 */
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
                
                /**
                 * Determine Bootstrap badge color classes based on listing metadata
                 * Provides visual differentiation between listing types and statuses
                 */
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

            <!-- Main Listing Card -->
            <div class="row mb-4">
                <!-- Bootstrap grid: centered column, responsive width -->
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        
                        <!-- Header with type and status badges -->
                        <div class="card-header bg-light">
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php echo $badgeText; ?>
                            </span>
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo ucfirst($safeStatus); ?>
                            </span>
                        </div>

                        <?php
                            if ($listingPhotoPath) {
                                echo "<img src=\"" . $listingPhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\">";
                            }
                        ?>

                        <div class="card-body">
                            
                            <h2 class="card-title mb-3">
                                <?php echo $safeTitle; ?>
                            </h2>

                            <div class="mb-4">
                                <h5 class="text-secondary">Description</h5>
                                <!-- nl2br() preserves user-entered line breaks in output -->
                                <p class="card-text">
                                    <?php echo nl2br($safeDescription); ?>
                                </p>
                            </div>

                            <!-- Care Sheet Download Section -->
                            <?php
                                if ($careSheetPath) {
                            ?>
                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <h6 class="mb-2">📄 Care Sheet Available</h6>
                                        <p class="mb-2">Download detailed plant care instructions (PDF)</p>
                                        <!-- HTML5 download attribute triggers file download -->
                                        <a href="<?php echo $careSheetPath; ?>" download class="btn btn-primary btn-sm">
                                            Download PDF
                                        </a>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>

                            <!-- Listing Metadata Grid: Uses Bootstrap col-md-6 for two-column layout on medium+ screens -->
                            <div class="row mb-4">
                                
                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Location</small>
                                    <p class="mb-0"><strong><?php echo $safeLocation; ?></strong></p>
                                </div>

                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Availability</small>
                                    <p class="mb-0">
                                        <strong><?php echo $safeStartDate; ?></strong> to <strong><?php echo $safeEndDate; ?></strong>
                                    </p>
                                </div>

                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Experience Required</small>
                                    <p class="mb-0"><strong><?php echo $safeExperience; ?></strong></p>
                                </div>

                                <div class="col-12 col-md-6 mb-3">
                                    <small class="text-muted">Price Range</small>
                                    <p class="mb-0"><strong><?php echo $priceRange; ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Author Information Card -->
            <div class="row mb-4">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Posted By</h5>
                            
                            <?php
                                if ($profilePhotoPath) {
                                    echo "<img src=\"" . $profilePhotoPath . "\" alt=\"" . $safeUsername . "\" class=\"rounded-circle mb-3\" style=\"width: 60px; height: 60px;\">";
                                }
                            ?>

                            <h6 class="mb-2">
                                <strong><?php echo $safeUsername; ?></strong>
                            </h6>
                            
                            <!-- User Rating Display -->
                            <?php
                                if ($totalRatings > 0) {
                                    
                                    /**
                                     * Generate star rating visualization
                                     * Calculates full stars and half-star based on average rating
                                     */
                                    $fullStars = floor($averageRating);
                                    $hasHalfStar = ($averageRating - $fullStars) >= 0.5;
                                    $starsDisplay = '';
                                    
                                    for ($i = 0; $i < $fullStars; $i = $i + 1) {
                                        $starsDisplay = $starsDisplay . '⭐';
                                    }
                                    
                                    // Add half star if needed
                                    if ($hasHalfStar) {
                                        $starsDisplay = $starsDisplay . '✨';
                                    }
                                    
                                    // STEP 2: Display the rating
                                    echo '<div class="mb-3">';
                                    echo '<div class="text-warning fw-bold">';
                                    echo $starsDisplay;
                                    echo ' ' . htmlspecialchars($averageRating) . ' / 5.0';
                                    echo '</div>';
                                    echo '<small class="text-muted">';
                                    echo 'Based on ' . htmlspecialchars($totalRatings) . ' rating';
                                    // Add 's' if more than 1 rating
                                    if ($totalRatings > 1) {
                                        echo 's';
                                    }
                                    echo '</small>';
                                    echo '</div>';
                                    
                                } else {
                                    // No ratings yet
                                    echo '<div class="mb-3">';
                                    echo '<small class="text-muted">No ratings yet</small>';
                                    echo '</div>';
                                }
                            ?>
                            
                            <!-- Contact Button: Disabled for own listings -->
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
                // Check if there are any plants to display
                if (count($plants) > 0) {
            ?>
                <div class="row mb-4">
                    <div class="col-12 col-md-8 offset-md-2">
                        <h4 class="mb-3">Plants Included</h4>

                        <!-- Bootstrap grid: col-md-6 creates two-column layout on medium+ screens -->
                        <div class="row">
                            <?php
                                foreach ($plants as $plant) {
                                    
                                    $safePlantType = htmlspecialchars($plant['plant_type']);
                                    $safeWateringNeeds = htmlspecialchars($plant['watering_needs']);
                                    $safeLightNeeds = htmlspecialchars($plant['light_needs']);
                            ?>
                                <div class="col-12 col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <?php echo $safePlantType; ?>
                                            </h6>
                                        </div>

                                        <div class="card-body">
                                            <div class="mb-3">
                                                <strong>💧 Watering Needs</strong>
                                                <p class="mb-0">
                                                    <?php echo $safeWateringNeeds; ?>
                                                </p>
                                            </div>

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

            <!-- Action Buttons: Favorite/Unfavorite -->
            <div class="row mb-5">
                <div class="col-12 col-md-8 offset-md-2">
                    <?php
                        if ($isLoggedIn) {
                            
                            if ($isFavorited) {
                    ?>
                                <div class="mb-2">
                                    <!-- NOTE: POST method prevents CSRF attacks and keeps listing_id out of browser history -->
                                    <form method="POST" action="listing-unfavorite.php">
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
                                        <!-- Bootstrap utility: d-block d-md-inline-block creates responsive button layout -->
                                        <button type="submit" class="btn btn-outline-danger btn-lg d-block d-md-inline-block">
                                            ❤️ Remove from Favorites
                                        </button>
                                    </form>
                                </div>
                    <?php
                            } else {
                    ?>
                                <div class="mb-2">
                                    <form method="POST" action="listing-favorite.php">
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
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