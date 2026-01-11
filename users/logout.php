<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// Process cookie invalidation before session destruction to maintain database connection context
if (isset($_COOKIE['remember_token'])) {
    $cookieToken = $_COOKIE['remember_token'];
    
    // Invalidate remember token in database to prevent reuse
    // NOTE: PDO prepared statements protect against SQL injection by separating SQL logic from user data
    $query = "UPDATE users SET remember_token = NULL WHERE remember_token = :token";
    $statement = $connection->prepare($query);
    $statement->bindParam(':token', $cookieToken, PDO::PARAM_STR);
    $statement->execute();
    
    // Delete client-side cookie by setting expiration to past timestamp
    // NOTE: Cookies are deleted by setting expiration time before current time (time() - 3600)
    // Path "/" must match original cookie path for successful deletion
    setcookie("remember_token", "", time() - 3600, "/");
}

// Clear all session variables from $_SESSION superglobal
// NOTE: session_unset() removes session data but maintains session ID and file
session_unset();

// Completely destroy session file on server
// NOTE: session_destroy() removes the server-side session file, terminating the session lifecycle
session_destroy();

// Redirect unauthenticated user to login page
header("Location: login.php");

// NOTE: exit() after header() prevents code execution after redirect and ensures clean termination
exit();
