<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin-auth.php';

// ============================================
// STEP 3: FETCH USERS PENDING VERIFICATION
// ============================================

// This array will store users who uploaded ID documents but are not verified yet
// These are the users the admin needs to review
$pendingUsers = array();

try {
    // Query to get users who have a verification document but is_verified = 0
    // We also get their username and email so admin knows who they are
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
    
    // Prepare the query
    $pendingStatement = $connection->prepare($pendingQuery);
    
    // Execute the query
    $pendingStatement->execute();
    
    // Get all results as an array
    // fetchAll() returns multiple rows (unlike fetch() which returns one row)
    $pendingUsers = $pendingStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // If the query fails, $pendingUsers stays as empty array
    // We will show an error message in the HTML section
}

// ============================================
// STEP 4: FETCH ALL USERS
// ============================================

// This array will store all users in the system
// Admin can see the complete user list here
$allUsers = array();

try {
    // Query to get all users with their basic info
    // We order by created_at DESC to show newest users first
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
    
    // Prepare the query
    $usersStatement = $connection->prepare($usersQuery);
    
    // Execute the query
    $usersStatement->execute();
    
    // Get all results
    $allUsers = $usersStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // If the query fails, $allUsers stays as empty array
}

// ============================================
// STEP 5: CALCULATE STATISTICS
// ============================================

// We need three numbers for the statistics dashboard:
// 1. Total number of users
// 2. Number of verified users (is_verified = 1)
// 3. Total number of listings

// Initialize variables to store the counts
// We start with 0 in case the database queries fail
$totalUsersCount = 0;
$verifiedUsersCount = 0;
$totalListingsCount = 0;

try {
    // ========== COUNT TOTAL USERS ==========
    // COUNT(*) counts all rows in the users table
    $totalUsersQuery = "SELECT COUNT(*) as total FROM users";
    
    // Prepare the query to prevent SQL injection
    $totalUsersStatement = $connection->prepare($totalUsersQuery);
    
    // Execute the query
    $totalUsersStatement->execute();
    
    // Fetch the result
    // We use fetch() because we expect only one row
    $totalUsersResult = $totalUsersStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if we got a result
    if ($totalUsersResult) {
        // Store the count in our variable
        $totalUsersCount = intval($totalUsersResult['total']);
    }
    
} catch (PDOException $error) {
    // If this query fails, $totalUsersCount stays at 0
}

try {
    // ========== COUNT VERIFIED USERS ==========
    // We only count users where is_verified = 1
    $verifiedUsersQuery = "SELECT COUNT(*) as total FROM users WHERE is_verified = 1";
    
    // Prepare the query
    $verifiedUsersStatement = $connection->prepare($verifiedUsersQuery);
    
    // Execute the query
    $verifiedUsersStatement->execute();
    
    // Fetch the result
    $verifiedUsersResult = $verifiedUsersStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if we got a result
    if ($verifiedUsersResult) {
        // Store the count in our variable
        $verifiedUsersCount = intval($verifiedUsersResult['total']);
    }
    
} catch (PDOException $error) {
    // If this query fails, $verifiedUsersCount stays at 0
}

try {
    // ========== COUNT TOTAL LISTINGS ==========
    // COUNT(*) counts all rows in the listings table
    $totalListingsQuery = "SELECT COUNT(*) as total FROM listings";
    
    // Prepare the query
    $totalListingsStatement = $connection->prepare($totalListingsQuery);
    
    // Execute the query
    $totalListingsStatement->execute();
    
    // Fetch the result
    $totalListingsResult = $totalListingsStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if we got a result
    if ($totalListingsResult) {
        // Store the count in our variable
        $totalListingsCount = intval($totalListingsResult['total']);
    }
    
} catch (PDOException $error) {
    // If this query fails, $totalListingsCount stays at 0
}

// ============================================
// STEP 6: FETCH ALL LISTINGS
// ============================================

// This array will store all listings in the system
// Admin can see all listings and manage them
$allListings = array();

