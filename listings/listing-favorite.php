<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$userID = intval($_SESSION['user_id']);

// Enforce POST-only access to prevent accidental favorites via GET requests or direct URL access.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate that listing_id exists and is numeric before type casting.
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        
        // Type cast to integer for safe database parameter binding.
        $listingID = intval($_POST['listing_id']);

        // Determine redirect destination: use provided URL or default to favorites page.
        if (isset($_POST['redirect_url'])) {
            $redirectURL = $_POST['redirect_url'];
        } else {
            $redirectURL = 'favoritelistings.php';
        }

        // Query to check for existing favorite entry to maintain database integrity.
        // NOTE: COUNT(*) aggregate function returns 0 if no match exists, enabling idempotent insert logic.
        $checkQuery = "SELECT COUNT(*) FROM favorites WHERE user_id = :userID AND listing_id = :listingID";
        
        // NOTE: PDO prepare() with named placeholders protects against SQL injection by separating SQL structure from user data.
        $checkStatement = $connection->prepare($checkQuery);
        
        // Bind parameters with explicit type declarations for additional type safety.
        $checkStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
        $checkStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
        
        $checkStatement->execute();
        
        // fetchColumn() retrieves the COUNT result as a scalar value.
        $favoriteCount = $checkStatement->fetchColumn();

        // Insert only if favorite does not already exist (idempotent operation).
        if ($favoriteCount == 0) {
            
            // Insert new favorite record with server timestamp for audit trail.
            // NOTE: NOW() is a MySQL function that records the exact timestamp of the operation, useful for analytics.
            $insertQuery = "INSERT INTO favorites (user_id, listing_id, created_at) VALUES (:userID, :listingID, NOW())";
            
            // NOTE: Prepared statements are used here to protect against SQL injection attacks.
            $insertStatement = $connection->prepare($insertQuery);
            
            $insertStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $insertStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
            
            $insertStatement->execute();
        }
        // If favorite already exists, skip insert (silent success for better UX).

        // Redirect to the determined destination after successful processing.
        header('Location: ' . $redirectURL);
        exit();

    } else {
        // Invalid or missing listing_id: redirect to safe default page.
        header('Location: listings.php');
        exit();
    }

} else {
    // Reject non-POST requests by redirecting to listings page.
    header('Location: listings.php');
    exit();
}
?>