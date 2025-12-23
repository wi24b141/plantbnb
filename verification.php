<?php
require_once 'includes/header.php';
require_once 'includes/user-auth.php';
require_once 'includes/db.php';

// Store the user_id from the session for use in queries
// We use intval() to ensure it's an integer for extra safety
$userID = intval($_SESSION['user_id']);

// ============================================
// INITIALIZE VARIABLES
// ============================================

// Initialize variables to store user data and feedback messages
$user = null;
$currentVerificationDocPath = '';
$isVerified = 0;
$successMessage = '';
$errorMessage = '';

// ============================================
// FETCH CURRENT USER DATA
// ============================================

// Use a try-catch block to safely handle database errors
try {
    // Query to fetch the user's current verification status and document
    // We need is_verified and verification_document_path to show current status
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            is_verified,
            verification_document_path
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

    // Store the verification status and document path
    // If is_verified is null, set it to 0 (not verified)
    $isVerified = intval($user['is_verified'] ?? 0);
    // If verification_document_path is null, set it to empty string
    $currentVerificationDocPath = $user['verification_document_path'] ?? '';

} catch (PDOException $error) {
    // If a database error occurs, catch it and display a friendly message
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the upload

    // Initialize variable to store new verification document path
    // We'll update this only if a file is uploaded
    $newVerificationDocPath = $currentVerificationDocPath;

    // ============================================
    // HANDLE FILE UPLOAD
    // ============================================

    // Check if a file was uploaded in the verification_document field
    // isset($_FILES['verification_document']) checks if the file input exists
    // $_FILES['verification_document']['error'] == UPLOAD_ERR_OK checks if upload was successful (0 = no error)
    if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === UPLOAD_ERR_OK) {
        // A file was uploaded successfully, now validate it

        // Extract file upload information
        // $_FILES['verification_document']['tmp_name'] = temporary file location on server
        // $_FILES['verification_document']['name'] = original filename from user's computer
        // $_FILES['verification_document']['size'] = file size in bytes
        // $_FILES['verification_document']['type'] = MIME type (e.g., image/jpeg, application/pdf)
        $uploadedFileName = $_FILES['verification_document']['name'];
        $uploadedFileSize = $_FILES['verification_document']['size'];
        $uploadedFileTmpPath = $_FILES['verification_document']['tmp_name'];
        $uploadedFileMimeType = $_FILES['verification_document']['type'];

        // Validate file size
        // Maximum allowed size is 5MB = 5 * 1024 * 1024 = 5242880 bytes
        // ID documents can be scans/PDFs so we allow slightly larger size than profile photos
        $maxFileSize = 5 * 1024 * 1024;

        if ($uploadedFileSize > $maxFileSize) {
            // File is too large, set error message
            $errorMessage = "File size exceeds 5MB limit. Please choose a smaller file.";
        } else if ($uploadedFileMimeType !== 'image/jpeg' && $uploadedFileMimeType !== 'image/png' && $uploadedFileMimeType !== 'application/pdf') {
            // File type is not allowed (only JPG, PNG, and PDF allowed for ID documents)
            $errorMessage = "Only JPG, PNG, and PDF files are allowed. Please upload your ID document in one of these formats.";
        } else {
            // File passed validation, now process the upload

            // Create the uploads/verification directory if it doesn't exist
            // This ensures the directory is ready to receive the file
            if (!is_dir('uploads/verification')) {
                // Directory doesn't exist, so create it
                // 0777 = permissions (readable, writable, executable for everyone)
                // true = create parent directories if needed
                mkdir('uploads/verification', 0777, true);
            }

            // Generate a unique filename to prevent overwriting existing files
            // uniqid() creates a unique ID based on current time (13 characters)
            // This ensures no two files will have the same name
            // basename() extracts just the filename from the full path
            $uniqueFileName = uniqid() . "_" . basename($uploadedFileName);

            // Build the full path where the file will be saved
            // This path is relative to the web root (e.g., uploads/verification/someid_document.pdf)
            $destinationPath = 'uploads/verification/' . $uniqueFileName;

            // Move the uploaded file from temporary location to permanent location
            // move_uploaded_file() is the secure way to handle file uploads
            // It validates that the file was actually uploaded via HTTP POST
            if (move_uploaded_file($uploadedFileTmpPath, $destinationPath)) {
                // File was successfully moved, update the path variable
                // We'll save this path to the database
                $newVerificationDocPath = $destinationPath;
            } else {
                // File move failed for some reason
                $errorMessage = "Failed to save the verification document. Please try again.";
            }
        }
    } else {
        // No file was uploaded or there was an upload error
        $errorMessage = "Please select an ID document to upload.";
    }

    // ============================================
    // UPDATE DATABASE
    // ============================================

    // Only update the database if there are no errors
    if (empty($errorMessage)) {
        try {
            // Query to update the user's verification_document_path
            // We do NOT set is_verified here because that's done by an admin
            // We only store the document path so admin can review it later
            $updateQuery = "
                UPDATE users
                SET 
                    verification_document_path = :verificationDocPath
                WHERE user_id = :userID
            ";

            // Prepare the update statement
            $updateStatement = $connection->prepare($updateQuery);

            // Bind all the parameters to prevent SQL injection
            $updateStatement->bindParam(':verificationDocPath', $newVerificationDocPath, PDO::PARAM_STR);
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Execute the update
            $updateStatement->execute();

            // Update was successful!
            $successMessage = "Your verification document has been uploaded successfully! An admin will review it soon.";

            // Update the local variable so the page reflects the new data
            $currentVerificationDocPath = $newVerificationDocPath;

        } catch (PDOException $error) {
            // If a database error occurs, set an error message
            $errorMessage = "Failed to upload verification document: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - PlantBnB</title>
</head>
<body>
    <!-- ============================================
         VERIFICATION PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

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

        <!-- Display success message if document was uploaded -->
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

        <!-- Main Verification Card -->
        <!-- col-12 = full width on mobile, col-md-8 = 2/3 width on desktop -->
        <!-- offset-md-2 = centers the card on desktop -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                <div class="card shadow-sm">
                    <!-- Card Header -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Account Verification</h3>
                    </div>

                    <!-- Card Body with verification info and form -->
                    <div class="card-body">
                        <!-- Current Verification Status Section -->
                        <!-- This section shows whether the user is verified or not -->
                        <div class="mb-4">
                            <h5 class="mb-3">Current Verification Status</h5>

                            <!-- Verification status display area -->
                            <!-- We center it horizontally using text-center -->
                            <div class="text-center">
                                <?php
                                    // Check if user is already verified
                                    if ($isVerified == 1) {
                                        // User is verified - show green success badge
                                        echo "<div class=\"alert alert-success\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">✓ Verified Account</h4>";
                                        echo "  <p class=\"mb-0\">Your account has been verified by an admin. You can now enjoy full access to PlantBnB!</p>";
                                        echo "</div>";
                                    } else if (!empty($currentVerificationDocPath)) {
                                        // User uploaded a document but not verified yet - show yellow warning badge
                                        echo "<div class=\"alert alert-warning\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">⏳ Pending Verification</h4>";
                                        echo "  <p class=\"mb-0\">Your verification document has been uploaded and is waiting for admin review. This usually takes 1-2 business days.</p>";
                                        echo "</div>";
                                    } else {
                                        // User has not uploaded any document yet - show blue info badge
                                        echo "<div class=\"alert alert-info\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">○ Not Verified</h4>";
                                        echo "  <p class=\"mb-0\">Your account is not verified yet. Please upload an ID document below to start the verification process.</p>";
                                        echo "</div>";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Information About Verification -->
                        <!-- This section explains why verification is important -->
                        <div class="mb-4">
                            <h5 class="mb-3">Why Get Verified?</h5>
                            <ul class="small">
                                <li>Build trust with other PlantBnB users</li>
                                <li>Show a verified badge on your profile</li>
                                <li>Access premium features (coming soon)</li>
                                <li>Help create a safer plant-swapping community</li>
                            </ul>
                        </div>

                        <!-- Upload Verification Document Form -->
                        <!-- Only show the upload form if user is not already verified -->
                        <?php
                            if ($isVerified == 0) {
                                // User is not verified, show the upload form
                        ?>
                            <!-- Form Divider -->
                            <hr class="my-4">

                            <h5 class="mb-3">Upload ID Document</h5>

                            <!-- Instructions for uploading -->
                            <div class="alert alert-light" role="alert">
                                <small>
                                    <strong>Accepted Documents:</strong> Government-issued ID, Driver's License, Passport, or National ID Card<br>
                                    <strong>Privacy:</strong> Your document is securely stored and only visible to administrators<br>
                                    <strong>File Format:</strong> JPG, PNG, or PDF (maximum 5MB)
                                </small>
                            </div>

                            <!-- Verification Upload Form -->
                            <!-- CRITICAL: enctype="multipart/form-data" is REQUIRED for file uploads -->
                            <!-- Without this, the file upload will not work -->
                            <!-- method="POST" sends data securely -->
                            <!-- action="" submits to the same page for processing -->
                            <form method="POST" action="" enctype="multipart/form-data">
                                <!-- ID Document Upload -->
                                <!-- mb-3 = adds bottom margin for touch-friendly spacing -->
                                <div class="mb-3">
                                    <label for="verification_document" class="form-label">Select ID Document</label>
                                    <!-- type="file" creates a file upload input -->
                                    <!-- accept=".jpg, .jpeg, .png, .pdf" restricts file selection to allowed types -->
                                    <!-- required ensures user must select a file before submitting -->
                                    <input 
                                        type="file" 
                                        id="verification_document" 
                                        name="verification_document" 
                                        class="form-control" 
                                        accept=".jpg, .jpeg, .png, .pdf"
                                        required
                                    >
                                    <!-- Helper text explaining file requirements -->
                                    <small class="text-muted d-block mt-1">
                                        JPG, PNG, or PDF format. Maximum file size: 5MB
                                    </small>
                                </div>

                                <!-- Privacy Notice -->
                                <!-- This reassures users about data security -->
                                <div class="mb-3">
                                    <small class="text-muted">
                                        🔒 Your personal information is encrypted and stored securely. We will never share your ID document with third parties.
                                    </small>
                                </div>

                                <!-- Submit Button -->
                                <!-- d-grid = full width button on mobile -->
                                <!-- gap-2 = adds spacing inside the button area -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        Upload ID Document
                                    </button>
                                </div>
                            </form>

                        <?php
                            } else {
                                // User is already verified, show a message
                                echo "<div class=\"alert alert-success text-center\" role=\"alert\">";
                                echo "  <p class=\"mb-0\">🎉 Your account is fully verified! No further action is needed.</p>";
                                echo "</div>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>