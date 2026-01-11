<?php





require_once __DIR__ . '/../includes/header.php';


require_once __DIR__ . '/../includes/db.php';


require_once __DIR__ . '/../includes/user-auth.php';







$isUserAdmin = false;

try {
    
    $adminCheckQuery = "
        SELECT is_admin
        FROM users
        WHERE user_id = :userID
    ";
    
    
    $adminCheckStatement = $connection->prepare($adminCheckQuery);
    
    
    $adminCheckStatement->bindParam(':userID', $loggedInUserID, PDO::PARAM_INT);
    
    
    $adminCheckStatement->execute();
    
    
    $adminCheckResult = $adminCheckStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if ($adminCheckResult && $adminCheckResult['is_admin'] == 1) {
        
        $isUserAdmin = true;
    }
    
} catch (PDOException $error) {
    
    $errorMessage = "Database error: " . $error->getMessage();
}







if (!isset($_GET['listing_id'])) {
    
    
    if ($isUserAdmin) {
        header('Location: ../admin/admin-dashboard.php');
    } else {
        header('Location: ../users/dashboard.php');
    }
    exit();
}


$listingToDeleteID = intval($_GET['listing_id']);






$listingToDelete = null;


$errorMessage = '';

try {
    
    
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
    
    
    $listingStatement = $connection->prepare($listingQuery);
    
    
    $listingStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
    
    
    $listingStatement->execute();
    
    
    $listingToDelete = $listingStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$listingToDelete) {
        
        if ($isUserAdmin) {
            header('Location: ../admin/admin-dashboard.php');
        } else {
            header('Location: ../users/dashboard.php');
        }
        exit();
    }
    
} catch (PDOException $error) {
    
    $errorMessage = "Database error: " . $error->getMessage();
}









$userOwnsListing = ($listingToDelete['user_id'] == $loggedInUserID);


if (!$userOwnsListing && !$isUserAdmin) {
    
    
    header('Location: ../users/dashboard.php');
    exit();
}






if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (isset($_POST['confirm_delete'])) {
        
        
        try {
            
            
            
            
            
            
            
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE listing_id = :listingID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            
            $deletePlantsQuery = "
                DELETE FROM plants
                WHERE listing_id = :listingID
            ";
            $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
            $deletePlantsStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deletePlantsStatement->execute();
            
            
            $deleteListingQuery = "
                DELETE FROM listings
                WHERE listing_id = :listingID
            ";
            $deleteListingStatement = $connection->prepare($deleteListingQuery);
            $deleteListingStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deleteListingStatement->execute();
            
            
            
            if ($isUserAdmin) {
                
                header('Location: ../admin/admin-dashboard.php');
            } else {
                
                header('Location: ../users/dashboard.php');
            }
            exit();
            
        } catch (PDOException $error) {
            
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
                    
                    if ($isUserAdmin) {
                        
                        echo "<a href=\"../admin/admin-dashboard.php\" class=\"btn btn-outline-secondary btn-sm\">";
                        echo "Back to Admin Dashboard";
                        echo "</a>";
                    } else {
                        
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
            
            if (!empty($errorMessage)) {
                
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                
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
                                            
                                            
                                            echo htmlspecialchars($listingToDelete['title']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Description:</strong> 
                                        <?php 
                                            
                                            echo htmlspecialchars($listingToDelete['description']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Listing ID:</strong> 
                                        <?php 
                                            
                                            echo htmlspecialchars($listingToDelete['listing_id']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Created By:</strong> 
                                        <?php 
                                            
                                            echo htmlspecialchars($listingToDelete['username']); 
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Type:</strong>
                                        <?php
                                            
                                            
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
                                            
                                            if ($listingToDelete['status'] === 'active') {
                                                
                                                echo "<span class=\"badge bg-success\">Active</span>";
                                            } else if ($listingToDelete['status'] === 'inactive') {
                                                
                                                echo "<span class=\"badge bg-secondary\">Inactive</span>";
                                            } else {
                                                
                                                echo "<span class=\"badge bg-info\">Completed</span>";
                                            }
                                        ?>
                                    </li>
                                    <li>
                                        <strong>Created:</strong> 
                                        <?php 
                                            
                                            
                                            
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
                                        
                                        if ($isUserAdmin) {
                                            
                                            echo "<a href=\"../admin/admin-dashboard.php\" class=\"btn btn-secondary btn-lg\">";
                                        } else {
                                            
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
