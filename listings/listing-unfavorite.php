<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================================
// STEP 2: GET THE LOGGED-IN USER'S ID
// ============================================================

// At this point, we know the user is logged in (user-auth.php checked that)
// So we can safely access $_SESSION['user_id']
// We convert it to an integer for safety (prevents SQL injection)
$userID = intval($_SESSION['user_id']);

// ============================================================
// STEP 3: CHECK IF FORM WAS SUBMITTED
// ============================================================

// We check if the page was accessed using the POST method
// POST method = data was sent via a form submission
// If someone just types the URL in their browser, that is GET method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // YES - Form was submitted via POST, we can continue

    // ============================================================
    // STEP 4: VALIDATE THE LISTING ID
    // ============================================================

    // Check if the form included a field called "listing_id"
    // AND check if that value is numeric (a number)
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        // YES - We have a valid listing ID

        // Store the listing ID as an integer for safety
        $listingID = intval($_POST['listing_id']);

        // ============================================================
        // STEP 5: DETERMINE WHERE TO REDIRECT AFTER UNFAVORITE
        // ============================================================

        // Check if the form included a "redirect_url" field
        // This tells us which page to send the user back to after unfavoriting
        if (isset($_POST['redirect_url'])) {
            // YES - Use the provided redirect URL
            $redirectURL = $_POST['redirect_url'];
        } else {
            // NO - Use a default redirect URL (the favorites list page)
            $redirectURL = 'favoritelistings.php';
        }

        // ============================================================
        // STEP 6: DELETE THE FAVORITE FROM DATABASE
        // ============================================================

        // We use try-catch to handle database errors
        try {
            // WRITE THE DELETE QUERY
            // This query removes one row from the "favorites" table
            // We match BOTH user_id and listing_id to make sure:
            // - The user can only delete THEIR OWN favorites
            // - We delete the correct favorite entry
            $deleteQuery = "DELETE FROM favorites WHERE user_id = :userID AND listing_id = :listingID";

            // PREPARE THE QUERY (Step 1 of PDO prepared statements)
            // This creates a prepared statement object
            // Prepared statements prevent SQL injection attacks
            $deleteStatement = $connection->prepare($deleteQuery);

            // BIND THE PARAMETERS (Step 2 of PDO prepared statements)
            // Replace :userID with the actual $userID value
            $deleteStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Replace :listingID with the actual $listingID value
            $deleteStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // EXECUTE THE QUERY (Step 3 of PDO prepared statements)
            // This actually runs the DELETE command on the database
            $deleteStatement->execute();

            // ============================================================
            // STEP 7: REDIRECT BACK TO PREVIOUS PAGE
            // ============================================================

            // The unfavorite was successful!
            // Send the user back to the page they came from
            // header() tells the browser to load a different page
            header('Location: ' . $redirectURL);

            // Stop running this script immediately
            // exit() is required after header() to prevent further code execution
            exit();

        } catch (PDOException $error) {
            // ============================================================
            // ERROR HANDLING: DATABASE ERROR
            // ============================================================

            // If something goes wrong with the database (connection lost, table missing, etc.)
            // We just redirect the user back anyway
            // NOTE: In a real app, we would display an error message to the user
            header('Location: ' . $redirectURL);
            exit();
        }

    } else {
        // ============================================================
        // ERROR HANDLING: INVALID LISTING ID
        // ============================================================

        // The form did NOT include a valid listing_id
        // This should never happen if the form is built correctly
        // Send user to the listings page
        header('Location: listings.php');
        exit();
    }

} else {
    // ============================================================
    // ERROR HANDLING: NOT A POST REQUEST
    // ============================================================

    // Someone accessed this page directly (typed URL in browser)
    // This page should ONLY be accessed via form submission (POST)
    // Send user to the listings page
    header('Location: listings.php');
    exit();
}
?>