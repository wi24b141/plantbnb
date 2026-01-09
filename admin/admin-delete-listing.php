<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 3: GET LISTING ID FROM URL
// ============================================

// The admin clicked a link like: admin-delete-listing.php?listing_id=5
// We get the listing_id from the URL
if (!isset($_GET['listing_id'])) {
    // No listing_id in URL - go back to dashboard
    header('Location: admin-dashboard.php');
    exit();
}

// Get the listing ID and convert to integer
$listingToDeleteID = intval($_GET['listing_id']);

// ============================================
// STEP 4: FETCH LISTING DATA
// ============================================

// We need to get the listing's information to show on the confirmation page
$listingToDelete = null;

// This will store error messages
$errorMessage = '';

try {
    // Query to get the listing information
    // We also get the username of who created it
    $listingQuery = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.listing_type,
            l.status,
            l.created_at,
            u.username
        FROM listings l
        INNER JOIN users u ON l.user_id = u.user_id
        WHERE l.listing_id = :listingID
    ";
    
    // Prepare the query
    $listingStatement = $connection->prepare($listingQuery);
    
    // Bind the parameter
    $listingStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
    
    // Execute the query
    $listingStatement->execute();
    
    // Get the result
    $listingToDelete = $listingStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if listing was found
    if (!$listingToDelete) {
        // Listing not found - redirect back
        header('Location: admin-dashboard.php');
        exit();
    }
    
} catch (PDOException $error) {
    // Database error
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 5: HANDLE FORM SUBMISSION (DELETE LISTING)
// ============================================

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the confirm button was clicked
    if (isset($_POST['confirm_delete'])) {
        // Admin confirmed deletion
        
        try {
            // When we delete a listing, we need to delete related data
            // We delete in this order:
            // 1. Favorites that point to this listing
            // 2. Plants associated with this listing
            // 3. The listing itself
            
            // DELETE STEP 1: Delete all favorites for this listing
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE listing_id = :listingID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            // DELETE STEP 2: Delete all plants for this listing
            $deletePlantsQuery = "
                DELETE FROM plants
                WHERE listing_id = :listingID
            ";
            $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
            $deletePlantsStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deletePlantsStatement->execute();
            
            // DELETE STEP 3: Delete the listing itself
            $deleteListingQuery = "
                DELETE FROM listings
                WHERE listing_id = :listingID
            ";
            $deleteListingStatement = $connection->prepare($deleteListingQuery);
            $deleteListingStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deleteListingStatement->execute();
            
            // Success! Listing and all related data deleted
            // Redirect back to admin dashboard
            header('Location: admin-dashboard.php');
            exit();
            
        } catch (PDOException $error) {
            // Database error during deletion
            $errorMessage = "Failed to delete listing: " . $error->getMessage();
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
    <title>Delete Listing - Admin Panel</title>
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
                    
                    <!-- Card header with red background -->
                    <!-- Red indicates this is a dangerous action -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Delete Listing</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- ============================================ -->
                        <!-- SUBSECTION 3A: WARNING MESSAGE -->
                        <!-- ============================================ -->
                        
                        <!-- alert-warning = yellow/orange warning box -->
                        <div class="alert alert-warning" role="alert">
                            <h5 class="alert-heading">Warning: This action cannot be undone!</h5>
                            <p class="mb-0">
                                Deleting this listing will permanently remove:
                            </p>
                            <ul class="mt-2 mb-0">
                                <li>The listing itself</li>
                                <li>All plant information associated with it</li>
                                <li>All favorites pointing to this listing</li>
                            </ul>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3B: LISTING INFORMATION -->
                        <!-- ============================================ -->
                        
                        <div class="my-4">
                            <h5 class="mb-3">Listing to Delete:</h5>
                            
                            <!-- bg-light = light gray background -->
                            <!-- p-3 = padding all around -->
                            <!-- rounded = rounded corners -->
                            <div class="bg-light p-3 rounded">
                                <!-- list-unstyled = removes bullet points -->
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Title:</strong> <?php echo htmlspecialchars($listingToDelete['title']); ?></li>
                                    <li><strong>Description:</strong> <?php echo htmlspecialchars($listingToDelete['description']); ?></li>
                                    <li><strong>Listing ID:</strong> <?php echo htmlspecialchars($listingToDelete['listing_id']); ?></li>
                                    <li><strong>Created By:</strong> <?php echo htmlspecialchars($listingToDelete['username']); ?></li>
                                    <li>
                                        <strong>Type:</strong>
                                        <?php
                                            // Show type with colored badge
                                            if ($listingToDelete['listing_type'] === 'offer') {
                                                echo "<span class=\"badge bg-primary\">Offer</span>";
                                            } else {
                                                echo "<span class=\"badge bg-warning\">Need</span>";
                                            }
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Status:</strong>
                                        <?php
                                            // Show status with colored badge
                                            if ($listingToDelete['status'] === 'active') {
                                                echo "<span class=\"badge bg-success\">Active</span>";
                                            } else if ($listingToDelete['status'] === 'inactive') {
                                                echo "<span class=\"badge bg-secondary\">Inactive</span>";
                                            } else {
                                                echo "<span class=\"badge bg-info\">Completed</span>";
                                            }
                                        ?>
                                    </li>
                                    <li><strong>Created:</strong> <?php echo date('M d, Y', strtotime($listingToDelete['created_at'])); ?></li>
                                </ul>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3C: CONFIRMATION BUTTONS -->
                        <!-- ============================================ -->
                        
                        <div class="row g-2">
                            
                            <!-- Cancel button column -->
                            <!-- col-12 = full width on mobile (buttons stack vertically) -->
                            <!-- col-md-6 = half width on desktop (buttons side by side) -->
                            <div class="col-12 col-md-6">
                                <!-- Link to go back without deleting -->
                                <!-- d-grid makes link full width -->
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
                                        <!-- btn-danger = red button (indicates dangerous action) -->
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
