<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\listing-unfavorite.php

// ============================================
// UNFAVORITE LISTING - PHP LOGIC
// ============================================

// Start the session to access $_SESSION variables
session_start();

// Include the database connection
require_once 'db.php';

// ============================================
// SECURITY CHECK: VERIFY USER IS LOGGED IN
// ============================================

// Check if user_id exists in the session
// If the user is not logged in, redirect to the login page immediately
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Store the user_id from the session for use in queries
$userID = intval($_SESSION['user_id']);

// ============================================
// HANDLE UNFAVORITE REQUEST
// ============================================

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the unfavorite request

    // Check if listing_id was sent in the POST data
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        // Listing ID exists and is a number, proceed with unfavorite

        // Store the listing ID and convert to integer for safety
        $listingID = intval($_POST['listing_id']);

        // Get the redirect URL if provided, otherwise default to favoritelistings.php
        // This allows us to redirect back to where the user came from
        // CHANGED: Now defaults to favoritelistings.php (your renamed file)
        if (isset($_POST['redirect_url'])) {
            $redirectURL = $_POST['redirect_url'];
        } else {
            $redirectURL = 'favoritelistings.php';
        }

        try {
            // Query to delete the favorite entry
            // We need to match both user_id and listing_id to ensure users can only delete their own favorites
            $deleteQuery = "
                DELETE FROM favorites
                WHERE user_id = :userID
                AND listing_id = :listingID
            ";

            // Prepare the delete statement
            $deleteStatement = $connection->prepare($deleteQuery);

            // Bind the parameters to prevent SQL injection
            $deleteStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $deleteStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // Execute the delete
            $deleteStatement->execute();

            // Unfavorite was successful, redirect back
            // We use header() to redirect the browser
            header('Location: ' . $redirectURL);
            exit();

        } catch (PDOException $error) {
            // If a database error occurs, redirect back with an error
            // In a real application, you would show a proper error message
            header('Location: ' . $redirectURL);
            exit();
        }

    } else {
        // Invalid listing ID, redirect to listings page
        header('Location: listings.php');
        exit();
    }

} else {
    // Form was not submitted via POST, redirect to listings page
    header('Location: listings.php');
    exit();
}
?>