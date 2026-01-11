<?php
// ============================================
// STEP 1: INCLUDE REQUIRED FILES
// ============================================

// Include the header which has Bootstrap CSS
require_once __DIR__ . '/../includes/header.php';

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Include user authentication (checks if user is logged in)
require_once __DIR__ . '/../includes/user-auth.php';

// ============================================
// STEP 2: CHECK IF USER IS ADMIN
// ============================================

// We need to know if the logged-in user is an admin
// We check the is_admin column in the database
$isUserAdmin = false;

try {
    // Query to check if current user is an admin
    $adminCheckQuery = "
        SELECT is_admin
        FROM users
        WHERE user_id = :userID
    ";
    
    // Prepare the query
    $adminCheckStatement = $connection->prepare($adminCheckQuery);
    
    // Bind the logged-in user's ID
    $adminCheckStatement->bindParam(':userID', $loggedInUserID, PDO::PARAM_INT);
    
    // Execute the query
    $adminCheckStatement->execute();
    
    // Get the result
    $adminCheckResult = $adminCheckStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is admin
    if ($adminCheckResult && $adminCheckResult['is_admin'] == 1) {
        // User is an admin
        $isUserAdmin = true;
    }
    
} catch (PDOException $error) {
    // Database error - we'll handle this later
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 3: GET LISTING ID FROM URL
// ============================================

// The user clicked a link like: listing-delete.php?listing_id=5
// We get the listing_id from the URL
if (!isset($_GET['listing_id'])) {
    // No listing_id in URL - go back to dashboard
    // If admin, go to admin dashboard, otherwise user dashboard
    if ($isUserAdmin) {
        header('Location: ../admin/admin-dashboard.php');
    } else {
        header('Location: ../users/dashboard.php');
    }
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
            l.user_id,
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
        if ($isUserAdmin) {
            header('Location: ../admin/admin-dashboard.php');
        } else {
            header('Location: ../users/dashboard.php');
        }
        exit();
    }
    
} catch (PDOException $error) {
    // Database error
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 5: CHECK PERMISSION TO DELETE
// ============================================

// A regular user can only delete their OWN listings
// An admin can delete ANY listing

// Check if the logged-in user owns this listing
$userOwnsListing = ($listingToDelete['user_id'] == $loggedInUserID);

// Check if user has permission to delete this listing
if (!$userOwnsListing && !$isUserAdmin) {
    // User does NOT own this listing AND is NOT an admin
    // Redirect back - user is not allowed to delete this listing
    header('Location: ../users/dashboard.php');
    exit();
}

// ============================================
// STEP 6: HANDLE FORM SUBMISSION (DELETE LISTING)
// ============================================

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the confirm button was clicked
    if (isset($_POST['confirm_delete'])) {
        // User confirmed deletion
        
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
            // Redirect back to appropriate dashboard
            if ($isUserAdmin) {
                // Admin - go to admin dashboard
                header('Location: ../admin/admin-dashboard.php');
            } else {
                // Regular user - go to user dashboard
                header('Location: ../users/dashboard.php');
            }
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
    <!-- width=device-width means use the device's width -->
    <!-- initial-scale=1.0 means don't zoom in or out -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title -->
    <title>Delete Listing - PlantBnB</title>
</head>
<body>
    <!-- Container for all content -->
    <!-- container = Bootstrap class that centers content and adds padding on sides -->
    <!-- mt-4 = margin-top with 4 spacing units -->
    <div class="container mt-4">
        
        <!-- ============================================ -->
        <!-- SECTION 1: BACK BUTTON -->
        <!-- ============================================ -->
        
        <!-- row = Bootstrap grid row -->
        <!-- mb-3 = margin-bottom with 3 spacing units -->
        <div class="row mb-3">
            <!-- col-12 = full width on all screen sizes -->
            <div class="col-12">
                <?php
                    // Show different back button depending on if user is admin or not
                    if ($isUserAdmin) {
                        // Admin sees link to admin dashboard
                        echo "<a href=\"../admin/admin-dashboard.php\" class=\"btn btn-outline-secondary btn-sm\">";
                        echo "Back to Admin Dashboard";
                        echo "</a>";
                    } else {
                        // Regular user sees link to user dashboard
                        echo "<a href=\"../users/dashboard.php\" class=\"btn btn-outline-secondary btn-sm\">";
                        echo "Back to My Dashboard";
                        echo "</a>";
                    }
                ?>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 2: ERROR MESSAGE (IF ANY) -->
        <!-- ============================================ -->
        
        <?php
            // Show error message if there is one
            if (!empty($errorMessage)) {
                // alert-danger = red alert box for errors
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                // htmlspecialchars prevents XSS attacks
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- ============================================ -->
        <!-- SECTION 3: CONFIRMATION CARD -->
        <!-- ============================================ -->
        
        <!-- row = Bootstrap grid row -->
        <!-- mb-5 = margin-bottom with 5 spacing units -->
        <div class="row mb-5">
            <!-- col-12 = full width on mobile (small screens) -->
            <!-- col-md-8 = 8 out of 12 columns on medium screens and up (desktop) -->
            <!-- offset-md-2 = push right by 2 columns on desktop to center the card -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- card = Bootstrap component for a box with border -->
                <!-- shadow = adds a shadow effect to the card -->
                <div class="card shadow">
                    
                    <!-- Card header with red background -->
                    <!-- bg-danger = red background (indicates dangerous action) -->
                    <!-- text-white = white text color -->
                    <div class="card-header bg-danger text-white">
                        <!-- mb-0 = margin-bottom 0 (removes default margin) -->
                        <h3 class="mb-0">Delete Listing</h3>
                    </div>

                    <!-- card-body = the main content area of the card -->
                    <div class="card-body">
                        
                        <!-- ============================================ -->
                        <!-- SUBSECTION 3A: WARNING MESSAGE -->
                        <!-- ============================================ -->
                        
                        <!-- alert-warning = yellow/orange warning box -->
                        <!-- role="alert" = accessibility attribute for screen readers -->
                        <div class="alert alert-warning" role="alert">
                            <!-- alert-heading = Bootstrap class for alert titles -->
                            <h5 class="alert-heading">Warning: This action cannot be undone!</h5>
                            <!-- mb-0 = margin-bottom 0 -->
                            <p class="mb-0">
                                Deleting this listing will permanently remove:
                            </p>
                            <!-- mt-2 = margin-top with 2 spacing units -->
                            <!-- mb-0 = margin-bottom 0 -->
                            <ul class="mt-2 mb-0">
                                <li>The listing itself</li>
                                <li>All plant information associated with it</li>
                                <li>All favorites pointing to this listing</li>
                            </ul>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3B: LISTING INFORMATION -->
                        <!-- ============================================ -->
                        
                        <!-- my-4 = margin top and bottom with 4 spacing units -->
                        <div class="my-4">
                            <!-- mb-3 = margin-bottom with 3 spacing units -->
                            <h5 class="mb-3">Listing to Delete:</h5>
                            
                            <!-- bg-light = light gray background -->
                            <!-- p-3 = padding all around with 3 spacing units -->
                            <!-- rounded = rounded corners -->
                            <div class="bg-light p-3 rounded">
                                <!-- list-unstyled = removes bullet points from list -->
                                <!-- mb-0 = margin-bottom 0 -->
                                <ul class="list-unstyled mb-0">
                                    <li>
                                        <strong>Title:</strong> 
                                        <?php 
                                            // Show the listing title
                                            // htmlspecialchars prevents XSS attacks
                                            echo htmlspecialchars($listingToDelete['title']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Description:</strong> 
                                        <?php 
                                            // Show the listing description
                                            echo htmlspecialchars($listingToDelete['description']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Listing ID:</strong> 
                                        <?php 
                                            // Show the listing ID number
                                            echo htmlspecialchars($listingToDelete['listing_id']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Created By:</strong> 
                                        <?php 
                                            // Show the username of who created this listing
                                            echo htmlspecialchars($listingToDelete['username']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Type:</strong>
                                        <?php
                                            // Show type with colored badge
                                            // badge = Bootstrap component for small colored labels
                                            if ($listingToDelete['listing_type'] === 'offer') {
                                                // bg-primary = blue background
                                                echo "<span class=\"badge bg-primary\">Offer</span>";
                                            } else {
                                                // bg-warning = yellow background
                                                echo "<span class=\"badge bg-warning\">Need</span>";
                                            }
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Status:</strong>
                                        <?php
                                            // Show status with colored badge
                                            if ($listingToDelete['status'] === 'active') {
                                                // bg-success = green background
                                                echo "<span class=\"badge bg-success\">Active</span>";
                                            } else if ($listingToDelete['status'] === 'inactive') {
                                                // bg-secondary = gray background
                                                echo "<span class=\"badge bg-secondary\">Inactive</span>";
                                            } else {
                                                // bg-info = light blue background
                                                echo "<span class=\"badge bg-info\">Completed</span>";
                                            }
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Created:</strong> 
                                        <?php 
                                            // Format the date to be more readable
                                            // strtotime converts string date to timestamp
                                            // date formats timestamp as Month Day, Year
                                            echo date('M d, Y', strtotime($listingToDelete['created_at'])); 
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 3C: CONFIRMATION BUTTONS -->
                        <!-- ============================================ -->
                        
                        <!-- row = Bootstrap grid row -->
                        <!-- g-2 = gap between columns with 2 spacing units -->
                        <div class="row g-2">
                            
                            <!-- Cancel button column -->
                            <!-- col-12 = full width on mobile (buttons stack vertically) -->
                            <!-- col-md-6 = half width on desktop (buttons side by side) -->
                            <div class="col-12 col-md-6">
                                <!-- Link to go back without deleting -->
                                <!-- d-grid = makes the link take full width of column -->
                                <div class="d-grid">
                                    <?php
                                        // Show different cancel link depending on if user is admin
                                        if ($isUserAdmin) {
                                            // Admin goes back to admin dashboard
                                            echo "<a href=\"../admin/admin-dashboard.php\" class=\"btn btn-secondary btn-lg\">";
                                        } else {
                                            // Regular user goes back to user dashboard
                                            echo "<a href=\"../users/dashboard.php\" class=\"btn btn-secondary btn-lg\">";
                                        }
                                    ?>
                                        Cancel
                                    </a>
                                </div>
                            </div>

                            <!-- Delete button column -->
                            <!-- col-12 = full width on mobile (buttons stack vertically) -->
                            <!-- col-md-6 = half width on desktop (buttons side by side) -->
                            <div class="col-12 col-md-6">
                                <!-- Form to submit the deletion -->
                                <!-- method="POST" = send data using POST method -->
                                <!-- action="" = submit to same page -->
                                <form method="POST" action="">
                                    <!-- d-grid = makes button full width -->
                                    <div class="d-grid">
                                        <!-- name="confirm_delete" = how we detect button click in PHP -->
                                        <!-- btn-danger = red button (indicates dangerous action) -->
                                        <!-- btn-lg = large button -->
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
