<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize variables to store listing and plant data
$listing = null;
$plants = [];
$errorMessage = '';

// Initialize variable to track if current user has favorited this listing
// This will be true if a favorite entry exists in the database
$isFavorited = false;

// Check if the 'id' parameter exists in the URL and is a valid number
// We use isset() to check if the parameter exists, and is_numeric() to ensure it's a number
// This prevents SQL injection and invalid queries
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // If the ID is missing or invalid, redirect the user back to the listings page
    // header() must be called before any HTML output
    header('Location: listings.php');
    exit();
}

// Store the listing ID and convert it to an integer for extra safety
// intval() converts the string to an integer, removing any potential malicious characters
$listingID = intval($_GET['id']);

// Use a try-catch block to safely handle database connection errors
try {
    // Query 1: Fetch the specific listing with author information
    // We use a LEFT JOIN with users to get the author's username and profile photo
    // LEFT JOIN ensures we get the listing even if the user data is missing (though it shouldn't be)
    $listingQuery = "
        SELECT 
            listings.*,
            users.username,
            users.profile_photo_path
        FROM listings
        LEFT JOIN users ON listings.user_id = users.user_id
        WHERE listings.listing_id = :listingID
    ";

    // Prepare the statement to prevent SQL injection attacks
    // Prepared statements separate the SQL code from the data
    $listingStatement = $connection->prepare($listingQuery);

    // Bind the listing ID parameter to prevent SQL injection
    // :listingID is a placeholder that will be safely replaced with the actual ID
    $listingStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

    // Execute the prepared statement
    $listingStatement->execute();

    // Fetch the result as an associative array
    // fetch() returns only one row (or null if not found)
    $listing = $listingStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the listing was found
    // If $listing is false/null, the listing does not exist in the database
    if (!$listing) {
        // Set an error message that will be displayed to the user
        $errorMessage = "Listing not found. Please check the ID and try again.";
    } else {
        // Query 2: Fetch all plants associated with this listing
        // The plants table contains information about each plant in this listing
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

        // Prepare the plants query
        $plantsStatement = $connection->prepare($plantsQuery);

        // Bind the listing ID parameter
        $plantsStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

        // Execute the query
        $plantsStatement->execute();

        // Fetch all plants as an array of associative arrays
        // fetchAll() returns all rows, or an empty array if none are found
        $plants = $plantsStatement->fetchAll(PDO::FETCH_ASSOC);

        // Query 3: Check if the current user has favorited this listing
        // We only run this query if the user is logged in
        if ($isLoggedIn) {
            // Query to check if a favorite entry exists for this user and listing
            // We SELECT the favorite_id to see if a row exists
            $favoriteCheckQuery = "
                SELECT favorite_id
                FROM favorites
                WHERE user_id = :userID
                AND listing_id = :listingID
            ";

            // Prepare the favorite check query
            $favoriteCheckStatement = $connection->prepare($favoriteCheckQuery);

            // Bind both parameters to prevent SQL injection
            $favoriteCheckStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
            $favoriteCheckStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // Execute the query
            $favoriteCheckStatement->execute();

            // If a row is found, the user has favorited this listing
            // fetch() returns false if no row exists
            if ($favoriteCheckStatement->fetch()) {
                $isFavorited = true;
            }
        }
    }

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
    <title>Listing Details</title>
</head>
<body>
    <div class="container mt-4">
        <!-- Back to Listings Button -->
        <!-- This button allows users to easily navigate back to the listings page -->
        <!-- col-12 = full width on mobile, col-md-8 = narrower on desktop for better readability -->
        <div class="row mb-3">
            <div class="col-12 col-md-8 offset-md-2">
                <a href="listings.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Listings
                </a>
            </div>
        </div>

        <!-- Check if there was a database error and display it -->
        <?php
            if (!empty($errorMessage)) {
                // Display an alert message if there was any error
                // alert-danger = red background for errors
                // We use htmlspecialchars() to prevent XSS attacks when displaying the error
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Main Listing Details Section (Only display if listing exists) -->
        <?php
            if ($listing) {
                // Extract and sanitize all listing data to prevent XSS attacks
                $safeTitle = htmlspecialchars($listing['title']);
                $safeDescription = htmlspecialchars($listing['description']);
                $safeLocation = htmlspecialchars($listing['location_approx']);
                $safeStartDate = htmlspecialchars($listing['start_date']);
                $safeEndDate = htmlspecialchars($listing['end_date']);
                $safeListingType = htmlspecialchars($listing['listing_type']);
                $safeStatus = htmlspecialchars($listing['status']);
                $safeUsername = htmlspecialchars($listing['username']);
                $safeExperience = htmlspecialchars($listing['experience']);
                $priceRange = htmlspecialchars($listing['price_range'] ?? 'Not specified');
                
                // Get the user's profile photo path and sanitize it
                // WHY: We need to construct the correct path for the browser.
                //      Since this file is in listings/ folder, we go UP one level (../) to reach uploads/
                if (!empty($listing['profile_photo_path'])) {
                    $rawProfilePath = $listing['profile_photo_path'];
                    
                    // Remove any leading slashes to normalize the path
                    // WHY: This prevents double slashes like //uploads/
                    $rawProfilePath = ltrim($rawProfilePath, '/');
                    
                    // Check if the path already contains the uploads folder structure
                    // WHY: If it's already there, we don't want to add it twice
                    if (strpos($rawProfilePath, 'uploads/profiles/') !== 0) {
                        // Path does not start with 'uploads/profiles/', so add it
                        // WHY: The database might only store the filename (e.g., 'user123.jpg')
                        $rawProfilePath = 'uploads/profiles/' . $rawProfilePath;
                    }
                    
                    // Build path relative to this file's location
                    // WHY: We're in listings/ folder, so ../ goes up to project root, then into uploads
                    $profilePhotoPath = '../' . $rawProfilePath;
                    
                    // Sanitize the final path to prevent XSS attacks
                    $profilePhotoPath = htmlspecialchars($profilePhotoPath);
                } else {
                    // No profile photo path in database, set to null
                    $profilePhotoPath = null;
                }

                // Get the listing photo path and sanitize it
                // WHY: We need to construct the correct path for the browser.
                //      Since this file is in listings/ folder, we go UP one level (../) to reach uploads/
                if (!empty($listing['listing_photo_path'])) {
                    $rawPath = $listing['listing_photo_path'];
                    
                    // Remove any leading slashes to normalize the path
                    // WHY: This prevents double slashes like //uploads/
                    $rawPath = ltrim($rawPath, '/');
                    
                    // Check if the path already contains the uploads folder structure
                    // WHY: If it's already there, we don't want to add it twice
                    if (strpos($rawPath, 'uploads/listings/') !== 0) {
                        // Path does not start with 'uploads/listings/', so add it
                        // WHY: The database might only store the filename (e.g., 'plant1.jpg')
                        $rawPath = 'uploads/listings/' . $rawPath;
                    }
                    
                    // Build path relative to this file's location
                    // WHY: We're in listings/ folder, so ../ goes up to project root, then into uploads
                    $listingPhotoPath = '../' . $rawPath;
                    
                    // Sanitize the final path to prevent XSS attacks
                    $listingPhotoPath = htmlspecialchars($listingPhotoPath);
                } else {
                    // No photo path in database, set to null to show placeholder
                    $listingPhotoPath = null;
                }

                // Get the care sheet PDF path and sanitize it
                // This is the PDF file that contains detailed plant care instructions
                // If the user uploaded a care sheet when creating the listing, this path will not be empty
                if (!empty($listing['care_sheet_path'])) {
                    $rawCareSheetPath = $listing['care_sheet_path'];
                    
                    // Remove any leading slashes to normalize the path
                    $rawCareSheetPath = ltrim($rawCareSheetPath, '/');
                    
                    // Check if the path already contains the uploads folder structure
                    if (strpos($rawCareSheetPath, 'uploads/caresheets/') !== 0) {
                        // Path does not start with 'uploads/caresheets/', so add it
                        $rawCareSheetPath = 'uploads/caresheets/' . $rawCareSheetPath;
                    }
                    
                    // Build path relative to this file's location
                    // WHY: We're in listings/ folder, so ../ goes up to project root, then into uploads
                    $careSheetPath = '../' . $rawCareSheetPath;
                    
                    // Sanitize the final path to prevent XSS attacks
                    $careSheetPath = htmlspecialchars($careSheetPath);
                } else {
                    $careSheetPath = null;
                }

                // Determine the badge color based on listing type
                if ($safeListingType === 'offer') {
                    $badgeColor = 'success';
                    $badgeText = 'Offering';
                } else {
                    $badgeColor = 'warning';
                    $badgeText = 'Looking For';
                }

                // Determine the status badge color
                if ($safeStatus === 'active') {
                    $statusColor = 'info';
                } else if ($safeStatus === 'completed') {
                    $statusColor = 'success';
                } else {
                    $statusColor = 'secondary';
                }
        ?>

            <!-- Main Content Card -->
            <!-- col-12 = full width on mobile, col-md-8 = 2/3 width on desktop for better reading -->
            <!-- offset-md-2 = centers the content on desktop by adding left margin -->
            <div class="row mb-4">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <!-- Card Header with badges -->
                        <div class="card-header bg-light d-flex gap-2 flex-wrap">
                            <!-- Type Badge (Offer or Need) -->
                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                <?php echo $badgeText; ?>
                            </span>
                            <!-- Status Badge (Active, Inactive, Completed) -->
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo ucfirst($safeStatus); ?>
                            </span>
                        </div>

                        <!-- Listing Photo Section -->
                        <!-- Display the plant photo if it exists -->
                        <!-- This photo is uploaded when creating the listing -->
                        <?php
                            if ($listingPhotoPath) {
                                // Listing has a photo, display it at the top of the card
                                // We use inline styles for responsive sizing
                                // width: 100% makes the image fill its container on all screen sizes
                                // max-height: 400px prevents the image from being too tall
                                // object-fit: cover crops the image nicely to fill the space
                                echo "<img src=\"" . $listingPhotoPath . "\" alt=\"" . $safeTitle . "\" class=\"card-img-top\" style=\"width: 100%; max-height: 400px; object-fit: cover;\">";
                            }
                        ?>


                        <!-- Card Body with main listing information -->
                        <div class="card-body">
                            <!-- Listing Title -->
                            <h2 class="card-title mb-3">
                                <?php echo $safeTitle; ?>
                            </h2>

                            <!-- Listing Description -->
                            <div class="mb-4">
                                <h5 class="text-secondary">Description</h5>
                                <p class="card-text">
                                    <?php echo nl2br($safeDescription); ?>
                                </p>
                            </div>

                            <!-- Care Sheet Download Section (NEW) -->
                            <!-- This section appears only if a care sheet PDF was uploaded -->
                            <!-- It provides a download button for the PDF -->
                            <?php
                                if ($careSheetPath) {
                                    // Care sheet exists, display download button
                                    // We use a Bootstrap alert box to make it stand out
                            ?>
                                <div class="mb-4">
                                    <!-- Alert box with info color (blue) to highlight the care sheet -->
                                    <!-- d-flex = use flexbox layout -->
                                    <!-- justify-content-between = space out the text and button -->
                                    <!-- align-items-center = vertically center the content -->
                                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                                        <div>
                                            <h6 class="mb-0">📄 Care Sheet Available</h6>
                                            <small class="text-muted">Download the detailed plant care instructions (PDF)</small>
                                        </div>
                                        <!-- Download button -->
                                        <!-- download attribute forces browser to download instead of opening -->
                                        <!-- btn-sm = smaller button size -->
                                        <!-- The href points to the PDF file path stored in the database -->
                                        <a href="<?php echo $careSheetPath; ?>" download class="btn btn-primary btn-sm">
                                            Download PDF
                                        </a>
                                    </div>
                                </div>
                            <?php
                                }
                            ?>

                            <!-- Key Details Section -->
                            <!-- This section uses a responsive grid for mobile-first design -->
                            <div class="row g-3 mb-4">
                                <!-- Location Detail -->
                                <div class="col-12 col-md-6">
                                    <div class="border-start border-3 border-primary ps-3">
                                        <small class="text-muted">Location</small>
                                        <p class="mb-0"><strong><?php echo $safeLocation; ?></strong></p>
                                    </div>
                                </div>

                                <!-- Dates Detail -->
                                <div class="col-12 col-md-6">
                                    <div class="border-start border-3 border-success ps-3">
                                        <small class="text-muted">Availability</small>
                                        <p class="mb-0">
                                            <strong><?php echo $safeStartDate; ?></strong> to <strong><?php echo $safeEndDate; ?></strong>
                                        </p>
                                    </div>
                                </div>

                                <!-- Experience Detail -->
                                <div class="col-12 col-md-6">
                                    <div class="border-start border-3 border-warning ps-3">
                                        <small class="text-muted">Experience Required</small>
                                        <p class="mb-0"><strong><?php echo $safeExperience; ?></strong></p>
                                    </div>
                                </div>

                                <!-- Price Range Detail -->
                                <div class="col-12 col-md-6">
                                    <div class="border-start border-3 border-danger ps-3">
                                        <small class="text-muted">Price Range</small>
                                        <p class="mb-0"><strong><?php echo $priceRange; ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Author Section -->
            <!-- This section displays information about who posted this listing -->
            <div class="row mb-4">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Posted By</h5>
                            
                            <!-- Use flexbox to align profile photo and username horizontally on desktop -->
                            <!-- flex-column = stack vertically on mobile, flex-md-row = side by side on desktop -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                                <!-- Profile Photo (if it exists) -->
                                <?php
                                    if ($profilePhotoPath) {
                                        // Display the user's profile photo if available
                                        echo "<img src=\"" . $profilePhotoPath . "\" alt=\"" . $safeUsername . "'s profile\" class=\"rounded-circle\" style=\"width: 60px; height: 60px; object-fit: cover;\">";
                                    }
                                ?>

                                <!-- Username and Contact Info -->
                                <div>
                                    <h6 class="mb-2">
                                        <strong><?php echo $safeUsername; ?></strong>
                                    </h6>
                                    <!-- Contact Button (in the future, this can link to messaging) -->
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        Contact Seller
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plants Section -->
            <!-- Display all plants associated with this listing -->
            <?php
                if (count($plants) > 0) {
                    // Only display this section if there are plants to show
            ?>
                <div class="row mb-4">
                    <div class="col-12 col-md-8 offset-md-2">
                        <h4 class="mb-3">Plants Included</h4>

                        <!-- Use Bootstrap's row-cols for responsive grid -->
                        <!-- col-12 = 1 plant per row on mobile -->
                        <!-- col-md-6 = 2 plants per row on desktop -->
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php
                                // Loop through each plant and display it as a card
                                foreach ($plants as $plant) {
                                    // Extract and sanitize plant data
                                    $safePlantType = htmlspecialchars($plant['plant_type']);
                                    $safeWateringNeeds = htmlspecialchars($plant['watering_needs']);
                                    $safeLightNeeds = htmlspecialchars($plant['light_needs']);
                            ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <!-- Card Header with plant name -->
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <?php echo $safePlantType; ?>
                                            </h6>
                                        </div>

                                        <!-- Card Body with care requirements -->
                                        <div class="card-body">
                                            <!-- Watering Needs -->
                                            <div class="mb-3">
                                                <small class="text-muted d-block mb-1">
                                                    <strong>💧 Watering Needs</strong>
                                                </small>
                                                <p class="small mb-0">
                                                    <?php echo $safeWateringNeeds; ?>
                                                </p>
                                            </div>

                                            <!-- Light Needs -->
                                            <div>
                                                <small class="text-muted d-block mb-1">
                                                    <strong>☀️ Light Needs</strong>
                                                </small>
                                                <p class="small mb-0">
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

            <!-- Action Buttons Section -->
            <!-- These buttons allow users to interact with the listing -->
            <div class="row mb-5">
                <div class="col-12 col-md-8 offset-md-2">
                    <!-- d-grid = makes buttons full width on mobile -->
                    <!-- gap-2 = adds spacing between buttons -->
                    <div class="d-grid gap-2 d-md-flex gap-md-2">
                        <!-- Apply for this listing button (full width on mobile, auto width on desktop) -->
                        <!-- This button links to apply.php with the listing ID as a parameter -->
                        <a href="apply.php?id=<?php echo $listingID; ?>" class="btn btn-success btn-lg">
                            Apply for this Listing
                        </a>

                        <!-- Favorite/Unfavorite Button -->
                        <!-- This button changes based on whether the user has favorited this listing -->
                        <!-- We only show this button if the user is logged in -->
                        <?php
                            if ($isLoggedIn) {
                                // User is logged in, show favorite/unfavorite button

                                if ($isFavorited) {
                                    // User has already favorited this listing
                                    // Show an "Unfavorite" button that submits a form to listing-unfavorite.php
                                    // We use a form with POST method for security (prevents accidental unfavoriting via GET)
                        ?>
                                    <form method="POST" action="listing-unfavorite.php" class="d-grid d-md-inline">
                                        <!-- Hidden input to pass the listing ID to listing-unfavorite.php -->
                                        <!-- This tells the script which listing to unfavorite -->
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        
                                        <!-- Hidden input to remember where to redirect after unfavoriting -->
                                        <!-- After unfavoriting, we want to come back to this listing-details page -->
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
                                        <!-- Unfavorite button (red heart = already favorited) -->
                                        <!-- btn-outline-danger = red outline to indicate removal action -->
                                        <button type="submit" class="btn btn-outline-danger btn-lg">
                                            ❤️ Remove from Favorites
                                        </button>
                                    </form>
                        <?php
                                } else {
                                    // User has not favorited this listing yet
                                    // Show an "Add to Favorites" button that submits a form to listing-favorite.php
                        ?>
                                    <form method="POST" action="listing-favorite.php" class="d-grid d-md-inline">
                                        <!-- Hidden input to pass the listing ID to listing-favorite.php -->
                                        <!-- This tells the script which listing to favorite -->
                                        <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                        
                                        <!-- Hidden input to remember where to redirect after favoriting -->
                                        <!-- After favoriting, we want to come back to this listing-details page -->
                                        <input type="hidden" name="redirect_url" value="listing-details.php?id=<?php echo $listingID; ?>">
                                        
                                        <!-- Favorite button (empty heart = not favorited yet) -->
                                        <!-- btn-outline-secondary = gray outline (secondary action) -->
                                        <button type="submit" class="btn btn-outline-secondary btn-lg">
                                            ♡ Add to Favorites
                                        </button>
                                    </form>
                        <?php
                                }
                            } else {
                                // User is not logged in
                                // Show a link to login page with message to sign in first
                        ?>
                                <a href="login.php" class="btn btn-outline-secondary btn-lg">
                                    ♡ Login to Favorite
                                </a>
                        <?php
                            }
                        ?>
                    </div>
                </div>
            </div>

        <?php
            }
        ?>
    </div>
</body>
</html>