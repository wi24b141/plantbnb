<?php
// NOTE: Including user-auth.php first ensures the user is logged in before checking admin status.
// This implements a layered security approach: first authenticate, then authorize.
require_once __DIR__ . '/user-auth.php';
require_once __DIR__ . '/db.php';

// Retrieve and sanitize the user ID from the session
// NOTE: intval() ensures type safety and prevents type juggling vulnerabilities.
$currentUserID = intval($_SESSION['user_id']);

// Initialize role variable to empty string for security-by-default principle
$currentUserRole = '';

// Query database to verify user's role for Role-Based Access Control (RBAC)
try {
    // NOTE: This query retrieves only the role column to minimize data exposure.
    $roleQuery = "SELECT role FROM users WHERE user_id = :userID";

    // NOTE: Using prepared statements protects against SQL Injection attacks.
    // The placeholder :userID is bound separately from the query string.
    $roleStatement = $connection->prepare($roleQuery);

    // Bind the user ID as an integer to ensure type safety at the database level
    $roleStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
    
    // Execute the prepared statement with bound parameters
    $roleStatement->execute();

    // Fetch the result as an associative array for easier access
    $roleResult = $roleStatement->fetch(PDO::FETCH_ASSOC);
    
    // Only assign role if a valid result was returned from the database
    if ($roleResult) {
        $currentUserRole = $roleResult['role'];
    }
    
} catch (PDOException $error) {
    // NOTE: On database error, role remains empty string, triggering redirect below.
    // This fail-secure approach prevents access if role cannot be verified.
    $currentUserRole = '';
}

// NOTE: This implements the Principle of Least Privilege - only 'admin' role can proceed.
// Any other role (including empty string on error) is redirected to regular dashboard.
if ($currentUserRole !== 'admin') {
    header('Location: /plantbnb/users/dashboard.php');
    exit(); // Terminate script execution to prevent any further code from running
}
?>
