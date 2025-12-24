<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$userID = intval($_SESSION['user_id']);

$user = null;
$bio = '';
$currentProfilePhotoPath = '';
$successMessage = '';
$errorMessage = '';

// ============================================
// FETCH CURRENT USER DATA
// ============================================

// Use a try-catch block to safely handle database errors
try {
    // Query to fetch the user's current profile information
    // We need bio and profile_photo_path to pre-fill the form
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

    // Prepare the statement to prevent SQL injection attacks
    $userStatement = $connection->prepare($userQuery);

    // Bind the user ID parameter
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the prepared statement
    $userStatement->execute();

    // Fetch the result as an associative array
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found in the database
    if (!$user) {
        // User not found, destroy session and redirect
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Pre-fill the form fields with current user data
    // If bio is null, set it to empty string
    $bio = $user['bio'] ?? '';
    // If profile_photo_path is null, set it to empty string
    $currentProfilePhotoPath = $user['profile_photo_path'] ?? '';

} catch (PDOException $error) {
    // If a database error occurs, catch it and display a friendly message
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the update

    // Get the bio from the form
    // trim() removes whitespace from beginning and end
    // ?? '' provides a default empty string if the key doesn't exist
    $newBio = trim($_POST['bio'] ?? '');

    // Sanitize the bio to prevent XSS attacks when storing in database
    // We use htmlspecialchars() to convert special characters to HTML entities
    $sanitizedBio = htmlspecialchars($newBio);

    // Initialize variable to store new profile photo path
    // We'll update this only if a file is uploaded
    $newProfilePhotoPath = $currentProfilePhotoPath;

    // ============================================
    // HANDLE FILE UPLOAD
    // ============================================

    // Check if a file was uploaded in the profile_photo field
    // isset($_FILES['profile_photo']) checks if the file input exists
    // $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK checks if upload was successful (0 = no error)
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        // A file was uploaded successfully, now validate it

        // Extract file upload information
        // $_FILES['profile_photo']['tmp_name'] = temporary file location on server
        // $_FILES['profile_photo']['name'] = original filename from user's computer
        // $_FILES['profile_photo']['size'] = file size in bytes
        // $_FILES['profile_photo']['type'] = MIME type (e.g., image/jpeg)
        $uploadedFileName = $_FILES['profile_photo']['name'];
        $uploadedFileSize = $_FILES['profile_photo']['size'];
        $uploadedFileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $uploadedFileMimeType = $_FILES['profile_photo']['type'];

        // Validate file size
        // Maximum allowed size is 2MB = 2 * 1024 * 1024 = 2097152 bytes
        $maxFileSize = 2 * 1024 * 1024;

        if ($uploadedFileSize > $maxFileSize) {
            // File is too large, set error message
            $errorMessage = "File size exceeds 2MB limit. Please choose a smaller file.";
        } else if ($uploadedFileMimeType !== 'image/jpeg' && $uploadedFileMimeType !== 'image/png') {
            // File type is not allowed (only JPG and PNG allowed)
            $errorMessage = "Only JPG and PNG files are allowed. Please upload an image in one of these formats.";
        } else {
            // File passed validation, now process the upload

            // Create the uploads/profiles directory if it doesn't exist
            // This ensures the directory is ready to receive the file
            if (!is_dir('uploads/profiles')) {
                // Directory doesn't exist, so create it
                // 0777 = permissions (readable, writable, executable for everyone)
                // true = create parent directories if needed
                mkdir('uploads/profiles', 0777, true);
            }

            // Generate a unique filename to prevent overwriting existing files
            // uniqid() creates a unique ID based on current time (13 characters)
            // This ensures no two files will have the same name
            // basename() extracts just the filename from the full path
            $uniqueFileName = uniqid() . "_" . basename($uploadedFileName);

            // Build the full path where the file will be saved
            // This path is relative to the web root (e.g., uploads/profiles/someid_photo.jpg)
            $destinationPath = 'uploads/profiles/' . $uniqueFileName;

            // Move the uploaded file from temporary location to permanent location
            // move_uploaded_file() is the secure way to handle file uploads
            // It validates that the file was actually uploaded via HTTP POST
            if (move_uploaded_file($uploadedFileTmpPath, $destinationPath)) {
                // File was successfully moved, update the path variable
                // We'll save this path to the database
                $newProfilePhotoPath = $destinationPath;
            } else {
                // File move failed for some reason
                $errorMessage = "Failed to save the profile photo. Please try again.";
            }
        }
    }

    // ============================================
    // UPDATE DATABASE
    // ============================================

    // Only update the database if there are no errors
    if (empty($errorMessage)) {
        try {
            // Query to update the user's bio and profile_photo_path
            $updateQuery = "
                UPDATE users
                SET 
                    bio = :bio,
                    profile_photo_path = :profilePhotoPath
                WHERE user_id = :userID
            ";

            // Prepare the update statement
            $updateStatement = $connection->prepare($updateQuery);

            // Bind all the parameters to prevent SQL injection
            $updateStatement->bindParam(':bio', $sanitizedBio, PDO::PARAM_STR);
            $updateStatement->bindParam(':profilePhotoPath', $newProfilePhotoPath, PDO::PARAM_STR);
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Execute the update
            $updateStatement->execute();

            // Update was successful!
            $successMessage = "Your profile has been updated successfully!";

            // Update the local variables so the form reflects the new data
            $bio = $newBio;
            $currentProfilePhotoPath = $newProfilePhotoPath;

        } catch (PDOException $error) {
            // If a database error occurs, set an error message
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
    <!-- ============================================
         EDIT PROFILE PAGE - HTML VIEW (BOTTOM)
         ============================================ -->
         
    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <!-- This button allows users to easily navigate back -->
        <!-- col-12 = full width on mobile, col-md-8 = narrower on desktop -->
        <div class="row mb-3">
            <div class="col-12 col-md-8 offset-md-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Display success message if profile was updated -->
        <?php
            if (!empty($successMessage)) {
                // Success alert - green background
                // alert-dismissible allows user to close the alert
                echo "<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($successMessage);
                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "</div>";
            }
        ?>

        <!-- Display error message if something went wrong -->
        <?php
            if (!empty($errorMessage)) {
                // Error alert - red background
                echo "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "</div>";
            }
        ?>

        <!-- Main Edit Profile Card -->
        <!-- col-12 = full width on mobile, col-md-8 = 2/3 width on desktop -->
        <!-- offset-md-2 = centers the card on desktop -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                <div class="card shadow-sm">
                    <!-- Card Header -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Edit Your Profile</h3>
                    </div>

                    <!-- Card Body with form -->
                    <div class="card-body">
                        <!-- Current Profile Photo Section -->
                        <!-- This section displays the user's current profile photo -->
                        <div class="mb-4">
                            <h5 class="mb-3">Current Profile Photo</h5>

                            <!-- Profile photo display area -->
                            <!-- We center it horizontally using text-center -->
                            <div class="text-center">
                                <?php
                                    // Check if user has a profile photo
                                    if (!empty($currentProfilePhotoPath)) {
                                        // Display the user's current profile photo
                                        // The image is displayed in a circle using rounded-circle class
                                        // object-fit: cover ensures the image fills the circle without distortion
                                        echo "<img src=\"" . htmlspecialchars($currentProfilePhotoPath) . "\" alt=\"Current profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    } else {
                                        // No profile photo yet, display a placeholder
                                        // Using a placeholder service for demo purposes
                                        echo "<img src=\"https://via.placeholder.com/120?text=No+Photo\" alt=\"No profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Edit Profile Form -->
                        <!-- CRITICAL: enctype="multipart/form-data" is REQUIRED for file uploads -->
                        <!-- Without this, the file upload will not work -->
                        <!-- method="POST" sends data securely -->
                        <!-- action="" submits to the same page for processing -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Bio Textarea -->
                            <!-- mb-3 = adds bottom margin for touch-friendly spacing -->
                            <!-- This allows users to enter a longer bio with multiple lines -->
                            <div class="mb-3">
                                <label for="bio" class="form-label">Short Bio</label>
                                <!-- textarea allows multi-line text input -->
                                <!-- rows="5" sets the initial height (5 lines) -->
                                <!-- htmlspecialchars() prevents XSS when displaying existing bio -->
                                <textarea 
                                    id="bio" 
                                    name="bio" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Tell other users a bit about yourself and your interest in plants (optional)"
                                ><?php echo htmlspecialchars($bio); ?></textarea>
                                <!-- Helper text explaining bio purpose -->
                                <small class="text-muted d-block mt-1">
                                    Write a short bio to help other users get to know you (max 500 characters recommended)
                                </small>
                            </div>

                            <!-- Profile Photo Upload -->
                            <!-- mb-3 = adds bottom margin for touch-friendly spacing -->
                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Update Profile Photo</label>
                                <!-- type="file" creates a file upload input -->
                                <!-- accept=".jpg, .jpeg, .png" restricts file selection to image types -->
                                <!-- This helps guide users to select the correct file type -->
                                <input 
                                    type="file" 
                                    id="profile_photo" 
                                    name="profile_photo" 
                                    class="form-control" 
                                    accept=".jpg, .jpeg, .png"
                                >
                                <!-- Helper text explaining file requirements -->
                                <small class="text-muted d-block mt-1">
                                    JPG or PNG format. Maximum file size: 2MB
                                </small>
                            </div>

                            <!-- Form Divider -->
                            <!-- Visually separates the form sections -->
                            <hr class="my-4">

                            <!-- Submit Button -->
                            <!-- d-grid = full width button on mobile -->
                            <!-- gap-2 = adds spacing inside the button area -->
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