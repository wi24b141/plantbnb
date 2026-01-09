<?php
require_once __DIR__ . '/../includes/header.php';

// ============================================================
// STEP 2: Clear the "Remember Me" cookie (if it exists)
// ============================================================
// IMPORTANT: We must do this BEFORE destroying the session
// because we might need database access which requires proper setup

// Check if a remember_token cookie exists
// If it does, we need to delete it
if (isset($_COOKIE['remember_token'])) {
    // Get the token value from the cookie
    // We need this to find and delete it from the database
    $cookieToken = $_COOKIE['remember_token'];
    
    // Connect to the database to remove the token
    require_once __DIR__ . '/../includes/db.php';
    
    // Update the database to remove the token
    // This prevents anyone from using this old token to login again
    // We use a prepared statement to prevent SQL injection
    $query = "UPDATE users SET remember_token = NULL WHERE remember_token = :token";
    $statement = $connection->prepare($query);
    $statement->bindParam(':token', $cookieToken, PDO::PARAM_STR);
    $statement->execute();
    
    // Now delete the cookie from the user's browser
    // To delete a cookie, we set its expiration time to the past
    // time() - 3600 = 1 hour ago (3600 seconds = 1 hour)
    // When the browser sees an expired cookie, it automatically deletes it
    // Parameters explained:
    // 1. "remember_token" = the name of the cookie to delete
    // 2. "" = empty value (we're deleting it anyway)
    // 3. time() - 3600 = expiration time in the past
    // 4. "/" = the path (must match the path used when creating the cookie)
    setcookie("remember_token", "", time() - 3600, "/");
}

// STEP 3: Clear all session variables
// session_unset() removes ALL data from the $_SESSION array.
// Example: If $_SESSION had ['user_id' => 5, 'username' => 'john'], now it's empty [].
// This is like erasing all the notes from a notebook, but keeping the notebook.
session_unset();

// STEP 4: Completely destroy the session
// session_destroy() deletes the entire session file from the server.
// This is like throwing away the entire notebook, not just erasing the notes.
// After this, PHP no longer remembers this user at all.
session_destroy();


// STEP 5: Redirect the user to the login page
// header("Location: ...") tells the web browser to go to a different page.
// Since the user is now logged out, we send them back to the login page.
// "login.php" is in the same folder as logout.php (the users folder).
header("Location: login.php");

// STEP 6: Stop the script immediately
// exit() makes sure no other code runs after the redirect.
// This is important because after we tell the browser to go somewhere else,
// we don't want to accidentally run more code or show more HTML.
exit();
