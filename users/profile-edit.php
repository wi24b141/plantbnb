<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';



$userID = intval($_SESSION['user_id']);


$user = null;
$bio = '';
$currentProfilePhotoPath = '';
$successMessage = '';
$errorMessage = '';


try {
    
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

    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    
    $bio = isset($user['bio']) ? $user['bio'] : '';
    $currentProfilePhotoPath = isset($user['profile_photo_path']) ? $user['profile_photo_path'] : '';

} catch (PDOException $error) {
    
    $errorMessage = "Database error: " . $error->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    $newBio = isset($_POST['bio']) ? $_POST['bio'] : '';
    $newBio = trim($newBio);
    
    
    $sanitizedBio = htmlspecialchars($newBio);

    
    $newProfilePhotoPath = $currentProfilePhotoPath;

    
    
    $profilePhotoResult = uploadFile(
        'profile_photo',
        __DIR__ . '/../uploads/profiles',
        ['image/jpeg', 'image/png'],
        2 * 1024 * 1024 
    );

    
    if ($profilePhotoResult !== null) {
        
        if (strpos($profilePhotoResult, '/') !== false) {
            $newProfilePhotoPath = $profilePhotoResult;
        } else {
            $errorMessage = "Profile photo: " . $profilePhotoResult;
        }
    }

    
    if (empty($errorMessage)) {
        try {
            
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
                                        
                                        $displayPath = '../' . htmlspecialchars($currentProfilePhotoPath);
                                        echo "<img src=\"" . $displayPath . "\" alt=\"Current profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    } else {
                                        echo "<img src=\"https:
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