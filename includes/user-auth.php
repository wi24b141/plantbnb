<?php
// Check if the $isLoggedIn variable exists and evaluates to true
// NOTE: Using isset() prevents undefined variable errors and ensures defensive programming
if (!isset($isLoggedIn) || $isLoggedIn === false) {
    // Redirect unauthorized users to the login page
    // NOTE: The Location header performs a 302 redirect, moving the user to the authentication page
    header("Location: /plantbnb/users/login.php");
    
    // Terminate script execution immediately to prevent unauthorized access to page content below
    // NOTE: exit() is critical for security - without it, the page would continue rendering after the header
    exit();
}
