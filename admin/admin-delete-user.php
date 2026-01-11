<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

// Validate that a user_id was provided in the URL
if (!isset($_GET['user_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

// NOTE: intval() sanitizes the input by converting to integer, preventing injection attacks
$userToDeleteID = intval($_GET['user_id']);

// Security check: Prevent admin from deleting their own account
if ($userToDeleteID === $currentUserID) {
    header('Location: admin-dashboard.php');
    exit();
}

$userToDelete = null;

$errorMessage = '';

// Query database to retrieve user information before deletion
try {
    // Retrieve user details to display on confirmation page
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            role
        FROM users
        WHERE user_id = :userID
    ";
    
    // NOTE: prepare() creates a prepared statement, protecting against SQL Injection attacks
    $userStatement = $connection->prepare($userQuery);
    
    // NOTE: bindParam() binds the variable to the placeholder, enforcing type safety with PDO::PARAM_INT
    $userStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
    
    $userStatement->execute();
    
    // Fetch user data as associative array
    $userToDelete = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    if (!$userToDelete) {
        // User does not exist - redirect to prevent further processing
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    // Handle database exceptions by storing error message for display
    $errorMessage = "Database error: " . $error->getMessage();
}

/**
 * Process user deletion request
 * 
 * Implements cascading deletion to maintain referential integrity by removing
 * all associated records before deleting the user account.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['confirm_delete'])) {
        
        try {
            // NOTE: Cascading delete maintains referential integrity by removing dependent records first.
            // This prevents orphaned records and foreign key constraint violations.
            // Order matters: delete child records before parent records.
            
            // Step 1: Delete messages (user is either sender or receiver)
            $deleteMessagesQuery = "
                DELETE FROM messages
                WHERE sender_id = :userID OR receiver_id = :userID
            ";
            // NOTE: Prepared statements protect against SQL Injection by separating SQL logic from data
            $deleteMessagesStatement = $connection->prepare($deleteMessagesQuery);
            $deleteMessagesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteMessagesStatement->execute();
            
            // Step 2: Delete favorite listings marked by this user
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE user_id = :userID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            // Step 3: Retrieve listing IDs owned by user (needed for plant deletion)
            $getUserListingsQuery = "
                SELECT listing_id FROM listings WHERE user_id = :userID
            ";
            $getUserListingsStatement = $connection->prepare($getUserListingsQuery);
            $getUserListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $getUserListingsStatement->execute();
            $userListings = $getUserListingsStatement->fetchAll(PDO::FETCH_ASSOC);
            
            // Step 4: Delete plants associated with each listing
            // NOTE: This demonstrates understanding of many-to-one relationships (plants → listings)
            foreach ($userListings as $listing) {
                $deletePlantsQuery = "
                    DELETE FROM plants
                    WHERE listing_id = :listingID
                ";
                $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
                $deletePlantsStatement->bindParam(':listingID', $listing['listing_id'], PDO::PARAM_INT);
                $deletePlantsStatement->execute();
            }
            
            // Step 5: Delete all listings owned by the user
            $deleteListingsQuery = "
                DELETE FROM listings
                WHERE user_id = :userID
            ";
            $deleteListingsStatement = $connection->prepare($deleteListingsQuery);
            $deleteListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteListingsStatement->execute();
            
            // Step 6: Finally, delete the user account
            $deleteUserQuery = "
                DELETE FROM users
                WHERE user_id = :userID
            ";
            $deleteUserStatement = $connection->prepare($deleteUserQuery);
            $deleteUserStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteUserStatement->execute();
            
            // Redirect to dashboard upon successful deletion
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            // NOTE: PDOException handling prevents application crashes and provides user feedback
            $errorMessage = "Failed to delete user: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - Admin Panel</title>
</head>
<body>
    <!-- Main container: mt-4 applies top margin for spacing from navbar -->
    <div class="container mt-4">
        
        <!-- Navigation: Back button -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
                    Back to Admin Dashboard
                </a>
            </div>
        </div>

        <!-- Error display section -->
        <?php
            // Display error alert if database operation failed
            // NOTE: htmlspecialchars() prevents XSS attacks by escaping HTML special characters
            if (!empty($errorMessage)) {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Deletion confirmation interface -->
        <div class="row mb-5">
            <!-- Uses Bootstrap grid: col-md-8 offset-md-2 centers the card on medium+ screens -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <div class="card shadow">
                    
                    <!-- bg-danger indicates destructive action requiring careful consideration -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Delete User Account</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- Warning: Informs admin of irreversible consequences -->
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

                        <!-- User details display -->
                        <div class="my-4">
                            <h5 class="mb-3">User to Delete:</h5>
                            
                            <!-- bg-light p-3 rounded: Creates visually distinct information box -->
                            <div class="bg-light p-3 rounded">
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Username:</strong> <?php echo htmlspecialchars($userToDelete['username']); ?></li>
                                    <li><strong>Email:</strong> <?php echo htmlspecialchars($userToDelete['email']); ?></li>
                                    <li><strong>User ID:</strong> <?php echo htmlspecialchars($userToDelete['user_id']); ?></li>
                                    <li>
                                        <strong>Role:</strong>
                                        <?php
                                            // Display role with Bootstrap badge component for visual distinction
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

                        <!-- Action buttons: Cancel and Confirm -->
                        <div class="row g-2">
                            
                            <!-- Cancel option: col-md-6 creates responsive two-column layout on desktop -->
                            <div class="col-12 col-md-6">
                                <!-- d-grid makes button expand to full column width -->
                                <div class="d-grid">
                                    <a href="admin-dashboard.php" class="btn btn-secondary btn-lg">
                                        Cancel
                                    </a>
                                </div>
                            </div>

                            <!-- Delete confirmation -->
                            <div class="col-12 col-md-6">
                                <!-- POST method ensures idempotency and prevents accidental deletion via URL -->
                                <form method="POST" action="">
                                    <div class="d-grid">
                                        <!-- NOTE: Using named submit button allows detection via isset($_POST['confirm_delete']) -->
                                        <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                                            Confirm Delete
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                    <!-- End card-body -->
                </div>
                <!-- End card -->
            </div>
        </div>

    </div>
    <!-- End container -->
</body>
</html>
