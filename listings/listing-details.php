<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// STEP 2: Create empty variables to store data from the database
// WHY: We initialize them first so they exist even if database queries fail
$listing = null;              // Will hold listing data (title, description, etc.)
$plants = [];                 // Will hold array of plants for this listing
$errorMessage = '';           // Will hold any error messages to show the user
$isFavorited = false;         // Will be true if user has favorited this listing

// STEP 3: Check if the URL has a valid listing ID
// WHY: This page needs ?id=123 in the URL to know which listing to display
// Example URL: listing-details.php?id=5

// First, check if 'id' exists in the URL
if (!isset($_GET['id'])) {
    // ID is missing, redirect to listings page
    header('Location: listings.php');
    exit();
}

// Second, check if the ID is a number
if (!is_numeric($_GET['id'])) {
    // ID is not a number (maybe someone typed ?id=hello), redirect
    header('Location: listings.php');
    exit();
}

// STEP 4: Store the listing ID as an integer
// WHY: We convert string to integer for security and to use in SQL query
$listingID = intval($_GET['id']);

// STEP 5: Use try-catch to handle database errors safely
// WHY: If the database is down or SQL has errors, we catch it here
try {
    
    // =============================================================================
    // DATABASE QUERY 1: Get the listing information + author info
    // =============================================================================
    // WHY: We need listing details (title, description, dates) AND the username
    //      of the person who posted it. We use JOIN to get both in one query.
    
    $listingQuery = "
        SELECT 
            listings.*,
            users.username,
            users.profile_photo_path
        FROM listings
        LEFT JOIN users ON listings.user_id = users.user_id
        WHERE listings.listing_id = :listingID
    ";
    
    // PREPARE the SQL query
    // WHY: This separates SQL code from data to prevent SQL injection attacks
    $listingStatement = $connection->prepare($listingQuery);
    
    // BIND the listing ID parameter
    // WHY: This safely inserts $listingID into the :listingID placeholder
    $listingStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
    
    // EXECUTE the query
    // WHY: This actually runs the SQL and fetches data from database
    $listingStatement->execute();
    
    // FETCH the result as an associative array
    // WHY: fetch() gets ONE row. If no row exists, it returns false/null
    $listing = $listingStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if the listing was found
    if (!$listing) {
        // Listing ID does not exist in database
        $errorMessage = "Listing not found. Please check the ID and try again.";
    } else {
        // Listing was found! Now get the plants for this listing.
        
        // =============================================================================
        // DATABASE QUERY 2: Get all plants for this listing
        // =============================================================================
        // WHY: Each listing can have multiple plants. We need to show all of them.
        
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
        
        // PREPARE the plants query
        $plantsStatement = $connection->prepare($plantsQuery);
        
        // BIND the listing ID
        $plantsStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
        
        // EXECUTE the query
        $plantsStatement->execute();
        
        // FETCH ALL plants as an array
        // WHY: fetchAll() gets ALL rows, not just one. Returns empty array if none.
        $plants = $plantsStatement->fetchAll(PDO::FETCH_ASSOC);
        
        // =============================================================================
        // DATABASE QUERY 3: Check if user has favorited this listing
        // =============================================================================
        // WHY: We need to know if the current user already favorited this listing
        //      so we can show "Add to Favorites" or "Remove from Favorites" button
        
        // Only check if user is logged in
        if ($isLoggedIn) {
            
            $favoriteCheckQuery = "
                SELECT favorite_id
                FROM favorites
                WHERE user_id = :userID
                AND listing_id = :listingID
            ";
            
            // PREPARE the query
            $favoriteCheckStatement = $connection->prepare($favoriteCheckQuery);
            
            // BIND both user ID and listing ID
            $favoriteCheckStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
            $favoriteCheckStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
            
            // EXECUTE the query
            $favoriteCheckStatement->execute();
            
            // CHECK if a row was found
            // WHY: If fetch() returns something, it means a favorite row exists
            if ($favoriteCheckStatement->fetch()) {
                $isFavorited = true;
            }
        }
        
        // =============================================================================
        // DATABASE QUERY 4: Get the average rating for the listing author
        // =============================================================================
        // WHY: We want to show how other users have rated the person who posted this listing
        //      This helps users decide if they want to work with this person
        
        $averageRating = 0;      // Will hold the average rating (0 to 5)
        $totalRatings = 0;       // Will hold the number of ratings this user has received
        
        // Get the user_id of the listing author
        $authorUserID = intval($listing['user_id']);
        
        $ratingQuery = "
            SELECT 
                AVG(rating) as average_rating,
                COUNT(rating_id) as total_ratings
            FROM ratings
            WHERE rated_user_id = :authorUserID
        ";
        
        // PREPARE the query
        $ratingStatement = $connection->prepare($ratingQuery);
        
        // BIND the author's user ID
        $ratingStatement->bindParam(':authorUserID', $authorUserID, PDO::PARAM_INT);
        
        // EXECUTE the query
        $ratingStatement->execute();
        
        // FETCH the result
        $ratingResult = $ratingStatement->fetch(PDO::FETCH_ASSOC);
        
        // If there are ratings, store them
        if ($ratingResult && $ratingResult['total_ratings'] > 0) {
            // Round the average to 1 decimal place
            $averageRating = round($ratingResult['average_rating'], 1);
            $totalRatings = intval($ratingResult['total_ratings']);
        }
    }

} catch (PDOException $error) {
    // If any database error occurs, save the error message
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
            // Check if there is an error message to display
            if (!empty($errorMessage)) {
                // Display the error in a red alert box
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                // Use htmlspecialchars to prevent XSS attacks
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- =============================================================================
             SECTION 3: Main Listing Content (only if listing exists)
             ============================================================================= -->
        <?php
            // Only show listing details if we successfully fetched it from database
            if ($listing) {
                
                // =============================================================================
                // STEP A: Extract and sanitize all listing data
                // =============================================================================
                // WHY: htmlspecialchars() prevents XSS attacks by converting < > to &lt; &gt;
                //      This stops hackers from injecting malicious HTML/JavaScript
                
                $safeTitle = htmlspecialchars($listing['title']);
                $safeDescription = htmlspecialchars($listing['description']);
                $safeLocation = htmlspecialchars($listing['location_approx']);
                $safeStartDate = htmlspecialchars($listing['start_date']);
                $safeEndDate = htmlspecialchars($listing['end_date']);
                $safeListingType = htmlspecialchars($listing['listing_type']);
                $safeStatus = htmlspecialchars($listing['status']);
                $safeUsername = htmlspecialchars($listing['username']);
                $safeExperience = htmlspecialchars($listing['experience']);
                
                // Handle price_range (might be NULL in database)
                if (!empty($listing['price_range'])) {
                    $priceRange = htmlspecialchars($listing['price_range']);
                } else {
                    $priceRange = 'Not specified';
                }
                
                // =============================================================================
                // STEP B: Build the profile photo path
                // =============================================================================
                // WHY: We need to construct the correct path from database to browser
                //      Database stores: "uploads/profiles/user123.jpg"
                //      But this file is in listings/ folder, so we need: "../uploads/profiles/user123.jpg"
                
                if (!empty($listing['profile_photo_path'])) {
                    // Get the path from database
                    $profilePhotoPath = $listing['profile_photo_path'];
                    
                    // Add ../ to go up one folder level (from listings/ to root)
                    $profilePhotoPath = '../' . $profilePhotoPath;
                    
                    // Sanitize to prevent XSS
                    $profilePhotoPath = htmlspecialchars($profilePhotoPath);
                } else {
                    // No profile photo in database
                    $profilePhotoPath = null;
                }
                
                // =============================================================================
                // STEP C: Build the listing photo path
                // =============================================================================
                // WHY: Same reason as profile photo - we need correct relative path
                
                if (!empty($listing['listing_photo_path'])) {
                    // Get the path from database
                    $listingPhotoPath = $listing['listing_photo_path'];
                    
                    // Add ../ to go up one folder level
                    $listingPhotoPath = '../' . $listingPhotoPath;
                    
                    // Sanitize to prevent XSS
                    $listingPhotoPath = htmlspecialchars($listingPhotoPath);
                } else {
                    // No listing photo
                    $listingPhotoPath = null;
                }
                
                // =============================================================================
                // STEP D: Build the care sheet PDF path
                // =============================================================================
                // WHY: If user uploaded a care sheet PDF, we need path for download button
                
                if (!empty($listing['care_sheet_path'])) {
                    // Get the path from database
                    $careSheetPath = $listing['care_sheet_path'];
                    
                    // Add ../ to go up one folder level
                    $careSheetPath = '../' . $careSheetPath;
                    
                    // Sanitize to prevent XSS
                    $careSheetPath = htmlspecialchars($careSheetPath);
                } else {
                    // No care sheet uploaded
                    $careSheetPath = null;
                }
                
                // =============================================================================
                // STEP E: Determine badge colors based on listing type and status
                // =============================================================================
                // WHY: We show colored badges to indicate if listing is "offer" or "need"
                //      and if it's "active", "completed", or "inactive"
                
                // Set badge color for listing type
                if ($safeListingType === 'offer') {
                    $badgeColor = 'success';      // Green badge
                    $badgeText = 'Offering';
                } else {
                    $badgeColor = 'warning';      // Yellow badge
                    $badgeText = 'Looking For';
                }
                
                // Set badge color for status
                if ($safeStatus === 'active') {
                    $statusColor = 'info';        // Blue badge
                } else if ($safeStatus === 'completed') {
                    $statusColor = 'success';     // Green badge
                } else {
                    $statusColor = 'secondary';   // Gray badge
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
                                // Display the photo at top of card
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
                                    // Display circular profile photo
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
                                // Only display rating if the user has been rated at least once
                                if ($totalRatings > 0) {
                                    
                                    // STEP 1: Build the star display
                                    // WHY: We want to show stars visually (⭐⭐⭐) instead of just a number
                                    
                                    // Get the full stars (integer part of average)
                                    // Example: If average is 4.3, fullStars = 4
                                    $fullStars = floor($averageRating);
                                    
                                    // Check if there is a half star
                                    // WHY: If average is 4.3, we show 4 full stars and 0 half stars
                                    //      If average is 4.6, we show 4 full stars and 1 half star
                                    $hasHalfStar = ($averageRating - $fullStars) >= 0.5;
                                    
                                    // Start building the star string
                                    $starsDisplay = '';
                                    
                                    // Add full stars
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
                            
                            <!-- Contact Button -->
                            <a href="../users/messages.php" class="btn btn-success">
                                Contact Seller
                            </a>
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

                        <!-- Plants Grid -->
                        <!-- col-12 = 1 plant per row on mobile, col-md-6 = 2 plants per row on desktop -->
                        <div class="row">
                            <?php
                                // Loop through each plant and display it
                                foreach ($plants as $plant) {
                                    
                                    // Sanitize plant data to prevent XSS attacks
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
                            
                            // Check if user has already favorited this listing
                            if ($isFavorited) {
                                // User HAS favorited it - show "Remove from Favorites" button
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
                                // User has NOT favorited it - show "Add to Favorites" button
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
                            // User is NOT logged in - show link to login page
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