<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

// NOTE: Session is already started in header.php; user-auth.php ensures only authenticated users access this page.
// Extract user ID from session and cast to integer for type safety
$userID = intval($_SESSION['user_id']);

// Initialize variables to prevent undefined variable warnings
$user = null;
$bio = '';
$currentProfilePhotoPath = '';
$successMessage = '';
$errorMessage = '';

// Fetch current user profile data to pre-populate the form
try {
    // NOTE: PDO prepared statements protect against SQL Injection by separating SQL logic from user data.
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            bio,
            profile_photo_path
        FROM users
        WHERE user_id = :userID
    ";

    $userStatement = $connection->prepare($userQuery);
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
    $userStatement->execute();
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // If user not found, session is invalid (user may have been deleted)
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Pre-fill form fields with current database values; use null coalescing for optional fields
    $bio = isset($user['bio']) ? $user['bio'] : '';
    $currentProfilePhotoPath = isset($user['profile_photo_path']) ? $user['profile_photo_path'] : '';

} catch (PDOException $error) {
    // NOTE: Catching PDOException prevents application crashes and provides graceful error handling.
    $errorMessage = "Database error: " . $error->getMessage();
}

// Process form submission on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Retrieve and sanitize bio input
    $newBio = isset($_POST['bio']) ? $_POST['bio'] : '';
    $newBio = trim($newBio);
    
    // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) attacks by converting special characters to HTML entities.
    $sanitizedBio = htmlspecialchars($newBio);

    // Retain current photo path unless a new file is uploaded
    $newProfilePhotoPath = $currentProfilePhotoPath;

    // NOTE: File upload validation occurs in uploadFile() helper function (MIME type checking, size limits).
    // This centralizes security logic and prevents arbitrary file uploads.
    $profilePhotoResult = uploadFile(
        'profile_photo',
        __DIR__ . '/../uploads/profiles',
        ['image/jpeg', 'image/png'],
        2 * 1024 * 1024 // 2MB max file size
    );

    // uploadFile() returns: file path (success), error string (failure), or null (no file provided)
    if ($profilePhotoResult !== null) {
        // Differentiate between success (path contains '/') and error (plain message)
        if (strpos($profilePhotoResult, '/') !== false) {
            $newProfilePhotoPath = $profilePhotoResult;
        } else {
            $errorMessage = "Profile photo: " . $profilePhotoResult;
        }
    }

    // Persist changes to database only if file upload succeeded or was optional
    if (empty($errorMessage)) {
        try {
            // NOTE: Prepared statements with bound parameters prevent SQL Injection attacks.
            $updateQuery = "
                UPDATE users
                SET 
                    bio = :bio,
                    profile_photo_path = :profilePhotoPath
                WHERE user_id = :userID
            ";

            $updateStatement = $connection->prepare($updateQuery);
            $updateStatement->bindParam(':bio', $sanitizedBio, PDO::PARAM_STR);
            $updateStatement->bindParam(':profilePhotoPath', $newProfilePhotoPath, PDO::PARAM_STR);
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $updateStatement->execute();

            $successMessage = "Your profile has been updated successfully!";
            
            // Update local variables to reflect new state in the form
            $bio = $newBio;
            $currentProfilePhotoPath = $newProfilePhotoPath;

        } catch (PDOException $error) {
            $errorMessage = "Failed to update profile: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - PlantBnB</title>
</head>
<body>
    <!-- Main Content: Profile Edit Form -->
         
    <!-- Bootstrap container provides responsive max-width centering -->
    <div class="container mt-4">
        
        <!-- Navigation: Back Button -->
        <!-- NOTE: Bootstrap grid uses 12-column system; offset-md-2 + col-md-8 = centered 2/3 width on medium+ screens -->
        <div class="row mb-3">
            <div class="col-12 col-md-8 offset-md-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
        
        <!-- Main Card: Profile Edit Form -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                
                <div class="card shadow-sm">
                    
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Edit Your Profile</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- Current Profile Photo Display -->
                        <div class="mb-4">
                            <h5 class="mb-3">Current Profile Photo</h5>

                            <div class="text-center">
                                <?php
                                    if (!empty($currentProfilePhotoPath)) {
                                        // Build relative path from users/ directory to uploads/
                                        $displayPath = '../' . htmlspecialchars($currentProfilePhotoPath);
                                        echo "<img src=\"" . $displayPath . "\" alt=\"Current profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    } else {
                                        echo "<img src=\"https://via.placeholder.com/120?text=No+Photo\" alt=\"No profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Edit Profile Form -->
                        <!-- NOTE: enctype="multipart/form-data" is mandatory for file uploads; POST method keeps data out of URL -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            
                            <!-- Bio Field -->
                            <div class="mb-3">
                                <label for="bio" class="form-label">Short Bio</label>
                                <textarea 
                                    id="bio" 
                                    name="bio" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Tell other users a bit about yourself and your interest in plants (optional)"
                                ><?php echo htmlspecialchars($bio); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    Write a short bio to help other users get to know you (max 500 characters recommended)
                                </small>
                            </div>

                            <!-- Profile Photo Upload Field -->
                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Update Profile Photo</label>
                                <!-- NOTE: accept attribute provides client-side guidance; server-side validation in PHP is mandatory -->
                                <input 
                                    type="file" 
                                    id="profile_photo" 
                                    name="profile_photo" 
                                    class="form-control" 
                                    accept=".jpg, .jpeg, .png"
                                >
                                <small class="text-muted d-block mt-1">
                                    JPG or PNG format. Maximum file size: 2MB
                                </small>
                            </div>

                            <hr class="my-4">

                            <!-- Submit Button: d-grid creates full-width layout (mobile-friendly) -->
                            <div class="d-grid gap-2 mb-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>