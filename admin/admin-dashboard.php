<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$pendingUsers = array();

try {
    // Retrieve users awaiting verification (uploaded ID but is_verified = 0)
    // Ordered chronologically to prioritize older submissions
    $pendingQuery = "
        SELECT 
            user_id,
            username,
            email,
            verification_document_path,
            created_at
        FROM users
        WHERE verification_document_path IS NOT NULL
          AND is_verified = 0
        ORDER BY created_at ASC
    ";
    
    // NOTE: PDO prepared statements protect against SQL Injection by separating query structure from data
    $pendingStatement = $connection->prepare($pendingQuery);
    $pendingStatement->execute();
    $pendingUsers = $pendingStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // Graceful degradation: empty array prevents fatal errors in HTML rendering
}

$allUsers = array();

try {
    // Fetch complete user registry for administrative oversight
    $usersQuery = "
        SELECT 
            user_id,
            username,
            email,
            role,
            is_verified,
            created_at
        FROM users
        ORDER BY created_at DESC
    ";
    
    $usersStatement = $connection->prepare($usersQuery);
    $usersStatement->execute();
    $allUsers = $usersStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // Graceful degradation maintains page functionality despite query failure
}

$totalUsersCount = 0;
$verifiedUsersCount = 0;
$totalListingsCount = 0;

try {
    // NOTE: Aggregate functions like COUNT(*) are efficient for dashboard metrics
    $totalUsersQuery = "SELECT COUNT(*) as total FROM users";
    
    $totalUsersStatement = $connection->prepare($totalUsersQuery);
    $totalUsersStatement->execute();
    $totalUsersResult = $totalUsersStatement->fetch(PDO::FETCH_ASSOC);
    
    if ($totalUsersResult) {
        $totalUsersCount = intval($totalUsersResult['total']);
    }
    
} catch (PDOException $error) {
    // Default value of 0 ensures display stability
}

try {
    // Filter count to users with successful ID verification
    $verifiedUsersQuery = "SELECT COUNT(*) as total FROM users WHERE is_verified = 1";
    
    $verifiedUsersStatement = $connection->prepare($verifiedUsersQuery);
    $verifiedUsersStatement->execute();
    $verifiedUsersResult = $verifiedUsersStatement->fetch(PDO::FETCH_ASSOC);
    
    if ($verifiedUsersResult) {
        $verifiedUsersCount = intval($verifiedUsersResult['total']);
    }
    
} catch (PDOException $error) {
    // Maintains metric integrity on database errors
}

try {
    // Retrieve total listings count across all users and types
    $totalListingsQuery = "SELECT COUNT(*) as total FROM listings";
    
    $totalListingsStatement = $connection->prepare($totalListingsQuery);
    $totalListingsStatement->execute();
    $totalListingsResult = $totalListingsStatement->fetch(PDO::FETCH_ASSOC);
    
    if ($totalListingsResult) {
        $totalListingsCount = intval($totalListingsResult['total']);
    }
    
} catch (PDOException $error) {
    // Prevents dashboard display errors if listings table is inaccessible
}

$allListings = array();

