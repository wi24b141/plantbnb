<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/user-auth.php';

// NOTE: Authorization is role-based - regular users can only delete their own listings,
// while administrators have privileges to delete any listing in the system.
$isUserAdmin = false;

try {
    // NOTE: Using prepared statements with bound parameters prevents SQL Injection attacks
    // by separating SQL logic from user-supplied data.
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
    // PDOException handling ensures graceful degradation when database queries fail
    $errorMessage = "Database error: " . $error->getMessage();
}

// Input validation: ensure listing_id parameter exists before proceeding
if (!isset($_GET['listing_id'])) {
    if ($isUserAdmin) {
        header('Location: ../admin/admin-dashboard.php');
    } else {
        header('Location: ../users/dashboard.php');
    }
    exit();
}

// NOTE: intval() sanitizes user input by converting to integer, preventing injection attacks
// through type coercion. Non-numeric values become 0.
$listingToDeleteID = intval($_GET['listing_id']);

$listingToDelete = null;
$errorMessage = '';

try {
    // INNER JOIN retrieves listing data along with creator username in a single query,
    // improving performance by avoiding multiple database roundtrips.
    // The prepared statement protects against SQL Injection.
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

// NOTE: Authorization check implements principle of least privilege - users can only
// delete resources they own unless they possess elevated administrative privileges.
$userOwnsListing = ($listingToDelete['user_id'] == $loggedInUserID);

if (!$userOwnsListing && !$isUserAdmin) {
    header('Location: ../users/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['confirm_delete'])) {
        
        try {
            // NOTE: Cascading deletion pattern manually maintains referential integrity
            // by deleting child records (favorites, plants) before parent record (listing).
            // This prevents orphaned foreign key references in the database.
            // Order matters: child tables first, parent table last.
            
            // Delete favorites referencing this listing
            $deleteFavoritesQuery = "
                DELETE FROM favorites
                WHERE listing_id = :listingID
            ";
            $deleteFavoritesStatement = $connection->prepare($deleteFavoritesQuery);
            $deleteFavoritesStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deleteFavoritesStatement->execute();
            
            // Delete plants associated with this listing
            $deletePlantsQuery = "
                DELETE FROM plants
                WHERE listing_id = :listingID
            ";
            $deletePlantsStatement = $connection->prepare($deletePlantsQuery);
            $deletePlantsStatement->bindParam(':listingID', $listingToDeleteID, PDO::PARAM_INT);
            $deletePlantsStatement->execute();
            
            // Delete the listing itself
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Listing - PlantBnB</title>
</head>
<body>
    <!-- Main Content Container: Bootstrap container class centers content with responsive padding -->
    <div class="container mt-4">
        
        <!-- Navigation Section -->
        <div class="row mb-3">
            <div class="col-12">
                <?php
                    // Conditional navigation based on user role
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

        <!-- Error Display Section -->
        <?php
            if (!empty($errorMessage)) {
                // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) attacks
                // by encoding HTML special characters in user-supplied data.
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo htmlspecialchars($errorMessage);
                echo "</div>";
            }
        ?>

        <!-- Confirmation Card: Responsive layout using Bootstrap grid (col-md-8 offset-md-2 centers on medium+ screens) -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                
                <div class="card shadow">
                    
                    <!-- Danger-themed header indicates destructive action -->
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Delete Listing</h3>
                    </div>

                    <div class="card-body">
                        
                        <!-- Warning Section: ARIA role="alert" improves accessibility for screen readers -->
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

                        <!-- Listing Details Section -->
                        <div class="my-4">
                            <h5 class="mb-3">Listing to Delete:</h5>
                            
                            <div class="bg-light p-3 rounded">
                                <ul class="list-unstyled mb-0">
                                    <li>
                                        <strong>Title:</strong> 
                                        <?php echo htmlspecialchars($listingToDelete['title']); ?>
                                    </li>
                                    <li>
                                        <strong>Description:</strong> 
                                        <?php echo htmlspecialchars($listingToDelete['description']); ?>
                                    </li>
                                    <li>
                                        <strong>Listing ID:</strong> 
                                        <?php echo htmlspecialchars($listingToDelete['listing_id']); ?>
                                    </li>
                                    <li>
                                        <strong>Created By:</strong> 
                                        <?php echo htmlspecialchars($listingToDelete['username']); ?>
                                    </li>
                                    <li>
                                        <strong>Type:</strong>
                                        <?php
                                            // Conditional rendering with semantic color coding via Bootstrap badges
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
                                            // Date formatting for user-friendly display
                                            echo date('M d, Y', strtotime($listingToDelete['created_at'])); 
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons: Responsive grid (col-md-6) displays buttons side-by-side on desktop, stacked on mobile -->
                        <div class="row g-2">
                            
                            <div class="col-12 col-md-6">
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

                            <div class="col-12 col-md-6">
                                <!-- POST method prevents deletion via URL manipulation or accidental GET requests -->
                                <form method="POST" action="">
                                    <div class="d-grid">
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
