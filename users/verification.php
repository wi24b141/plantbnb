<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

// NOTE: user-auth.php enforces session-based authentication, preventing unauthorized access
// intval() ensures type safety and prevents potential injection attacks
$userID = intval($_SESSION['user_id']);

// Initialize state variables for page rendering and error handling
$user = null;
$currentVerificationDocPath = '';
$isVerified = 0;
$successMessage = '';
$errorMessage = '';

// NOTE: PDO prepared statements protect against SQL injection by separating query logic from data
try {
    // Retrieve current user verification status and document path
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

    // Prepared statement with parameterized query prevents SQL injection attacks
    $userStatement = $connection->prepare($userQuery);
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
    $userStatement->execute();
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Enforce session integrity: invalid user_id terminates session
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Normalize null values for consistent state handling
    if ($user['is_verified'] === null) {
        $isVerified = 0;
    } else {
        $isVerified = intval($user['is_verified']);
    }
    
    if ($user['verification_document_path'] === null) {
        $currentVerificationDocPath = '';
    } else {
        $currentVerificationDocPath = $user['verification_document_path'];
    }

} catch (PDOException $error) {
    // Centralized exception handling for database errors
    $errorMessage = "Database error: " . $error->getMessage();
}

// Process POST request for verification document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $newVerificationDocPath = $currentVerificationDocPath;

    // NOTE: File upload security implemented via uploadFile() helper includes MIME type validation,
    // file size restrictions, and path sanitization to prevent directory traversal attacks
    $verificationDocResult = uploadFile(
        'verification_document',
        __DIR__ . '/../uploads/verification',
        ['image/jpeg', 'image/png', 'application/pdf'],
        5 * 1024 * 1024  // 5MB maximum file size
    );

    // Validate upload result and handle errors
    if (is_string($verificationDocResult) && strpos($verificationDocResult, '/') === false) {
        $errorMessage = "Verification document: " . $verificationDocResult;
        
    } else if ($verificationDocResult !== null) {
        $newVerificationDocPath = $verificationDocResult;
        
    } else {
        $errorMessage = "Please select an ID document to upload.";
    }

    // Persist document path to database only after successful upload
    if (empty($errorMessage)) {
        
        try {
            // NOTE: Verification workflow separates document submission from approval - users upload,
            // administrators manually verify. This maintains security and prevents self-verification
            $updateQuery = "
                UPDATE users
                SET 
                    verification_document_path = :verificationDocPath
                WHERE user_id = :userID
            ";

            // PDO prepared statement prevents SQL injection via parameterized queries
            $updateStatement = $connection->prepare($updateQuery);
            $updateStatement->bindParam(':verificationDocPath', $newVerificationDocPath, PDO::PARAM_STR);
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $updateStatement->execute();

            $successMessage = "Your verification document has been uploaded successfully! An admin will review it soon.";
            $currentVerificationDocPath = $newVerificationDocPath;

        } catch (PDOException $error) {
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
    <!-- Main container with top margin for visual spacing -->
    <div class="container mt-4">
        
        <!-- Navigation: Back to Dashboard -->
        <!-- Uses Bootstrap grid: col-md-8 offset-md-2 creates centered 8-column layout on medium+ screens -->
        <div class="row mb-3">
            <div class="col-12 col-md-8 offset-md-2">
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success feedback message -->
        <?php
            if (!empty($successMessage)) {
                // NOTE: htmlspecialchars() prevents XSS attacks by encoding special HTML characters
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                echo htmlspecialchars($successMessage);
                echo "</div>";
            }
        ?>

        <!-- Error feedback message -->
        <?php
            if (!empty($errorMessage)) {
                // XSS mitigation via output encoding
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Main verification card - centered using Bootstrap grid offset -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                
                <div class="card shadow-sm">
                    
                    <!-- Card header with brand color styling -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Account Verification</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- Verification status display -->
                        <div class="mb-4">
                            <h5 class="mb-3">Current Verification Status</h5>

                            <div class="text-center">
                                <?php
                                    // Display contextual alert based on verification state
                                    if ($isVerified == 1) {
                                        echo "<div class=\"alert alert-success\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Verified Account</h4>";
                                        echo "  <p class=\"mb-0\">Your account has been verified by an admin.</p>";
                                        echo "</div>";
                                        
                                    } else if (!empty($currentVerificationDocPath)) {
                                        echo "<div class=\"alert alert-warning\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Pending Verification</h4>";
                                        echo "  <p class=\"mb-0\">Your verification document has been uploaded and is waiting for admin review.</p>";
                                        echo "</div>";
                                        
                                    } else {
                                        echo "<div class=\"alert alert-info\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Not Verified</h4>";
                                        echo "  <p class=\"mb-0\">Your account is not verified yet. Please upload an ID document below.</p>";
                                        echo "</div>";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Verification benefits section -->
                        <div class="mb-4">
                            <h5 class="mb-3">Why Get Verified?</h5>
                            <ul class="small">
                                <li>Build trust with other PlantBnB users</li>
                                <li>Show a verified badge on your profile</li>
                                <li>Help create a safer plant-swapping community</li>
                            </ul>
                        </div>

                        <!-- ID document upload form (conditional on verification status) -->
                        <?php
                            if ($isVerified == 0) {
                        ?>
                        
                            <hr class="my-4">

                            <h5 class="mb-3">Upload ID Document</h5>

                            <!-- Upload requirements and privacy notice -->
                            <div class="alert alert-light" role="alert">
                                <small>
                                    <strong>Accepted Documents:</strong> Government-issued ID, Driver's License, Passport<br>
                                    <strong>Privacy:</strong> Your document is only visible to administrators<br>
                                    <strong>File Format:</strong> JPG, PNG, or PDF (maximum 5MB)
                                </small>
                            </div>

                            <!-- NOTE: enctype="multipart/form-data" is mandatory for file uploads in HTML forms -->
                            <form method="POST" action="" enctype="multipart/form-data">
                                
                                <div class="mb-3">
                                    <label for="verification_document" class="form-label">Select ID Document</label>
                                    
                                    <!-- Client-side file validation via accept attribute -->
                                    <input 
                                        type="file" 
                                        id="verification_document" 
                                        name="verification_document" 
                                        class="form-control" 
                                        accept=".jpg, .jpeg, .png, .pdf"
                                        required
                                    >
                                    
                                    <small class="text-muted d-block mt-1">
                                        JPG, PNG, or PDF format. Maximum file size: 5MB
                                    </small>
                                </div>

                                <!-- Full-width button using Bootstrap d-grid utility class -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        Upload ID Document
                                    </button>
                                </div>
                            </form>

                        <?php
                            } else {
                                // Display confirmation message for verified users
                                echo "<div class=\"alert alert-success text-center\" role=\"alert\">";
                                echo "  <p class=\"mb-0\">Your account is fully verified! No further action is needed.</p>";
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