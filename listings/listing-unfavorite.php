<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$userID = intval($_SESSION['user_id']);

// NOTE: This script only accepts POST requests to prevent accidental unfavoriting via GET.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate that listing_id is present and numeric before processing.
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {

        // Sanitize the listing ID to ensure it is an integer.
        $listingID = intval($_POST['listing_id']);

        // Determine the redirect destination; defaults to the favorites page if not specified.
        if (isset($_POST['redirect_url'])) {
            $redirectURL = $_POST['redirect_url'];
        } else {
            $redirectURL = 'favoritelistings.php';
        }

        // NOTE: try-catch ensures graceful handling of PDOExceptions (e.g., connection loss).
        try {
            // Delete the favorite record matching both user_id and listing_id.
            // This WHERE clause enforces authorization: users can only unfavorite their own entries.
            $deleteQuery = "DELETE FROM favorites WHERE user_id = :userID AND listing_id = :listingID";

            // NOTE: PDO prepared statements protect against SQL Injection by separating SQL logic from data.
            $deleteStatement = $connection->prepare($deleteQuery);

            // Bind parameters as integers to enforce type safety.
            $deleteStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $deleteStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // Execute the DELETE operation.
            $deleteStatement->execute();

            // Redirect to the referring page after successful deletion.
            // NOTE: exit() is required after header() to prevent further script execution.
            header('Location: ' . $redirectURL);
            exit();

        } catch (PDOException $error) {
            // Silently redirect on database errors. In production, this should log errors or notify the user.
            header('Location: ' . $redirectURL);
            exit();
        }

    } else {
        // Invalid or missing listing_id; redirect to listings page.
        header('Location: listings.php');
        exit();
    }

} else {
    // Reject non-POST requests (e.g., direct URL access) for security.
    header('Location: listings.php');
    exit();
}
?>