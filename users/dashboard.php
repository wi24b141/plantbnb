<?php
// ============================================
// STEP 1: INCLUDE REQUIRED FILES
// ============================================

// - header.php: Contains the Bootstrap CSS link and starts the session
// - user-auth.php: Checks if user is logged in (redirects if not)
// - db.php: Contains the database connection variable ($connection)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 2: GET THE LOGGED-IN USER'S ID
// ============================================

// To know which user is viewing their dashboard
// intval() converts the session value to an integer for security
// This prevents SQL injection if someone tampers with the session
$userID = intval($_SESSION['user_id']);

// ============================================
// STEP 3: INITIALIZE VARIABLES
// ============================================

// To set all variables to empty values BEFORE using them
// This prevents "undefined variable" errors in PHP
$user = null;
$errorMessage = '';

// ============================================
// STEP 4: FETCH USER DATA FROM DATABASE
// ============================================

// To get the user's profile information to display on the dashboard
// use a try-catch block to handle database errors safely

try {
    // Write the SQL query to get the user's profile information
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            profile_photo_path,
            is_verified,
            bio,
            created_at
        FROM users
        WHERE user_id = :userID
    ";

    // Prepare the SQL statement
    $userStatement = $connection->prepare($userQuery);

    // Bind the user ID to the placeholder
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the query
    $userStatement->execute();

    // Fetch the result as an associative array
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found in the database
    if (!$user) {
        // User not found, destroy the session
        session_destroy();
        // Redirect to the login page
        header('Location: login.php');
        exit();
    }

} catch (PDOException $error) {
    // If a database error occurs, catch it 
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 5: FETCH RATING SUMMARY FOR THIS USER
// ============================================
// WHY: We show the user's average rating and count on their profile
$avgRating = null;
$ratingCount = 0;
try {
    $ratingQuery = "
        SELECT AVG(rating) AS avg_rating, COUNT(*) AS rating_count
        FROM ratings
        WHERE rated_user_id = :userID
    ";
    $ratingStmt = $connection->prepare($ratingQuery);
    $ratingStmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $ratingStmt->execute();
    $ratingRow = $ratingStmt->fetch(PDO::FETCH_ASSOC);
    if ($ratingRow) {
        $avgRating = $ratingRow['avg_rating'] !== null ? round((float)$ratingRow['avg_rating'], 1) : null;
        $ratingCount = intval($ratingRow['rating_count']);
    }
} catch (PDOException $error) {
    // silently ignore rating errors; profile still shows
}

?>

<!-- ============================================================ -->
<!-- HTML SECTION STARTS HERE                                     -->
<!-- ============================================================ -->


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding for proper text display -->
    <meta charset="UTF-8">
    
    <!-- Viewport meta tag for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title shown in browser tab -->
    <title>Dashboard - PlantBnB</title>
</head>
<body>
    <!-- Main container - centers content and adds padding -->
    <!-- mt-4 = margin-top (spacing from top of page) -->
    <div class="container mt-4">
        
        <!-- ============================================
             ERROR MESSAGE SECTION
             ============================================ -->
        <!-- Display red error message if database error occurred -->
        <?php
            if (!empty($errorMessage)) {
                // Display a red error alert box
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                // Output the error message
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- ============================================
             WELCOME SECTION
             ============================================ -->
        <!-- Display a personalized welcome message to the logged-in user -->
        <!-- only shows the dashboard if $user has data -->
        <?php
            if ($user) {
                // Sanitize the username before displaying it
                $safeUsername = htmlspecialchars($user['username']);
        ?>
            <!-- Row for welcome message -->
            <!-- mb-4 = margin-bottom for spacing -->
            <div class="row mb-4">
                <!-- Column that spans full width -->
                <div class="col-12">
                    <!-- Welcome heading with user's name -->
                    <!-- mb-0 = no bottom margin (removes extra space) -->
                    <h1 class="mb-0">Welcome back, <?php echo $safeUsername; ?>! 🌿</h1>
                    <!-- Subtitle text -->
                    <!-- text-muted = gray color (less prominent) -->
                    <p class="text-muted">Manage your plant swaps and grow your community</p>
                </div>
            </div>

            <!-- ============================================
                 PROFILE CARD SECTION
                 ============================================ -->
            <!-- This card displays the user's profile information -->
            <!-- mb-4 = margin-bottom for spacing -->
            <div class="row mb-4">
                <!-- Column for profile card -->
                <!-- col-12 = full width on mobile (phone screens) -->
                <!-- col-md-8 = 2/3 width on desktop (medium screens and up) -->
                <!-- offset-md-2 = push 2 columns from left on desktop (centers the card) -->
                <div class="col-12 col-md-8 offset-md-2">
                    <!-- shadow-sm = small shadow effect -->
                    <!-- h-100 = height 100% (makes cards in same row equal height) -->
                    <div class="card shadow-sm h-100">
                        <!-- Card Header (top colored section) -->
                        <!-- bg-success = green background -->
                        <!-- text-white = white text -->
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">My Profile</h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <!-- Container for profile photo and user details -->
                            <!-- d-flex = use flexbox for flexible layout -->
                            <!-- flex-column = stack items vertically on mobile -->
                            <!-- flex-md-row = stack horizontally on desktop -->
                            <!-- align-items-md-center = vertically center on desktop -->
                            <!-- gap-3 = spacing between items -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                                <!-- Profile Photo Display -->
                                <?php
                                    // Get the profile photo path from the user array
                                    if (isset($user['profile_photo_path'])) {
                                        $profilePhoto = $user['profile_photo_path'];
                                    } else {
                                        // Photo path is NULL in database, use empty string
                                        $profilePhoto = '';
                                    }
                                    // Check if the user has a profile photo
                                    if (!empty($profilePhoto)) {
                                        // User HAS a photo, so display it
                                        // Build the correct path for the browser
                                        // WHY: We're in users/ folder, photo is in uploads/profiles/
                                        // So we need ../ to go up one level to project root
                                        // Example: ../uploads/profiles/photo123.jpg
                                        $profilePhotoPath = '../' . htmlspecialchars($profilePhoto);
                                        
                                        // Display the user's uploaded profile photo
                                        // rounded-circle = circular image
                                        // width/height = 80px = size of the circle
                                        // object-fit: cover = image fills circle without distortion
                                        echo "<img src=\"" . $profilePhotoPath . "\" alt=\"" . $safeUsername . "'s profile photo\" class=\"rounded-circle\" style=\"width: 80px; height: 80px; object-fit: cover;\">";
                                        
                                    } else {
                                        // User DOES NOT have a photo, show placeholder
                                        // via.placeholder.com generates a simple placeholder image
                                        echo "<img src=\"https://via.placeholder.com/80?text=No+Photo\" alt=\"Default profile placeholder\" class=\"rounded-circle\" style=\"width: 80px; height: 80px; object-fit: cover;\">";
                                    }
                                ?>

                                <!-- Container for username, email, and badge -->
                                <div>
                                    <!-- Display username -->
                                    <h5 class="mb-2">
                                        <?php echo $safeUsername; ?>
                                        <?php
                                            if ($avgRating !== null) {
                                                // Show star and average with count
                                                echo ' <span class="badge bg-warning text-dark ms-2">⭐ ' . htmlspecialchars($avgRating) . '</span>';
                                                if ($ratingCount > 0) {
                                                    echo ' <small class="text-muted">(' . $ratingCount . ')</small>';
                                                }
                                            } else {
                                                echo ' <small class="text-muted ms-2">(no ratings yet)</small>';
                                            }
                                        ?>
                                    </h5>
                                    
                                    <!-- Display email address -->
                                    <!-- text-muted = gray color -->
                                    <!-- small = smaller text size -->
                                    <p class="mb-2 text-muted">
                                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    </p>

                                    <!-- Verification Badge -->
                                    <?php
                                        // Check if user is verified
                                        // is_verified column is 1 (true) or 0 (false) in database
                                        if ($user['is_verified'] == 1) {
                                            // User IS verified - show green badge with checkmark
                                            echo "<span class=\"badge bg-success\">✓ Verified</span>";
                                        } else {
                                            // User is NOT verified - show gray badge
                                            echo "<span class=\"badge bg-secondary\">○ Unverified</span>";
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- User Bio Section -->
                            <?php
                                // Get the bio from the user array
                                if (isset($user['bio'])) {
                                    // Bio exists, use it
                                    $bio = $user['bio'];
                                } else {
                                    // Bio is NULL in database, use empty string
                                    $bio = '';
                                }
                                
                                // Sanitize the bio to prevent XSS attacks
                                $safeBio = htmlspecialchars($bio);

                                // Check if bio has content
                                if (!empty($safeBio)) {
                                    // User HAS a bio, display it
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><strong>Bio</strong></small>";
                                    //nl2br() converts newlines (\n) to <br> tags for display
                                    echo "  <p class=\"small mb-0\">" . nl2br($safeBio) . "</p>";
                                    echo "</div>";
                                } else {
                                    // User DOES NOT have a bio yet, show placeholder message
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><em>No bio added yet. <a href=\"profile-edit.php\">Add one now</a></em></small>";
                                    echo "</div>";
                                }
                            ?>

                            <!-- Member Since -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <strong>Member since:</strong> 
                                    <?php echo htmlspecialchars($user['created_at']); ?>
                                </small>
                            </div>

                            <!-- Edit Profile Button -->
                            <!-- d-grid = full width button on mobile -->
                            <div class="d-grid">
                                <a href="profile-edit.php" class="btn btn-outline-primary">
                                    Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================
                 QUICK ACTIONS SECTION
                 ============================================ -->
            <!-- This section provides navigation buttons to key features -->
            <div class="row mb-4">
                <!-- Section Header -->
                <div class="col-12 mb-3">
                    <h3>Quick Actions</h3>
                </div>

                <!-- Browse Listings Card -->
                <!-- col-12 = full width on mobile (stacked vertically) -->
                <!-- col-md-4 = 1/3 width on desktop (3 cards per row, evenly distributed) -->
                <div class="col-12 col-md-4">
                    <!-- Link to listings page -->
                    <!-- text-decoration-none removes the underline from link -->
                    <a href="/plantbnb/listings/listings.php" class="text-decoration-none">
                        <!-- Bootstrap card component -->
                        <div class="card shadow-sm h-100 text-center p-3">
                            <!-- Icon emoji -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                📋
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">Browse Listings</h5>
                            <!-- Description text -->
                            <p class="card-text small text-muted mb-0">
                                Browse all plant listings
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Create Listing Card -->
                <div class="col-12 col-md-4">
                            <!-- Link to listing creator page -->
                            <a href="/plantbnb/listings/listing-creator.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <!-- Icon emoji for creating new listing -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ➕
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">Create Listing</h5>
                            <!-- Description text -->
                            <p class="card-text small text-muted mb-0">
                                Post a new plant offer or request
                            </p>
                        </div>
                    </a>
                </div>

                <!-- My Listings Card -->
                <div class="col-12 col-md-4">
                    <!-- Link to user's listings page -->
                    <a href="/plantbnb/listings/my-listings.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <div class="mb-2" style="font-size: 2.5rem;">
                                🗂️
                            </div>
                            <h5 class="card-title">My Listings</h5>
                            <p class="card-text small text-muted mb-0">View and edit your listings</p>
                        </div>
                    </a>
                </div>

                <!-- User Verification Card -->
                <div class="col-12 col-md-4">
                    <!-- Link to verification page -->
                    <a href="/plantbnb/users/verification.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <!-- Icon emoji for verification -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ✅
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">User Verification</h5>
                            <!-- Description text -->
                            <p class="card-text small text-muted mb-0">
                                Upload ID documents for verification
                            </p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ============================================
                 LOGOUT SECTION
                 ============================================ -->
            <div class="row mb-5">
                <!-- Column spans full width -->
                <div class="col-12">
                    <!-- Container for logout button -->
                    <!-- d-grid makes button full width on mobile (touch-friendly) -->
                    <!-- gap-2 adds internal spacing -->
                    <div class="d-grid gap-2">
                        <!-- Logout link styled as button -->
                        <!-- btn-danger = red color (indicates destructive action) -->
                        <!-- btn-lg = large button size (easier to click) -->
                        <a href="/plantbnb/users/logout.php" class="btn btn-danger btn-lg">
                            Logout
                        </a>
                    </div>
                    <!-- Helper text explaining what logout does -->
                    <!-- d-block = display as block (own line) -->
                    <!-- text-center = center text horizontally -->
                    <!-- mt-2 = top margin for spacing -->
                    <small class="text-muted d-block text-center mt-2">
                        You will be logged out and returned to the login page
                    </small>
                </div>
            </div>

        <?php
            }
        ?>
    </div>
</body>
</html>