try {
    // Query to get all listings with the username of who created them
    // We use a JOIN to combine data from listings and users tables
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
    
    // Prepare the query
    $listingsStatement = $connection->prepare($listingsQuery);
    
    // Execute the query
    $listingsStatement->execute();
    
    // Get all results
    $allListings = $listingsStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // If the query fails, $allListings stays as empty array
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding for all languages and symbols -->
    <meta charset="UTF-8">
    
    <!-- Mobile-friendly viewport -->
    <!-- width=device-width = use the device's screen width -->
    <!-- initial-scale=1.0 = don't zoom in or out by default -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title shown in browser tab -->
    <title>Admin Dashboard - PlantBnB</title>
</head>
<body>
    <!-- Container centers content and adds padding -->
    <!-- mt-4 = margin-top (space at top of page) -->
    <div class="container mt-4">
        
        <!-- ============================================ -->
        <!-- SECTION 1: PAGE HEADER -->
        <!-- ============================================ -->
        
        <!-- mb-4 = margin-bottom (space below) -->
        <div class="row mb-4">
            <!-- col-12 = full width on mobile and desktop -->
            <div class="col-12">
                <!-- bg-primary = blue background -->
                <!-- text-white = white text color -->
                <!-- p-3 = padding all around -->
                <!-- rounded = rounded corners -->
                <div class="bg-primary text-white p-3 rounded">
                    <!-- mb-0 = no margin-bottom -->
                    <h1 class="mb-0">Admin Dashboard</h1>
                    <!-- small tag = smaller text -->
                    <small>Manage users, verify IDs, and moderate listings</small>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 2: STATISTICS CARDS -->
        <!-- ============================================ -->
        
        <!-- mb-5 = large margin-bottom (space below section) -->
        <div class="row mb-5">
            
            <!-- ========== CARD 1: TOTAL USERS ========== -->
            <!-- col-12 = full width on mobile (vertical phone screen) -->
            <!-- col-md-4 = one-third width on desktop (medium screens and up) -->
            <!-- mb-3 = margin-bottom for spacing between cards on mobile -->
            <div class="col-12 col-md-4 mb-3">
                <!-- card = Bootstrap box component -->
                <!-- shadow = adds shadow for visual depth -->
                <div class="card shadow">
                    <!-- card-body = main content area -->
                    <div class="card-body">
                        <!-- text-muted = gray text color -->
                        <!-- text-uppercase = makes text UPPERCASE -->
                        <!-- small = smaller font size -->
                        <h6 class="text-muted text-uppercase small">Total Users</h6>
                        
                        <!-- display-4 = very large text size -->
                        <!-- text-primary = blue color -->
                        <h2 class="display-4 text-primary">
                            <?php 
                                // Output the total users count
                                // htmlspecialchars() prevents XSS attacks
                                echo htmlspecialchars($totalUsersCount); 
                            ?>
                        </h2>
                        
                        <!-- text-muted = gray text -->
                        <p class="text-muted mb-0">All registered users</p>
                    </div>
                </div>
            </div>
            
            <!-- ========== CARD 2: VERIFIED USERS ========== -->
            <!-- Same layout as Card 1 -->
            <div class="col-12 col-md-4 mb-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Verified Users</h6>
                        
                        <!-- text-success = green color -->
                        <h2 class="display-4 text-success">
                            <?php 
                                // Output the verified users count
                                echo htmlspecialchars($verifiedUsersCount); 
                            ?>
                        </h2>
                        
                        <p class="text-muted mb-0">ID verified accounts</p>
                    </div>
                </div>
            </div>
            
            <!-- ========== CARD 3: TOTAL LISTINGS ========== -->
            <!-- Same layout as Cards 1 and 2 -->
            <div class="col-12 col-md-4 mb-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">Total Listings</h6>
                        
                        <!-- text-info = light blue/cyan color -->
                        <h2 class="display-4 text-info">
                            <?php 
                                // Output the total listings count
                                echo htmlspecialchars($totalListingsCount); 
                            ?>
                        </h2>
                        
                        <p class="text-muted mb-0">Plant listings</p>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- ============================================ -->
        <!-- SECTION 3: PENDING VERIFICATIONS -->
        <!-- ============================================ -->
        
        <!-- mb-5 = large margin-bottom (space below section) -->
        <div class="row mb-5">
            <div class="col-12">
                <!-- card = Bootstrap box component -->
                <!-- shadow = adds shadow to make it stand out -->
                <div class="card shadow">
                    <!-- card-header = top section of card -->
                    <!-- bg-warning = yellow/orange background -->
                    <div class="card-header bg-warning">
                        <h3 class="mb-0">Pending Verifications</h3>
                    </div>
                    
                    <!-- card-body = main content area of card -->
                    <div class="card-body">
                        <?php
                            // Check if there are any pending users
                            // count() returns the number of items in the array
                            if (count($pendingUsers) > 0) {
                                // There are pending users - show them in a table
                                
                                // On mobile, tables can overflow the screen
                                // table-responsive adds horizontal scrolling on small screens
                        ?>
                                <!-- table-responsive = allows horizontal scrolling on mobile -->
                                <div class="table-responsive">
                                    <!-- table = Bootstrap table styling -->
                                    <!-- table-hover = rows highlight when you hover over them -->
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <!-- th = table header cell -->
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Uploaded On</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // Loop through each pending user
                                                // foreach creates a loop that goes through each item in the array
                                                foreach ($pendingUsers as $pendingUser) {
                                                    // For each user, we create a table row (tr)
                                                    echo "<tr>";
                                                    
                                                    // Username column
                                                    // htmlspecialchars() prevents XSS attacks (security)
                                                    echo "  <td>" . htmlspecialchars($pendingUser['username']) . "</td>";
                                                    
                                                    // Email column
                                                    echo "  <td>" . htmlspecialchars($pendingUser['email']) . "</td>";
                                                    
                                                    // Date uploaded column
                                                    // We format the date to be human-readable
                                                    // strtotime() converts database date to timestamp
                                                    // date() formats timestamp to readable format
                                                    echo "  <td>" . date('M d, Y', strtotime($pendingUser['created_at'])) . "</td>";
                                                    
                                                    // Action column with a button
                                                    echo "  <td>";
                                                    // This link goes to admin-verify-user.php
                                                    // We pass the user_id in the URL so that page knows which user to show
                                                    // btn-sm = small button (saves space on mobile)
                                                    // btn-primary = blue button
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
                                // No pending users - show a message
                                // text-center = center the text horizontally
                                // text-muted = gray text color
                                echo "<p class=\"text-center text-muted mb-0\">No pending verifications at this time.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 4: ALL USERS -->
        <!-- ============================================ -->
        
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <!-- bg-info = light blue background -->
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0">All Users</h3>
                    </div>
                    
                    <div class="card-body">
                        <?php
                            // Check if there are any users
                            if (count($allUsers) > 0) {
                                // There are users - show them in a table
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
                                                // Loop through each user
                                                foreach ($allUsers as $user) {
                                                    echo "<tr>";
                                                    
                                                    // Username
                                                    echo "  <td>" . htmlspecialchars($user['username']) . "</td>";
                                                    
                                                    // Email
                                                    echo "  <td>" . htmlspecialchars($user['email']) . "</td>";
                                                    
                                                    // Role
                                                    // We show the role with a colored badge
                                                    echo "  <td>";
                                                    if ($user['role'] === 'admin') {
                                                        // Admin role gets red badge
                                                        echo "    <span class=\"badge bg-danger\">Admin</span>";
                                                    } else {
                                                        // Regular user gets gray badge
                                                        echo "    <span class=\"badge bg-secondary\">User</span>";
                                                    }
                                                    echo "  </td>";
                                                    
                                                    // Verified status
                                                    echo "  <td>";
                                                    if ($user['is_verified'] == 1) {
                                                        // Verified = green badge with checkmark
                                                        echo "    <span class=\"badge bg-success\">Yes</span>";
                                                    } else {
                                                        // Not verified = yellow badge
                                                        echo "    <span class=\"badge bg-warning\">No</span>";
                                                    }
                                                    echo "  </td>";
                                                    
                                                    // Join date
                                                    echo "  <td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>";
                                                    
                                                    // Actions
                                                    echo "  <td>";
                                                    // Link to delete user page
                                                    // btn-danger = red button (indicates dangerous action)
                                                    // btn-sm = small button
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
                                // No users found
                                echo "<p class=\"text-center text-muted mb-0\">No users found.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 5: ALL LISTINGS -->
        <!-- ============================================ -->
        
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow">
                    <!-- bg-success = green background -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">All Listings</h3>
                    </div>
                    
                    <div class="card-body">
                        <?php
                            // Check if there are any listings
                            if (count($allListings) > 0) {
                                // There are listings - show them in a table
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
                                                // Loop through each listing
                                                foreach ($allListings as $listing) {
                                                    echo "<tr>";
                                                    
                                                    // Title
                                                    echo "  <td>" . htmlspecialchars($listing['title']) . "</td>";
                                                    
                                                    // Type (offer or need)
                                                    echo "  <td>";
                                                    if ($listing['listing_type'] === 'offer') {
                                                        // Offer = blue badge
                                                        echo "    <span class=\"badge bg-primary\">Offer</span>";
                                                    } else {
                                                        // Need = orange badge
                                                        echo "    <span class=\"badge bg-warning\">Need</span>";
                                                    }
                                                    echo "  </td>";
                                                    
                                                    // Owner username
                                                    echo "  <td>" . htmlspecialchars($listing['username']) . "</td>";
                                                    
                                                    // Status
                                                    echo "  <td>";
                                                    if ($listing['status'] === 'active') {
                                                        // Active = green badge
                                                        echo "    <span class=\"badge bg-success\">Active</span>";
                                                    } else if ($listing['status'] === 'inactive') {
                                                        // Inactive = gray badge
                                                        echo "    <span class=\"badge bg-secondary\">Inactive</span>";
                                                    } else {
                                                        // Completed = blue badge
                                                        echo "    <span class=\"badge bg-info\">Completed</span>";
                                                    }
                                                    echo "  </td>";
                                                    
                                                    // Created date
                                                    echo "  <td>" . date('M d, Y', strtotime($listing['created_at'])) . "</td>";
                                                    
                                                    // Actions
                                                    echo "  <td>";
                                                    // View listing button
                                                    // me-2 = margin-end (space to the right on desktop, below on mobile)
                                                    echo "    <a href=\"/plantbnb/listings/listing-details.php?listing_id=" . $listing['listing_id'] . "\" class=\"btn btn-info btn-sm me-2 mb-1\">";
                                                    echo "      View";
                                                    echo "    </a>";
                                                    // Delete listing button
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
                                // No listings found
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
