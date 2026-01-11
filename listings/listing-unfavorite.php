<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';








$userID = intval($_SESSION['user_id']);








if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    
    
    

    
    
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        

        
        $listingID = intval($_POST['listing_id']);

        
        
        

        
        
        if (isset($_POST['redirect_url'])) {
            
            $redirectURL = $_POST['redirect_url'];
        } else {
            
            $redirectURL = 'favoritelistings.php';
        }

        
        
        

        
        try {
            
            
            
            
            
            $deleteQuery = "DELETE FROM favorites WHERE user_id = :userID AND listing_id = :listingID";

            
            
            
            $deleteStatement = $connection->prepare($deleteQuery);

            
            
            $deleteStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            
            $deleteStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            
            
            $deleteStatement->execute();

            
            
            

            
            
            
            header('Location: ' . $redirectURL);

            
            
            exit();

        } catch (PDOException $error) {
            
            
            

            
            
            
            header('Location: ' . $redirectURL);
            exit();
        }

    } else {
        
        
        

        
        
        
        header('Location: listings.php');
        exit();
    }

} else {
    
    
    

    
    
    
    header('Location: listings.php');
    exit();
}
?>