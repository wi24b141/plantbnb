<?php

// redirect the user to the login page
if (!isset($isLoggedIn) || $isLoggedIn === false) {
    header("Location: /plantbnb/users/login.php");
// Stop executing the script so no protected content is shown
    exit();
}