try {
    // NOTE: INNER JOIN combines listings with user data, excluding orphaned records
    // This query demonstrates relational database normalization principles
    $listingsQuery = "
        SELECT 
            l.listing_id,
            l.title,
            l.listing_type,
            l.status,
            l.created_at,
            u.username
        FROM listings l
        INNER JOIN users u ON l.user_id = u.user_id
        ORDER BY l.created_at DESC
    ";
    
    $listingsStatement = $connection->prepare($listingsQuery);
    $listingsStatement->execute();
    $allListings = $listingsStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // Empty array allows page rendering without data
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PlantBnB</title>
</head>
<body>
    <!-- Main Container: Bootstrap .container class provides responsive fixed-width layout -->
    <div class="container mt-4">
        
        <!-- Page Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <!-- Uses Bootstrap utility classes for visual hierarchy -->
                <div class="bg-primary text-white p-3 rounded">
                    <h1 class="mb-0">Admin Dashboard</h1>
                    <small>Manage users, verify IDs, and moderate listings</small>
                </div>
            </div>
        </div>

        <!-- Statistics Dashboard: Three-column responsive grid using Bootstrap's 12-column system -->
        <!-- NOTE: col-md-4 creates equal-width columns (4/12) on medium+ screens, stacks vertically on mobile -->
        <div class="row mb-5">
            
            <!-- Total Users Metric Card -->
            <div class="col-12 col-md-4 mb-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Users</h6>
                        <h2 class="display-4 text-primary">
                            <?php 
                                // NOTE: htmlspecialchars() prevents XSS by encoding HTML special characters
                                echo htmlspecialchars($totalUsersCount); 
                            ?>
                        </h2>
                        <p class="text-muted mb-0">All registered users</p>
                    </div>
                </div>
            </div>
            
            <!-- Verified Users Metric Card -->
            <div class="col-12 col-md-4 mb-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Verified Users</h6>
                        <h2 class="display-4 text-success">
                            <?php echo htmlspecialchars($verifiedUsersCount); ?>
                        </h2>
                        <p class="text-muted mb-0">ID verified accounts</p>
                    </div>
                </div>
            </div>
            
            <!-- Total Listings Metric Card -->
            <div class="col-12 col-md-4 mb-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Listings</h6>
                        <h2 class="display-4 text-info">
                            <?php echo htmlspecialchars($totalListingsCount); ?>
                        </h2>
                        <p class="text-muted mb-0">Plant listings</p>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Pending Verifications Section: Users awaiting ID verification -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-warning">
                        <h3 class="mb-0">Pending Verifications</h3>
                    </div>
                    
                    <!-- card-body = main content area of card -->
                    <div class="card-body">
                        <?php
                            if (count($pendingUsers) > 0) {
                        ?>
                                <!-- NOTE: .table-responsive enables horizontal scrolling on small viewports, preventing layout breakage -->
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Uploaded On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                foreach ($pendingUsers as $pendingUser) {
                                                    echo "<tr>";
                                                    echo "  <td>" . htmlspecialchars($pendingUser['username']) . "</td>";
                                                    echo "  <td>" . htmlspecialchars($pendingUser['email']) . "</td>";
                                                    // NOTE: date() and strtotime() convert MySQL DATETIME to human-readable format
                                                    echo "  <td>" . date('M d, Y', strtotime($pendingUser['created_at'])) . "</td>";
                                                    echo "  <td>";
                                                    // URL parameter passing for admin review workflow
                                                    echo "    <a href=\"admin-verify-user.php?user_id=" . $pendingUser['user_id'] . "\" class=\"btn btn-primary btn-sm\">";
                                                    echo "      Review Document";
                                                    echo "    </a>";
                                                    echo "  </td>";
                                                    echo "</tr>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                        <?php
                            } else {
                                echo "<p class=\"text-center text-muted mb-0\">No pending verifications at this time.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Users Section: Complete user registry -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">All Users</h3>
                    </div>
                    <div class="card-body">
                        <?php
                            if (count($allUsers) > 0) {
                        ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Verified</th>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                foreach ($allUsers as $user) {
                                                    echo "<tr>";
                                                    echo "  <td>" . htmlspecialchars($user['username']) . "</td>";
                                                    echo "  <td>" . htmlspecialchars($user['email']) . "</td>";
                                                    // NOTE: Bootstrap badges provide visual differentiation of user roles
                                                    echo "  <td>";
                                                    if ($user['role'] === 'admin') {
                                                        echo "    <span class=\"badge bg-danger\">Admin</span>";
                                                    } else {
                                                        echo "    <span class=\"badge bg-secondary\">User</span>";
                                                    }
                                                    echo "  </td>";
                                                    echo "  <td>";
                                                    if ($user['is_verified'] == 1) {
                                                        echo "    <span class=\"badge bg-success\">Yes</span>";
                                                    } else {
                                                        echo "    <span class=\"badge bg-warning\">No</span>";
                                                    }
                                                    echo "  </td>";
                                                    echo "  <td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>";
                                                    echo "  <td>";
                                                    // Destructive action uses .btn-danger to signal caution
                                                    echo "    <a href=\"admin-delete-user.php?user_id=" . $user['user_id'] . "\" class=\"btn btn-danger btn-sm\">";
                                                    echo "      Delete";
                                                    echo "    </a>";
                                                    echo "  </td>";
                                                    echo "</tr>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                        <?php
                            } else {
                                echo "<p class=\"text-center text-muted mb-0\">No users found.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Listings Section: Platform-wide listing management -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">All Listings</h3>
                    </div>
                    <div class="card-body">
                        <?php
                            if (count($allListings) > 0) {
                        ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Type</th>
                                                <th>Owner</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                foreach ($allListings as $listing) {
                                                    echo "<tr>";
                                                    echo "  <td>" . htmlspecialchars($listing['title']) . "</td>";
                                                    echo "  <td>";
                                                    if ($listing['listing_type'] === 'offer') {
                                                        echo "    <span class=\"badge bg-primary\">Offer</span>";
                                                    } else {
                                                        echo "    <span class=\"badge bg-warning\">Need</span>";
                                                    }
                                                    echo "  </td>";
                                                    echo "  <td>" . htmlspecialchars($listing['username']) . "</td>";
                                                    echo "  <td>";
                                                    if ($listing['status'] === 'active') {
                                                        echo "    <span class=\"badge bg-success\">Active</span>";
                                                    } else if ($listing['status'] === 'inactive') {
                                                        echo "    <span class=\"badge bg-secondary\">Inactive</span>";
                                                    } else {
                                                        echo "    <span class=\"badge bg-info\">Completed</span>";
                                                    }
                                                    echo "  </td>";
                                                    echo "  <td>" . date('M d, Y', strtotime($listing['created_at'])) . "</td>";
                                                    echo "  <td>";
                                                    // NOTE: .me-2 (margin-end) and .mb-1 (margin-bottom) prevent button wrapping issues
                                                    echo "    <a href=\"/plantbnb/listings/listing-details.php?listing_id=" . $listing['listing_id'] . "\" class=\"btn btn-info btn-sm me-2 mb-1\">";
                                                    echo "      View";
                                                    echo "    </a>";
                                                    echo "    <a href=\"admin-delete-listing.php?listing_id=" . $listing['listing_id'] . "\" class=\"btn btn-danger btn-sm mb-1\">";
                                                    echo "      Delete";
                                                    echo "    </a>";
                                                    echo "  </td>";
                                                    echo "</tr>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                        <?php
                            } else {
                                echo "<p class=\"text-center text-muted mb-0\">No listings found.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
