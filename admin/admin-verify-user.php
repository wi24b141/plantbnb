<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';










if (!isset($_GET['user_id'])) {
    
    header('Location: admin-dashboard.php');
    exit();
}



$userToVerifyID = intval($_GET['user_id']);






$userToVerify = null;


$message = '';
$messageType = ''; 

try {
    
    
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
    
    
    $userStatement = $connection->prepare($userQuery);
    
    
    $userStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
    
    
    $userStatement->execute();
    
    
    $userToVerify = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$userToVerify) {
        
        header('Location: admin-dashboard.php');
        exit();
    }
    
    
    if (empty($userToVerify['verification_document_path'])) {
        
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    
    $message = "Database error: " . $error->getMessage();
    $messageType = 'danger';
}








if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    
    if (isset($_POST['approve'])) {
        
        
        try {
            
            
            $approveQuery = "
                UPDATE users
                SET is_verified = 1
                WHERE user_id = :userID
            ";
            
            
            $approveStatement = $connection->prepare($approveQuery);
            
            
            $approveStatement->bindParam(':userID', $userToVerifyID, PDO::PARAM_INT);
            
            
            $approveStatement->execute();
            
            
            $message = "User verified successfully!";
            $messageType = 'success';
            
            
            $userToVerify['is_verified'] = 1;
            
        } catch (PDOException $error) {
            
            $message = "Failed to verify user: " . $error->getMessage();
            $messageType = 'danger';
        }
        
    } else if (isset($_POST['reject'])) {
        
        
        try {
            
            
            
            
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
            
            if (!empty($message)) {
                
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
                                    
                                    $documentPath = $userToVerify['verification_document_path'];
                                    
                                    
                                    
                                    
                                    $fileExtension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
                                    
                                    
                                    if ($fileExtension === 'jpg' || $fileExtension === 'jpeg' || $fileExtension === 'png') {
                                        
                                        
                                        
                                        
                                        
                                        echo "<img src=\"../" . htmlspecialchars($documentPath) . "\" alt=\"Verification Document\" class=\"img-fluid border mb-3\" style=\"max-width: 600px;\">";
                                        
                                    } else if ($fileExtension === 'pdf') {
                                        
                                        
                                        
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

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3C: APPROVAL BUTTONS -->
                        <!-- ============================================ -->
                        
                        <?php
                            
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
