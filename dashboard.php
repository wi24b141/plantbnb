<?php
// ============================================
// DASHBOARD PAGE - PHP LOGIC (TOP)
// ============================================

// Start the session to access $_SESSION variables
// session_start() must be called before any HTML output
// It either starts a new session or resumes an existing one
session_start();

// Include the database connection
require_once 'db.php';

// ============================================
// SECURITY CHECK: VERIFY USER IS LOGGED IN
// ============================================

// Check if user_id exists in the session
// If the user is not logged in, $_SESSION['user_id'] will not be set
// We redirect them immediately to the login page for security
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    // header() must be called before any HTML output
    header('Location: login.php');
    // exit() stops the script so nothing else runs after the redirect
    exit();
}

// Store the user_id from the session for use in queries
// We use intval() to ensure it's an integer for extra safety
$userID = intval($_SESSION['user_id']);

// ============================================
// FETCH USER DATA FROM DATABASE
// ============================================

// Initialize the user variable to null
// We'll fill this with actual data if the query succeeds
$user = null;
$errorMessage = '';

// Use a try-catch block to safely handle database errors
try {
    // Query to fetch the user's full profile information
    // We select all the important user details we need to display on the dashboard
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

    // Prepare the statement to prevent SQL injection attacks
    // Prepared statements separate the SQL code from the data
    $userStatement = $connection->prepare($userQuery);

    // Bind the user ID parameter to prevent SQL injection
    // :userID is a placeholder that will be safely replaced with the actual ID
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the prepared statement
    $userStatement->execute();

    // Fetch the result as an associative array
    // fetch() returns one row or null if not found
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found in the database
    // If the user is not found, something went wrong (user was deleted, session is corrupted, etc.)
    if (!$user) {
        // Destroy the session because the user data is no longer valid
        // This prevents the session from persisting if the user no longer exists
        session_destroy();

        // Redirect to the login page
        header('Location: login.php');
        exit();
    }

} catch (PDOException $error) {
    // If a database error occurs, catch it and display a friendly message
    // In production, you should log this instead of displaying it
    $errorMessage = "Database error: " . $error->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PlantBnB</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         DASHBOARD PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Check if there was a database error and display it -->
        <?php
            if (!empty($errorMessage)) {
                // Display an alert message if there was any error
                // alert-danger = red background for errors
                // We use htmlspecialchars() to prevent XSS attacks when displaying the error
                echo "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "</div>";
            }
        ?>

        <!-- Welcome Section -->
        <!-- Display a personalized welcome message to the logged-in user -->
        <?php
            if ($user) {
                // Extract and sanitize the username to prevent XSS attacks
                $safeUsername = htmlspecialchars($user['username']);
        ?>
            <div class="row mb-4">
                <div class="col-12">
                    <!-- Welcome heading with user's name -->
                    <h1 class="mb-0">Welcome back, <?php echo $safeUsername; ?>! 🌿</h1>
                    <p class="text-muted">Manage your plant swaps and grow your community</p>
                </div>
            </div>

            <!-- Profile Card Section -->
            <!-- This card displays the user's profile information at a glance -->
            <!-- col-12 = full width on mobile, col-md-6 = half width on desktop -->
            <div class="row mb-4">
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm h-100">
                        <!-- Card Header -->
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">My Profile</h5>
                        </div>

                        <!-- Card Body with profile info -->
                        <div class="card-body">
                            <!-- Profile Photo and User Details -->
                            <!-- d-flex = use flexbox for layout -->
                            <!-- flex-column = stack items vertically on mobile -->
                            <!-- flex-md-row = stack horizontally on desktop -->
                            <!-- align-items-md-center = vertically center on desktop -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                                <!-- Profile Photo -->
                                <?php
                                    // Extract the profile photo path
                                    $profilePhoto = htmlspecialchars($user['profile_photo_path'] ?? '');

                                    // Check if the user has a profile photo
                                    if (!empty($profilePhoto)) {
                                        // Display the user's uploaded profile photo
                                        echo "<img src=\"" . $profilePhoto . "\" alt=\"" . $safeUsername . "'s profile photo\" class=\"rounded-circle\" style=\"width: 80px; height: 80px; object-fit: cover;\">";
                                    } else {
                                        // Display a default placeholder image if no photo is uploaded
                                        // Using a placeholder service for demo purposes
                                        echo "<img src=\"https://via.placeholder.com/80?text=No+Photo\" alt=\"Default profile placeholder\" class=\"rounded-circle\" style=\"width: 80px; height: 80px; object-fit: cover;\">";
                                    }
                                ?>

                                <!-- Username and Email -->
                                <div>
                                    <!-- Username -->
                                    <h5 class="mb-2">
                                        <?php echo $safeUsername; ?>
                                    </h5>
                                    <!-- Email -->
                                    <p class="mb-2 text-muted">
                                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    </p>

                                    <!-- Verification Badge -->
                                    <!-- Check if user is verified (is_verified = 1) -->
                                    <?php
                                        if ($user['is_verified'] == 1) {
                                            // User is verified - show green badge
                                            echo "<span class=\"badge bg-success\">✓ Verified</span>";
                                        } else {
                                            // User is not verified - show grey badge
                                            echo "<span class=\"badge bg-secondary\">○ Unverified</span>";
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- User Bio -->
                            <!-- Display the user's bio if it exists -->
                            <?php
                                $safeBio = htmlspecialchars($user['bio'] ?? '');

                                if (!empty($safeBio)) {
                                    // User has a bio, display it
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><strong>Bio</strong></small>";
                                    echo "  <p class=\"small mb-0\">" . nl2br($safeBio) . "</p>";
                                    echo "</div>";
                                } else {
                                    // User has no bio yet, display a placeholder
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><em>No bio added yet. <a href=\"profile-edit.php\">Add one now</a></em></small>";
                                    echo "</div>";
                                }
                            ?>

                            <!-- Member Since -->
                            <!-- Display when the user joined -->
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

                <!-- Quick Stats Card -->
                <!-- This card will show stats like number of listings, messages, etc. -->
                <!-- col-12 = full width on mobile, col-md-6 = half width on desktop -->
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm h-100">
                        <!-- Card Header -->
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Quick Stats</h5>
                        </div>

                        <!-- Card Body with stats -->
                        <div class="card-body">
                            <!-- Stats Grid -->
                            <!-- row row-cols-1 = 1 column on mobile -->
                            <!-- row-cols-md-2 = 2 columns on desktop -->
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                <!-- Active Listings Stat -->
                                <div class="col">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="text-success mb-0">0</h3>
                                        <small class="text-muted">Active Listings</small>
                                    </div>
                                </div>

                                <!-- Completed Swaps Stat -->
                                <div class="col">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="text-primary mb-0">0</h3>
                                        <small class="text-muted">Completed Swaps</small>
                                    </div>
                                </div>

                                <!-- Pending Applications Stat -->
                                <div class="col">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="text-warning mb-0">0</h3>
                                        <small class="text-muted">Pending Applications</small>
                                    </div>
                                </div>

                                <!-- Unread Messages Stat -->
                                <div class="col">
                                    <div class="text-center p-3 border rounded">
                                        <h3 class="text-info mb-0">0</h3>
                                        <small class="text-muted">Unread Messages</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Cards Grid -->
            <!-- This section provides quick navigation to key dashboard features -->
            <!-- Each action card is clickable and takes the user to a specific page -->
            <div class="row mb-4">
                <!-- Section Header -->
                <div class="col-12 mb-3">
                    <h3>Quick Actions</h3>
                </div>

                <!-- My Listings Card -->
                <!-- col-12 = full width on mobile, col-md-6 col-lg-3 = 1/4 width on large desktop -->
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="my-listings.php" class="text-decoration-none">
                        <!-- h-100 = make card fill its container height -->
                        <div class="card shadow-sm h-100 text-center p-3 transition-hover" style="cursor: pointer; transition: transform 0.2s;">
                            <!-- Icon or emoji representing listings -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                📋
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">My Listings</h5>
                            <!-- Descriptive text -->
                            <p class="card-text small text-muted mb-0">
                                View and manage your plant listings
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Create New Listing Card -->
                <!-- col-12 = full width on mobile, col-md-6 col-lg-3 = 1/4 width on large desktop -->
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="create-listing.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3 transition-hover" style="cursor: pointer; transition: transform 0.2s;">
                            <!-- Icon representing creating new listing -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ➕
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">Create Listing</h5>
                            <!-- Descriptive text -->
                            <p class="card-text small text-muted mb-0">
                                Post a new plant offer or request
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Inbox/Messages Card -->
                <!-- col-12 = full width on mobile, col-md-6 col-lg-3 = 1/4 width on large desktop -->
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="messages.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3 transition-hover" style="cursor: pointer; transition: transform 0.2s;">
                            <!-- Icon representing messages -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                💬
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">Inbox</h5>
                            <!-- Descriptive text -->
                            <p class="card-text small text-muted mb-0">
                                Check messages from other users
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Edit Profile Card -->
                <!-- col-12 = full width on mobile, col-md-6 col-lg-3 = 1/4 width on large desktop -->
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="profile-edit.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3 transition-hover" style="cursor: pointer; transition: transform 0.2s;">
                            <!-- Icon representing profile editing -->
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ⚙️
                            </div>
                            <!-- Action title -->
                            <h5 class="card-title">Edit Profile</h5>
                            <!-- Descriptive text -->
                            <p class="card-text small text-muted mb-0">
                                Update your profile information
                            </p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Logout Section -->
            <!-- This section provides a prominent logout button at the bottom of the dashboard -->
            <div class="row mb-5">
                <div class="col-12">
                    <!-- Logout Button -->
                    <!-- btn-danger = red color to indicate a destructive action -->
                    <!-- d-grid = full width button on mobile -->
                    <div class="d-grid gap-2">
                        <a href="logout.php" class="btn btn-danger btn-lg">
                            Logout
                        </a>
                    </div>
                    <!-- Help text explaining what logout does -->
                    <small class="text-muted d-block text-center mt-2">
                        You will be logged out and returned to the login page
                    </small>
                </div>
            </div>

        <?php
            }
        ?>
    </div>

    <!-- Include the site footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>