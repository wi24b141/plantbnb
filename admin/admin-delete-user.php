<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 3: GET USER ID FROM URL
// ============================================

// The admin clicked a link like: admin-delete-user.php?user_id=5
// We get the user_id from the URL
if (!isset($_GET['user_id'])) {
    // No user_id in URL - go back to dashboard
    header('Location: admin-dashboard.php');
    exit();
}

// Get the user ID and convert to integer
$userToDeleteID = intval($_GET['user_id']);

// ============================================
// STEP 4: PREVENT ADMIN FROM DELETING THEMSELVES
// ============================================

// An admin should not be able to delete their own account
// This would lock them out of the admin panel
if ($userToDeleteID === $currentUserID) {
    // Admin is trying to delete themselves - redirect with error
    header('Location: admin-dashboard.php');
    exit();
}

// ============================================
// STEP 5: FETCH USER DATA
// ============================================

// We need to get the user's information to show it on the confirmation page
$userToDelete = null;

// This will store error messages
$errorMessage = '';

try {
    // Query to get the user's basic information
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            role
        FROM users
        WHERE user_id = :userID
    ";
    
    // Prepare the query
    $userStatement = $connection->prepare($userQuery);
    
    // Bind the parameter
    $userStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
    
    // Execute the query
    $userStatement->execute();
    
    // Get the result
    $userToDelete = $userStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if user was found
    if (!$userToDelete) {
        // User not found - redirect back
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    // Database error
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 6: HANDLE FORM SUBMISSION (DELETE USER)
// ============================================

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the confirm button was clicked
    if (isset($_POST['confirm_delete'])) {
        // Admin confirmed deletion
        
        try {
            // When we delete a user, we need to delete all their related data
            // This is called "cascading delete"
            // We delete in this order:
            // 1. User's messages (both sent and received)
            // 2. User's favorites
            // 3. User's plants (linked to their listings)
            // 4. User's listings
            // 5. Finally, the user account itself
            
            // DELETE STEP 1: Delete all messages where user is sender or receiver
            $deleteMessagesQuery = "
                DELETE FROM messages
                WHERE sender_id = :userID OR receiver_id = :userID
            ";
            $deleteMessagesStatement = $connection->prepare($deleteMessagesQuery);
            $deleteMessagesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteMessagesStatement->execute();
            
            // DELETE STEP 2: Delete all favorites created by this user
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE user_id = :userID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            // DELETE STEP 3: Get all listing IDs that belong to this user
            // We need these IDs to delete the plants table entries
            $getUserListingsQuery = "
                SELECT listing_id FROM listings WHERE user_id = :userID
            ";
            $getUserListingsStatement = $connection->prepare($getUserListingsQuery);
            $getUserListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $getUserListingsStatement->execute();
            $userListings = $getUserListingsStatement->fetchAll(PDO::FETCH_ASSOC);
            
            // DELETE STEP 4: Delete plants for each of the user's listings
            // We loop through each listing and delete its plants
            foreach ($userListings as $listing) {
                $deletePlantsQuery = "
                    DELETE FROM plants
                    WHERE listing_id = :listingID
                ";
                $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
                $deletePlantsStatement->bindParam(':listingID', $listing['listing_id'], PDO::PARAM_INT);
                $deletePlantsStatement->execute();
            }
            
            // DELETE STEP 5: Delete all listings by this user
            $deleteListingsQuery = "
                DELETE FROM listings
                WHERE user_id = :userID
            ";
            $deleteListingsStatement = $connection->prepare($deleteListingsQuery);
            $deleteListingsStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteListingsStatement->execute();
            
            // DELETE STEP 6: Finally, delete the user account itself
            $deleteUserQuery = "
                DELETE FROM users
                WHERE user_id = :userID
            ";
            $deleteUserStatement = $connection->prepare($deleteUserQuery);
            $deleteUserStatement->bindParam(':userID', $userToDeleteID, PDO::PARAM_INT);
            $deleteUserStatement->execute();
            
            // Success! User and all related data deleted
            // Redirect back to admin dashboard
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            // Database error during deletion
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
            // Show error message if there is one
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
                                            // Show role with colored badge
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
