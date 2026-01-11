<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

// Validate user_id parameter from URL query string
if (!isset($_GET['user_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

// NOTE: intval() provides type coercion to prevent type juggling vulnerabilities
$userToVerifyID = intval($_GET['user_id']);

$userToVerify = null;
$message = '';
$messageType = '';

try {
    // Retrieve user record including verification document path and current status
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            verification_document_path,
            is_verified
        FROM users
        WHERE user_id = :userID
    ";
    
    // NOTE: PDO prepared statements with bound parameters prevent SQL Injection attacks
    // by separating SQL logic from user data
    $userStatement = $connection->prepare($userQuery);
    $userStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
    $userStatement->execute();
    
    $userToVerify = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    // Validate user existence and document submission before proceeding
    if (!$userToVerify) {
        header('Location: admin-dashboard.php');
        exit();
    }
    
    if (empty($userToVerify['verification_document_path'])) {
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    // NOTE: PDOException provides detailed error information for debugging while
    // allowing graceful error handling in production
    $message = "Database error: " . $error->getMessage();
    $messageType = 'danger';
}

// Process form submission for verification approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['approve'])) {
        try {
            // Set is_verified flag to grant user verified status
            $approveQuery = "
                UPDATE users
                SET is_verified = 1
                WHERE user_id = :userID
            ";
            
            // NOTE: Prepared statements protect against SQL Injection by treating user input as data
            $approveStatement = $connection->prepare($approveQuery);
            $approveStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
            $approveStatement->execute();
            
            $message = "User verified successfully!";
            $messageType = 'success';
            
            // Update local array to reflect database change immediately
            $userToVerify['is_verified'] = 1;
            
        } catch (PDOException $error) {
            $message = "Failed to verify user: " . $error->getMessage();
            $messageType = 'danger';
        }
        
    } else if (isset($_POST['reject'])) {
        try {
            // Reset verification status and remove document path to enable resubmission
            $rejectQuery = "
                UPDATE users
                SET 
                    is_verified = 0,
                    verification_document_path = NULL
                WHERE user_id = :userID
            ";
            
            $rejectStatement = $connection->prepare($rejectQuery);
            $rejectStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
            $rejectStatement->execute();
            
            // Redirect to dashboard after successful rejection
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            $message = "Failed to reject verification: " . $error->getMessage();
            $messageType = 'danger';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify User - Admin Panel</title>
</head>
<body>
    <!-- Main Content Container: mt-4 provides top margin spacing -->
    <div class="container mt-4">
        
        <!-- Navigation Section -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
                    Back to Admin Dashboard
                </a>
            </div>
        </div>

        <!-- Alert Message Display Section -->
        
        <?php
            // Display feedback message with contextual Bootstrap styling
            if (!empty($message)) {
                echo "<div class=\"alert alert-" . $messageType . "\" role=\"alert\">";
                echo htmlspecialchars($message);
                echo "</div>";
            }
        ?>

        <!-- Verification Review Card: Primary content section -->
        
        <div class="row mb-5">
            <!-- Responsive layout: full-width on mobile (col-12), centered 10-column layout on desktop (col-md-10 offset-md-1) -->
            <div class="col-12 col-md-10 offset-md-1">
                
                <div class="card shadow">
                    
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Review Verification Document</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- User Information Display -->
                        
                        <div class="mb-4">
                            <h5 class="mb-3">User Information</h5>
                            
                            <ul class="list-unstyled">
                                <li><strong>Username:</strong> <?php echo htmlspecialchars($userToVerify['username']); ?></li>
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($userToVerify['email']); ?></li>
                                <li><strong>User ID:</strong> <?php echo htmlspecialchars($userToVerify['user_id']); ?></li>
                                <li>
                                    <strong>Current Status:</strong>
                                    <?php
                                        // Display verification status with contextual badge styling
                                        if ($userToVerify['is_verified'] == 1) {
                                            echo "<span class=\"badge bg-success\">Verified</span>";
                                        } else {
                                            echo "<span class=\"badge bg-warning\">Not Verified</span>";
                                        }
                                    ?>
                                </li>
                            </ul>
                        </div>

                        <hr class="my-4">

                        <!-- Verification Document Preview Section -->
                        
                        <div class="mb-4">
                            <h5 class="mb-3">Uploaded Verification Document</h5>
                            
                            <div class="text-center">
                                <?php
                                    $documentPath = $userToVerify['verification_document_path'];
                                    
                                    // NOTE: pathinfo() extracts file extension to determine MIME type handling
                                    // This approach supports multiple document formats (images and PDFs)
                                    $fileExtension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
                                    
                                    // Render image files inline using responsive Bootstrap classes
                                    if ($fileExtension === 'jpg' || $fileExtension === 'jpeg' || $fileExtension === 'png') {
                                        // img-fluid ensures responsive scaling; max-width prevents oversized display
                                        echo "<img src=\"../" . htmlspecialchars($documentPath) . "\" alt=\"Verification Document\" class=\"img-fluid border mb-3\" style=\"max-width: 600px;\">";
                                        
                                    } else if ($fileExtension === 'pdf') {
                                        // PDF documents require download due to browser compatibility limitations
                                        echo "<div class=\"alert alert-info\" role=\"alert\">";
                                        echo "  <p class=\"mb-2\">This is a PDF document. Click the button below to download and view it.</p>";
                                        echo "  <a href=\"../" . htmlspecialchars($documentPath) . "\" class=\"btn btn-primary\" download>";
                                        echo "    Download PDF Document";
                                        echo "  </a>";
                                        echo "</div>";
                                    } else {
                                        echo "<div class=\"alert alert-warning\" role=\"alert\">";
                                        echo "  Unknown file type. Cannot display.";
                                        echo "</div>";
                                    }
                                ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Admin Action Controls -->
                        
                        <?php
                            // Conditional rendering: display action controls only for unverified users
                            if ($userToVerify['is_verified'] == 0) {
                        ?>
                        
                            <div class="mb-3">
                                <h5 class="mb-3">Admin Action</h5>
                                
                                <div class="alert alert-light" role="alert">
                                    <strong>Approve:</strong> This will mark the user as verified.<br>
                                    <strong>Reject:</strong> This will remove the document and allow the user to upload a new one.
                                </div>

                                <!-- NOTE: Separate forms enable server-side action detection via $_POST array keys
                                     without requiring JavaScript, ensuring functionality in all browsers -->
                                
                                <!-- Bootstrap grid with responsive columns: stacked on mobile (col-12), side-by-side on desktop (col-md-6) -->
                                <div class="row g-2">
                                    
                                    <div class="col-12 col-md-6">
                                        <form method="POST" action="">
                                            <!-- d-grid creates full-width button within column -->
                                            <div class="d-grid">
                                                <button type="submit" name="approve" class="btn btn-success btn-lg">
                                                    Approve Verification
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <form method="POST" action="">
                                            <div class="d-grid">
                                                <button type="submit" name="reject" class="btn btn-danger btn-lg">
                                                    Reject Verification
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php
                            } else {
                                echo "<div class=\"alert alert-success text-center\" role=\"alert\">";
                                echo "  This user is already verified. No action needed.";
                                echo "</div>";
                            }
                        ?>

                    </div>
                </div>
            </div>
        </div>
        <!-- End Verification Review Card -->

    </div>
    <!-- End Main Content Container -->
</body>
</html>
