<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 3: GET USER ID FROM URL
// ============================================

// The admin clicked a link like: admin-verify-user.php?user_id=5
// We need to get that user_id from the URL
// $_GET is an array that contains URL parameters

// Check if user_id exists in the URL
if (!isset($_GET['user_id'])) {
    // No user_id in URL - send admin back to dashboard
    header('Location: admin-dashboard.php');
    exit();
}

// Get the user ID from the URL and convert to integer
// intval() converts to integer (security measure)
$userToVerifyID = intval($_GET['user_id']);

// ============================================
// STEP 4: FETCH USER DATA FROM DATABASE
// ============================================

// This variable will store the user's information
$userToVerify = null;

// This will store success or error messages
$message = '';
$messageType = ''; // Will be 'success' or 'danger'

try {
    // Query to get the user's information
    // We need username, email, and the verification document path
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
    
    // Prepare the query to prevent SQL injection
    $userStatement = $connection->prepare($userQuery);
    
    // Bind the parameter
    $userStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
    
    // Execute the query
    $userStatement->execute();
    
    // Get the result
    $userToVerify = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if user was found
    if (!$userToVerify) {
        // User not found - redirect back to dashboard
        header('Location: admin-dashboard.php');
        exit();
    }
    
    // Check if user has a verification document
    if (empty($userToVerify['verification_document_path'])) {
        // No verification document - redirect back
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    // Database error - show error message
    $message = "Database error: " . $error->getMessage();
    $messageType = 'danger';
}

// ============================================
// STEP 5: HANDLE FORM SUBMISSION
// ============================================

// Check if the form was submitted
// The form has two buttons: "Approve" and "Reject"
// We check which button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the approve button was clicked
    // isset() checks if a variable exists
    if (isset($_POST['approve'])) {
        // Admin clicked "Approve" button
        
        try {
            // Update the database to set is_verified = 1
            // This marks the user as verified
            $approveQuery = "
                UPDATE users
                SET is_verified = 1
                WHERE user_id = :userID
            ";
            
            // Prepare the query
            $approveStatement = $connection->prepare($approveQuery);
            
            // Bind the parameter
            $approveStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
            
            // Execute the query
            $approveStatement->execute();
            
            // Success! Show success message
            $message = "User verified successfully!";
            $messageType = 'success';
            
            // Update the local variable so the page shows updated status
            $userToVerify['is_verified'] = 1;
            
        } catch (PDOException $error) {
            // Database error
            $message = "Failed to verify user: " . $error->getMessage();
            $messageType = 'danger';
        }
        
    } else if (isset($_POST['reject'])) {
        // Admin clicked "Reject" button
        
        try {
            // We reject by:
            // 1. Setting is_verified = 0 (not verified)
            // 2. Removing the verification document path
            // This allows the user to upload a new document
            $rejectQuery = "
                UPDATE users
                SET 
                    is_verified = 0,
                    verification_document_path = NULL
                WHERE user_id = :userID
            ";
            
            // Prepare the query
            $rejectStatement = $connection->prepare($rejectQuery);
            
            // Bind the parameter
            $rejectStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
            
            // Execute the query
            $rejectStatement->execute();
            
            // Success! Redirect back to dashboard
            // The user will no longer appear in pending verifications
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            // Database error
            $message = "Failed to reject verification: " . $error->getMessage();
            $messageType = 'danger';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding -->
    <meta charset="UTF-8">
    
    <!-- Mobile-friendly viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title -->
    <title>Verify User - Admin Panel</title>
</head>
<body>
    <!-- Container centers content -->
    <div class="container mt-4">
        
        <!-- ============================================ -->
        <!-- SECTION 1: BACK BUTTON -->
        <!-- ============================================ -->
        
        <div class="row mb-3">
            <div class="col-12">
                <!-- Link back to admin dashboard -->
                <a href="admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
                    Back to Admin Dashboard
                </a>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 2: SUCCESS/ERROR MESSAGE -->
        <!-- ============================================ -->
        
        <?php
            // Show message if there is one
            if (!empty($message)) {
                // $messageType is either 'success' (green) or 'danger' (red)
                echo "<div class=\"alert alert-" . $messageType . "\" role=\"alert\">";
                echo htmlspecialchars($message);
                echo "</div>";
            }
        ?>

        <!-- ============================================ -->
        <!-- SECTION 3: MAIN VERIFICATION CARD -->
        <!-- ============================================ -->
        
        <div class="row mb-5">
            <!-- col-12 = full width on mobile -->
            <!-- col-md-10 = 10/12 width on desktop -->
            <!-- offset-md-1 = center on desktop -->
            <div class="col-12 col-md-10 offset-md-1">
                
                <div class="card shadow">
                    
                    <!-- Card header -->
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Review Verification Document</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- ============================================ -->
                        <!-- SUBSECTION 3A: USER INFORMATION -->
                        <!-- ============================================ -->
                        
                        <div class="mb-4">
                            <h5 class="mb-3">User Information</h5>
                            
                            <!-- We display user info in a list -->
                            <!-- list-unstyled = removes bullet points -->
                            <ul class="list-unstyled">
                                <li><strong>Username:</strong> <?php echo htmlspecialchars($userToVerify['username']); ?></li>
                                <li><strong>Email:</strong> <?php echo htmlspecialchars($userToVerify['email']); ?></li>
                                <li><strong>User ID:</strong> <?php echo htmlspecialchars($userToVerify['user_id']); ?></li>
                                <li>
                                    <strong>Current Status:</strong>
                                    <?php
                                        // Show current verification status
                                        if ($userToVerify['is_verified'] == 1) {
                                            echo "<span class=\"badge bg-success\">Verified</span>";
                                        } else {
                                            echo "<span class=\"badge bg-warning\">Not Verified</span>";
                                        }
                                    ?>
                                </li>
                            </ul>
                        </div>

                        <!-- hr = horizontal line (divider) -->
                        <hr class="my-4">

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3B: VERIFICATION DOCUMENT -->
                        <!-- ============================================ -->
                        
                        <div class="mb-4">
                            <h5 class="mb-3">Uploaded Verification Document</h5>
                            
                            <!-- text-center = center content horizontally -->
                            <div class="text-center">
                                <?php
                                    // Get the file path
                                    $documentPath = $userToVerify['verification_document_path'];
                                    
                                    // Get the file extension to determine file type
                                    // pathinfo() gets information about a file path
                                    // PATHINFO_EXTENSION gets just the extension (jpg, png, pdf, etc.)
                                    $fileExtension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
                                    
                                    // Check if it's an image or PDF
                                    if ($fileExtension === 'jpg' || $fileExtension === 'jpeg' || $fileExtension === 'png') {
                                        // It's an image - display it
                                        // img-fluid = responsive image (scales to fit screen)
                                        // border = adds border around image
                                        // mb-3 = margin-bottom
                                        // ../ goes up one level from admin/ folder to reach uploads/
                                        echo "<img src=\"../" . htmlspecialchars($documentPath) . "\" alt=\"Verification Document\" class=\"img-fluid border mb-3\" style=\"max-width: 600px;\">";
                                        
                                    } else if ($fileExtension === 'pdf') {
                                        // It's a PDF - show a download link
                                        // We cannot display PDFs without JavaScript
                                        // ../ goes up one level from admin/ folder to reach uploads/
                                        echo "<div class=\"alert alert-info\" role=\"alert\">";
                                        echo "  <p class=\"mb-2\">This is a PDF document. Click the button below to download and view it.</p>";
                                        echo "  <a href=\"../" . htmlspecialchars($documentPath) . "\" class=\"btn btn-primary\" download>";
                                        echo "    Download PDF Document";
                                        echo "  </a>";
                                        echo "</div>";
                                    } else {
                                        // Unknown file type
                                        echo "<div class=\"alert alert-warning\" role=\"alert\">";
                                        echo "  Unknown file type. Cannot display.";
                                        echo "</div>";
                                    }
                                ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3C: APPROVAL BUTTONS -->
                        <!-- ============================================ -->
                        
                        <?php
                            // Only show buttons if user is not already verified
                            if ($userToVerify['is_verified'] == 0) {
                        ?>
                        
                            <div class="mb-3">
                                <h5 class="mb-3">Admin Action</h5>
                                
                                <!-- alert-light = light gray background -->
                                <div class="alert alert-light" role="alert">
                                    <strong>Approve:</strong> This will mark the user as verified.<br>
                                    <strong>Reject:</strong> This will remove the document and allow the user to upload a new one.
                                </div>

                                <!-- We create TWO separate forms -->
                                <!-- One form for approve, one form for reject -->
                                <!-- This is because we cannot use JavaScript to detect which button was clicked -->
                                
                                <!-- row = Bootstrap grid row -->
                                <!-- g-2 = gap (space between columns) -->
                                <div class="row g-2">
                                    
                                    <!-- Approve button column -->
                                    <!-- col-12 = full width on mobile (stacks vertically) -->
                                    <!-- col-md-6 = half width on desktop (side by side) -->
                                    <div class="col-12 col-md-6">
                                        <!-- Form for approving -->
                                        <form method="POST" action="">
                                            <!-- d-grid = makes button full width of its column -->
                                            <div class="d-grid">
                                                <!-- name="approve" = this is how we detect which button was clicked in PHP -->
                                                <button type="submit" name="approve" class="btn btn-success btn-lg">
                                                    Approve Verification
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Reject button column -->
                                    <div class="col-12 col-md-6">
                                        <!-- Form for rejecting -->
                                        <form method="POST" action="">
                                            <div class="d-grid">
                                                <!-- name="reject" = this is how we detect which button was clicked -->
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
                                // User is already verified
                                echo "<div class=\"alert alert-success text-center\" role=\"alert\">";
                                echo "  This user is already verified. No action needed.";
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
