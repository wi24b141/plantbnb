<?php
require_once __DIR__ . '/../includes/header.php';

// STEP 2: Clear all session variables
// session_unset() removes ALL data from the $_SESSION array.
// Example: If $_SESSION had ['user_id' => 5, 'username' => 'john'], now it's empty [].
// This is like erasing all the notes from a notebook, but keeping the notebook.
session_unset();

// STEP 3: Completely destroy the session
// session_destroy() deletes the entire session file from the server.
// This is like throwing away the entire notebook, not just erasing the notes.
// After this, PHP no longer remembers this user at all.
session_destroy();

// STEP 4: Redirect the user to the login page
// header("Location: ...") tells the web browser to go to a different page.
// Since the user is now logged out, we send them back to the login page.
// "login.php" is in the same folder as logout.php (the users folder).
header("Location: login.php");

// STEP 5: Stop the script immediately
// exit() makes sure no other code runs after the redirect.
// This is important because after we tell the browser to go somewhere else,
// we don't want to accidentally run more code or show more HTML.
exit();
