<?php
// ============================================
// STEP 1: INCLUDE REQUIRED FILES
// ============================================
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 2: GET USER ID FROM SESSION
// ============================================

// Get the user_id from the session
// The session was started in header.php
// The user_id was stored when the user logged in
// We use intval() to convert it to an integer (whole number)
// Why intval()? Because we want to be 100% sure it's a number and not text
// Example: intval("5abc") becomes 5, intval("hello") becomes 0
$userID = intval($_SESSION['user_id']);

// ============================================
// STEP 3: CHECK IF FORM WAS SUBMITTED
// ============================================

// Check if this page was reached by submitting a form (POST method)
// Forms use POST to send data to the server
// $_SERVER['REQUEST_METHOD'] tells us HOW the page was accessed
// Possible values: 'GET' (clicking a link) or 'POST' (submitting a form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // YES, the form was submitted with POST method
    // Now we can process the favorite request

    // ============================================
    // STEP 4: VALIDATE THE LISTING ID
    // ============================================

    // Check if listing_id was sent in the form data
    // isset() checks if the variable exists
    // is_numeric() checks if the value is a number
    // Why both? Because we need to make sure:
    // 1. The listing_id field was included in the form (isset)
    // 2. The value is actually a number (is_numeric)
    if (isset($_POST['listing_id']) && is_numeric($_POST['listing_id'])) {
        // YES, listing_id exists AND it's a number
        // We can proceed with adding the favorite

        // Store the listing ID as an integer
        // intval() converts the text number to a real integer
        // Example: intval("42") becomes 42
        // This protects against SQL injection attacks
        $listingID = intval($_POST['listing_id']);

        // ============================================
        // STEP 5: DETERMINE WHERE TO REDIRECT AFTER
        // ============================================

        // Check if a redirect URL was provided in the form
        // The redirect URL tells us where to send the user after favoriting
        // Example: If they favorited from listing-details.php, send them back there
        if (isset($_POST['redirect_url'])) {
            // YES, a redirect URL was provided
            // Use that URL
            $redirectURL = $_POST['redirect_url'];
        } else {
            // NO, no redirect URL was provided
            // Use the default: go to the favorites page
            $redirectURL = 'favoritelistings.php';
        }

        // ============================================
        // STEP 6: CHECK IF FAVORITE ALREADY EXISTS
        // ============================================

        // Before we add a new favorite, we need to check if it already exists
        // Why? Because we don't want duplicate favorites in the database
        // Each user should only favorite a listing ONCE

        // Write the SQL query to check for existing favorite
        // COUNT(*) counts how many rows match our criteria
        // If COUNT returns 0 = favorite does NOT exist
        // If COUNT returns 1 = favorite DOES exist
        $checkQuery = "SELECT COUNT(*) FROM favorites WHERE user_id = :userID AND listing_id = :listingID";

        // Prepare the query for execution
        // prepare() is a security feature that prevents SQL injection
        // SQL injection = when hackers try to insert malicious SQL code
        $checkStatement = $connection->prepare($checkQuery);

        // Bind the user ID to the :userID placeholder
        // This replaces :userID with the actual user ID value
        // PDO::PARAM_INT means "this is an integer (whole number)"
        $checkStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

        // Bind the listing ID to the :listingID placeholder
        // This replaces :listingID with the actual listing ID value
        // PDO::PARAM_INT means "this is an integer (whole number)"
        $checkStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

        // Execute the query
        // This actually runs the SQL and gets the result from the database
        $checkStatement->execute();

        // Get the count result
        // fetchColumn() gets the first column of the first row
        // In our case, this is the COUNT(*) value
        // This will be 0 if no favorite exists, or 1 if it exists
        $favoriteCount = $checkStatement->fetchColumn();

        // ============================================
        // STEP 7: INSERT FAVORITE IF IT DOESN'T EXIST
        // ============================================

        // Check if the favorite does NOT exist yet
        // $favoriteCount will be 0 if it doesn't exist
        if ($favoriteCount == 0) {
            // The favorite does NOT exist yet
            // We can safely insert a new favorite

            // Write the SQL query to insert a new favorite
            // INSERT INTO adds a new row to the favorites table
            // We insert: user_id, listing_id, and the current date/time
            // NOW() is a MySQL function that gets the current date and time
            $insertQuery = "INSERT INTO favorites (user_id, listing_id, created_at) VALUES (:userID, :listingID, NOW())";

            // Prepare the insert query
            // This is for security (prevents SQL injection)
            $insertStatement = $connection->prepare($insertQuery);

            // Bind the user ID parameter
            // Replace :userID with the actual user ID value
            $insertStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Bind the listing ID parameter
            // Replace :listingID with the actual listing ID value
            $insertStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            // Execute the insert query
            // This actually adds the new favorite to the database
            $insertStatement->execute();
        }
        // If $favoriteCount is NOT 0 (meaning favorite already exists)
        // We do nothing - just skip the insert
        // This is OK because the user already favorited this listing

        // ============================================
        // STEP 8: REDIRECT BACK TO PREVIOUS PAGE
        // ============================================

        // Send the user back to where they came from
        // header('Location: ...') tells the browser to go to a new page
        // This is like clicking a link automatically
        header('Location: ' . $redirectURL);

        // Stop the PHP script immediately
        // exit() prevents any more code from running
        // Why? Because after header() we don't want to show any HTML
        exit();

    } else {
        // NO, listing_id is missing OR it's not a number
        // This is invalid, so we redirect to the listings page

        // Send user to the main listings page
        header('Location: listings.php');

        // Stop the script
        exit();
    }

} else {
    // NO, this page was NOT accessed via POST method
    // Someone probably typed the URL directly in the browser
    // This page should ONLY be accessed by submitting the favorite form

    // Send user to the main listings page
    header('Location: listings.php');

    // Stop the script
    exit();
}
?>