<?php


require_once __DIR__ . '/user-auth.php';
require_once __DIR__ . '/db.php';



$currentUserID = intval($_SESSION['user_id']);


$currentUserRole = '';


try {
    
    $roleQuery = "SELECT role FROM users WHERE user_id = :userID";

    
    
    $roleStatement = $connection->prepare($roleQuery);

    
    $roleStatement->bindParam(':userID', $currentUserID, PDO::PARAM_INT);
    
    
    $roleStatement->execute();

    
    $roleResult = $roleStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if ($roleResult) {
        $currentUserRole = $roleResult['role'];
    }
    
} catch (PDOException $error) {
    
    
    $currentUserRole = '';
}



if ($currentUserRole !== 'admin') {
    header('Location: /plantbnb/users/dashboard.php');
    exit(); 
}
?>
