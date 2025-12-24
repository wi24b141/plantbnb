<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($isLoggedIn === false) {
    header("Location: login.php");
    exit();
}

// Store the user_id from the session for use in queries
// We use intval() to ensure it's an integer for extra safety
$userID = intval($_SESSION['user_id']);

// ============================================
// HANDLE FAVORITE REQUEST
// ============================================

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the favorite request

    // Check if listing_id was sent in the POST data
    // We use isset() to check if the key exists
    // We use is_numeric() to ensure the value is a number
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        // Listing ID exists and is a number, proceed with favorite

        // Store the listing ID and convert to integer for safety
        // intval() converts the string to an integer, removing any potential malicious characters
        $listingID = intval($_POST['listing_id']);

        // Get the redirect URL if provided, otherwise default to favoritelistings.php
        // This allows us to redirect back to where the user came from
        // For example, if favoriting from listing-details.php, we go back there
        // If no redirect_url is provided, we go to the favorites page
        if (isset($_POST['redirect_url'])) {
            $redirectURL = $_POST['redirect_url'];
        } else {
            $redirectURL = 'favoritelistings.php';
        }

        try {
            // Query to insert a new favorite entry into the favorites table
            // We use INSERT IGNORE to prevent duplicate entries
            // INSERT IGNORE means: if the user-listing combination already exists, 
            // the query will silently do nothing (no error thrown)
            // This is safer than checking first with a SELECT query because it prevents race conditions
            // Race condition = two requests trying to favorite at the same time could both succeed
            $insertQuery = "
                INSERT IGNORE INTO favorites (user_id, listing_id, created_at)
                VALUES (:userID, :listingID, NOW())
            ";

            // Prepare the insert statement to prevent SQL injection
            // Prepared statements separate the SQL code from the data
            // This makes it impossible for attackers to inject malicious SQL code
            $insertStatement = $connection->prepare($insertQuery);

            // Bind the user_id parameter to prevent SQL injection
            // :userID is a placeholder that will be safely replaced with the actual user ID
            // PDO::PARAM_INT ensures the value is treated as an integer
            $insertStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Bind the listing_id parameter to prevent SQL injection
            // :listingID is a placeholder that will be safely replaced with the actual listing ID
            // PDO::PARAM_INT ensures the value is treated as an integer
            $insertStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // Execute the insert statement
            // This actually runs the SQL query and inserts the data
            $insertStatement->execute();

            // Favorite was successful (or already existed), redirect back
            // We use header() to redirect the browser to the redirect URL
            // header() must be called before any HTML output
            header('Location: ' . $redirectURL);
            exit();

        } catch (PDOException $error) {
            // If a database error occurs, redirect back with an error
            // In a real production application, you would:
            // 1. Log this error to a file for debugging
            // 2. Show a user-friendly error message
            // For now, we just redirect back to the same page
            header('Location: ' . $redirectURL);
            exit();
        }

    } else {
        // Invalid listing ID (either missing or not a number)
        // Redirect to the main listings page
        header('Location: listings.php');
        exit();
    }

} else {
    // Form was not submitted via POST method
    // This could happen if someone tries to access this page directly via URL
    // Redirect to the main listings page
    header('Location: listings.php');
    exit();
}
?>