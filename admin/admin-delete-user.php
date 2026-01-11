<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['user_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

$userToDeleteID = intval($_GET['user_id']);

if ($userToDeleteID === $currentUserID) {
    header('Location: admin-dashboard.php');
    exit();
}

$userToDelete = null;

$errorMessage = '';

try {
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            role
        FROM users
        WHERE user_id = :userID
    ";
    
    $userStatement = $connection->prepare($userQuery);
    
    $userStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
    
    $userStatement->execute();
    
    $userToDelete = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$userToDelete) {
        
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    
    $errorMessage = "Database error: " . $error->getMessage();
}






if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (isset($_POST['confirm_delete'])) {
        
        
        try {
            
            
            
            
            
            
            
            
            
            
            $deleteMessagesQuery = "
                DELETE FROM messages
                WHERE sender_id = :userID OR receiver_id = :userID
            ";
            $deleteMessagesStatement = $connection->prepare($deleteMessagesQuery);
            $deleteMessagesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteMessagesStatement->execute();
            
            
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE user_id = :userID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            
            
            $getUserListingsQuery = "
                SELECT listing_id FROM listings WHERE user_id = :userID
            ";
            $getUserListingsStatement = $connection->prepare($getUserListingsQuery);
            $getUserListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $getUserListingsStatement->execute();
            $userListings = $getUserListingsStatement->fetchAll(PDO::FETCH_ASSOC);
            
            
            
            foreach ($userListings as $listing) {
                $deletePlantsQuery = "
                    DELETE FROM plants
                    WHERE listing_id = :listingID
                ";
                $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
                $deletePlantsStatement->bindParam(':listingID', $listing['listing_id'], PDO::PARAM_INT);
                $deletePlantsStatement->execute();
            }
            
            
            $deleteListingsQuery = "
                DELETE FROM listings
                WHERE user_id = :userID
            ";
            $deleteListingsStatement = $connection->prepare($deleteListingsQuery);
            $deleteListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteListingsStatement->execute();
            
            
            $deleteUserQuery = "
                DELETE FROM users
                WHERE user_id = :userID
            ";
            $deleteUserStatement = $connection->prepare($deleteUserQuery);
            $deleteUserStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteUserStatement->execute();
            
            
            
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            
            $errorMessage = "Failed to delete user: " . $error->getMessage();
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
    <title>Delete User - Admin Panel</title>
</head>
<body>
    <!-- Container -->
    <div class="container mt-4">
        
        <!-- ============================================ -->
        <!-- SECTION 1: BACK BUTTON -->
        <!-- ============================================ -->
        
        <div class="row mb-3">
            <div class="col-12">
                <a href="admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
                    Back to Admin Dashboard
                </a>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 2: ERROR MESSAGE (IF ANY) -->
        <!-- ============================================ -->
        
        <?php
            
            if (!empty($errorMessage)) {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- ============================================ -->
        <!-- SECTION 3: CONFIRMATION CARD -->
        <!-- ============================================ -->
        
        <div class="row mb-5">
            <!-- col-12 = full width on mobile -->
            <!-- col-md-8 = 8/12 width on desktop -->
            <!-- offset-md-2 = center on desktop -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- card = Bootstrap box component -->
                <div class="card shadow">
                    
                    <!-- Card header with red background (danger color) -->
                    <!-- Red indicates this is a dangerous action -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Delete User Account</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- ============================================ -->
                        <!-- SUBSECTION 3A: WARNING MESSAGE -->
                        <!-- ============================================ -->
                        
                        <!-- alert-warning = yellow/orange warning box -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">Warning: This action cannot be undone!</h5>
                            <p class="mb-0">
                                Deleting this user will permanently remove:
                            </p>
                            <ul class="mt-2 mb-0">
                                <li>The user account</li>
                                <li>All their listings</li>
                                <li>All their messages</li>
                                <li>All their favorites</li>
                            </ul>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3B: USER INFORMATION -->
                        <!-- ============================================ -->
                        
                        <div class="my-4">
                            <h5 class="mb-3">User to Delete:</h5>
                            
                            <!-- bg-light = light gray background -->
                            <!-- p-3 = padding all around -->
                            <!-- rounded = rounded corners -->
                            <div class="bg-light p-3 rounded">
                                <!-- list-unstyled = removes bullet points from list -->
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Username:</strong> <?php echo htmlspecialchars($userToDelete['username']); ?></li>
                                    <li><strong>Email:</strong> <?php echo htmlspecialchars($userToDelete['email']); ?></li>
                                    <li><strong>User ID:</strong> <?php echo htmlspecialchars($userToDelete['user_id']); ?></li>
                                    <li>
                                        <strong>Role:</strong>
                                        <?php
                                            
                                            if ($userToDelete['role'] === 'admin') {
                                                echo "<span class=\"badge bg-danger\">Admin</span>";
                                            } else {
                                                echo "<span class=\"badge bg-secondary\">User</span>";
                                            }
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3C: CONFIRMATION BUTTONS -->
                        <!-- ============================================ -->
                        
                        <div class="row g-2">
                            
                            <!-- Cancel button column -->
                            <!-- col-12 = full width on mobile (buttons stack) -->
                            <!-- col-md-6 = half width on desktop (side by side) -->
                            <div class="col-12 col-md-6">
                                <!-- Link to go back without deleting -->
                                <!-- d-grid makes link full width of its column -->
                                <div class="d-grid">
                                    <a href="admin-dashboard.php" class="btn btn-secondary btn-lg">
                                        Cancel
                                    </a>
                                </div>
                            </div>

                            <!-- Delete button column -->
                            <div class="col-12 col-md-6">
                                <!-- Form to submit the deletion -->
                                <form method="POST" action="">
                                    <div class="d-grid">
                                        <!-- name="confirm_delete" = how we detect button click in PHP -->
                                        <!-- btn-danger = red button -->
                                        <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                                            Confirm Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
