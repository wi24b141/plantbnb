<?php
// ============================================
// ADMIN AUTHENTICATION CHECK
// ============================================
// This file checks if the current user is an admin
// Include this file at the top of any admin-only page
// It will automatically redirect non-admins away

// STEP 1: Make sure user is logged in first
// This file checks if user has an active session
require_once __DIR__ . '/user-auth.php';

// STEP 2: Connect to database
// We need the database connection to check the user's role
require_once __DIR__ . '/db.php';

// ============================================
// STEP 3: GET CURRENT USER'S ROLE
// ============================================

// Get the current logged-in user's ID from the session
// intval() converts to integer for security
$currentUserID = intval($_SESSION['user_id']);

// This variable will store the user's role
$currentUserRole = '';

// Wrap database code in try-catch to handle errors
try {
    // Query to get the user's role from the database
    // We only need the role column
    $roleQuery = "SELECT role FROM users WHERE user_id = :userID";
    
    // Prepare the query to prevent SQL injection
    $roleStatement = $connection->prepare($roleQuery);
    
    // Bind the user ID parameter
    $roleStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
    
    // Execute the query
    $roleStatement->execute();
    
    // Get the result
    $roleResult = $roleStatement->fetch(PDO::FETCH_ASSOC);
    
    // Check if we found the user
    if ($roleResult) {
        // Store the role
        $currentUserRole = $roleResult['role'];
    }
    
} catch (PDOException $error) {
    // If database query fails, we cannot verify admin status
    // For security, we deny access by setting role to empty string
    $currentUserRole = '';
}

// ============================================
// STEP 4: CHECK IF USER IS ADMIN
// ============================================

// Check if the user's role is NOT 'admin'
if ($currentUserRole !== 'admin') {
    // Not an admin - redirect to regular user dashboard
    // exit() stops the script immediately after redirect
    header('Location: /plantbnb/users/dashboard.php');
    exit();
}

// If we reach this point, the user is confirmed to be an admin
// The admin page can now safely display admin content
?>
