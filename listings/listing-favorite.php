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

        
        
        

        
        
        

        
        
        
        
        $checkQuery = "SELECT COUNT(*) FROM favorites WHERE user_id = :userID AND listing_id = :listingID";

        
        
        
        $checkStatement = $connection->prepare($checkQuery);

        
        
        
        $checkStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

        
        
        
        $checkStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

        
        
        $checkStatement->execute();

        
        
        
        
        $favoriteCount = $checkStatement->fetchColumn();

        
        
        

        
        
        if ($favoriteCount == 0) {
            
            

            
            
            
            
            $insertQuery = "INSERT INTO favorites (user_id, listing_id, created_at) VALUES (:userID, :listingID, NOW())";

            
            
            $insertStatement = $connection->prepare($insertQuery);

            
            
            $insertStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            
            
            $insertStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            
            
            $insertStatement->execute();
        }
        
        
        

        
        
        

        
        
        
        header('Location: ' . $redirectURL);

        
        
        
        exit();

    } else {
        
        

        
        header('Location: listings.php');

        
        exit();
    }

} else {
    
    
    

    
    header('Location: listings.php');

    
    exit();
}
?>