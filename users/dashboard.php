<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';



$userID = intval($_SESSION['user_id']);


$user = null;
$errorMessage = '';



try {
    
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

    
    $userStatement = $connection->prepare($userQuery);
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
    $userStatement->execute();
    
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

} catch (PDOException $error) {
    $errorMessage = "Database error: " . $error->getMessage();
}


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
    
}

?>

<!-- ============================================================ -->
<!-- HTML Presentation Layer: User Dashboard                      -->
<!-- ============================================================ -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PlantBnB</title>
</head>
<body>
    <!-- Bootstrap container with top margin utility (mt-4) for vertical spacing -->
    <div class="container mt-4">
        
        <!-- Error display section with Bootstrap alert styling -->
        <?php
            if (!empty($errorMessage)) {
                
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Personalized welcome section -->
        <?php
            if ($user) {
                $safeUsername = htmlspecialchars($user['username']);
        ?>
            <!-- Bootstrap row with bottom margin (mb-4) for vertical spacing -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="mb-0">Welcome back, <?php echo $safeUsername; ?>! 🌿</h1>
                    <!-- text-muted utility class provides subdued gray color for secondary text -->
                    <p class="text-muted">Manage your plant swaps and grow your community</p>
                </div>
            </div>

            <!-- Profile information card with responsive layout -->
            <div class="row mb-4">
                <!-- Bootstrap grid: col-12 (full width mobile), col-md-8 (66% on medium+ screens),
                     offset-md-2 (centers by adding 16.67% left margin) -->
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">My Profile</h5>
                        </div>

                        <div class="card-body">
                            <!-- Flexbox container: flex-column (mobile stacked), flex-md-row (horizontal on medium+ screens) -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                                <?php
                                    $profilePhoto = $user['profile_photo_path'] ?? '';
                                    
                                    if (!empty($profilePhoto)) {
                                        
                                        $profilePhotoPath = '../' . htmlspecialchars($profilePhoto);
                                        
                                        
                                        
                                        echo "<img src=\"" . $profilePhotoPath . "\" alt=\"" . $safeUsername . "'s profile photo\" class=\"rounded-circle\" style=\"width: 80px; height: 80px; object-fit: cover;\">";
                                        
                                    } else {
                                        
                                        echo "<img src=\"https:
                                    }
                                ?>

                                <div>
                                    <!-- Display username with rating badge if available -->
                                    <h5 class="mb-2">
                                        <?php echo $safeUsername; ?>
                                        <?php
                                            if ($avgRating !== null) {
                                                echo ' <span class="badge bg-warning text-dark ms-2">⭐ ' . htmlspecialchars($avgRating) . '</span>';
                                                if ($ratingCount > 0) {
                                                    echo ' <small class="text-muted">(' . $ratingCount . ')</small>';
                                                }
                                            } else {
                                                echo ' <small class="text-muted ms-2">(no ratings yet)</small>';
                                            }
                                        ?>
                                    </h5>
                                    
                                    <p class="mb-2 text-muted">
                                        <small><?php echo htmlspecialchars($user['email']); ?></small>
                                    </p>

                                    <!-- Conditional verification badge based on is_verified status (1 = verified, 0 = unverified) -->
                                    <?php
                                        if ($user['is_verified'] == 1) {
                                            echo "<span class=\"badge bg-success\">✓ Verified</span>";
                                        } else {
                                            echo "<span class=\"badge bg-secondary\">○ Unverified</span>";
                                        }
                                    ?>
                                </div>
                            </div>

                            <!-- User biography section with optional content -->
                            <?php
                                $bio = $user['bio'] ?? '';
                                $safeBio = htmlspecialchars($bio);

                                if (!empty($safeBio)) {
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><strong>Bio</strong></small>";
                                    
                                    echo "  <p class=\"small mb-0\">" . nl2br($safeBio) . "</p>";
                                    echo "</div>";
                                } else {
                                    echo "<div class=\"mb-3\">";
                                    echo "  <small class=\"text-muted\"><em>No bio added yet. <a href=\"profile-edit.php\">Add one now</a></em></small>";
                                    echo "</div>";
                                }
                            ?>

                            <div class="mb-3">
                                <small class="text-muted">
                                    <strong>Member since:</strong> 
                                    <?php echo htmlspecialchars($user['created_at']); ?>
                                </small>
                            </div>

                            <!-- d-grid utility creates full-width button layout -->
                            <div class="d-grid">
                                <a href="profile-edit.php" class="btn btn-outline-primary">
                                    Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick navigation section with responsive card layout -->
            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <h3>Quick Actions</h3>
                </div>

                <!-- Bootstrap grid: col-12 (full width mobile), col-md-4 (33.33% on medium+ screens)
                     Creates 3-column layout on desktop, stacked on mobile -->
                <div class="col-12 col-md-4">
                    <!-- text-decoration-none removes default link underline -->
                    <a href="/plantbnb/listings/listings.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <div class="mb-2" style="font-size: 2.5rem;">
                                📋
                            </div>
                            <h5 class="card-title">Browse Listings</h5>
                            <p class="card-text small text-muted mb-0">
                                Browse all plant listings
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-4">
                    <a href="/plantbnb/listings/listing-creator.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ➕
                            </div>
                            <h5 class="card-title">Create Listing</h5>
                            <p class="card-text small text-muted mb-0">
                                Post a new plant offer or request
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-4">
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

                <div class="col-12 col-md-4">
                    <a href="/plantbnb/users/verification.php" class="text-decoration-none">
                        <div class="card shadow-sm h-100 text-center p-3">
                            <div class="mb-2" style="font-size: 2.5rem;">
                                ✅
                            </div>
                            <h5 class="card-title">User Verification</h5>
                            <p class="card-text small text-muted mb-0">
                                Upload ID documents for verification
                            </p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Logout section with destructive action styling -->
            <div class="row mb-5">
                <div class="col-12">
                    <!-- d-grid creates full-width button layout for mobile accessibility -->
                    <div class="d-grid gap-2">
                        <!-- btn-danger (red) indicates destructive/logout action, btn-lg for touch-friendly size -->
                        <a href="/plantbnb/users/logout.php" class="btn btn-danger btn-lg">
                            Logout
                        </a>
                    </div>
                    <small class="text-muted d-block text-center mt-2">
                        You will be logged out and returned to the login page
                    </small>
                </div>
            </div>

        <?php
            }
        ?>
    </div> <!-- End: Container -->
</body>
</html>