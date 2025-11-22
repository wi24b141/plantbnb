<?php
// Start the session so we can access $_SESSION data
session_start();

// Clear all session variables
// This line removes all data stored in $_SESSION
session_unset();

// Destroy the session completely
// This removes the session file from the server
session_destroy();

// Redirect the user back to the login page after logout
// header() must be called before any HTML output
// exit() stops the script so nothing else runs
header("Location: login.php");
exit();

?>