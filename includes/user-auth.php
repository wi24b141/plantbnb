<?php


if (!isset($isLoggedIn) || $isLoggedIn === false) {
    
    
    header("Location: /plantbnb/users/login.php");
    
    
    
    exit();
}
