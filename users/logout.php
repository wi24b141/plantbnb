<?php
require_once __DIR__ . '/../includes/header.php';


if (isset($_COOKIE['remember_token'])) {
    $cookieToken = $_COOKIE['remember_token'];
    
    require_once __DIR__ . '/../includes/db.php';
    
    
    
    $query = "UPDATE users SET remember_token = NULL WHERE remember_token = :token";
    $statement = $connection->prepare($query);
    $statement->bindParam(':token', $cookieToken, PDO::PARAM_STR);
    $statement->execute();
    
    
    
    
    setcookie("remember_token", "", time() - 3600, "/");
}



session_unset();



session_destroy();


header("Location: login.php");


exit();
