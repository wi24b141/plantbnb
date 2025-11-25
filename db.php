<?php
// This file handles the database connection for the entire application
// We only need to write the connection code once here, then include this file in other pages

// Define the database connection settings
// These values tell PDO where to find the database and how to log in
$databaseHost = 'localhost';           // The server where MariaDB is running
$databaseName = 'plantbnbDatabase';    // The name of the database we created
$databaseUser = 'root';                // The username to log in with (default for XAMPP)
$databasePassword = '';                // The password (empty by default in XAMPP)

// Use a try-catch block to safely handle connection errors
// If something goes wrong, we catch the error instead of crashing
try {
    // Create a new PDO connection to MariaDB
    // We build a "Data Source Name" (DSN) that tells PDO which database to connect to
    // The format is: mysql:host=HOSTNAME;dbname=DATABASE_NAME
    $connection = new PDO(
        'mysql:host=' . $databaseHost . ';dbname=' . $databaseName,
        $databaseUser,
        $databasePassword
    );

    // Configure PDO to throw exceptions when errors occur
    // This makes it easier to catch and handle database errors
    // PDO::ATTR_ERRMODE = "Set the error mode"
    // PDO::ERRMODE_EXCEPTION = "Throw an exception (error) so we can catch it"
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $error) {
    // If the connection fails, catch the PDOException (PDO error)
    // We use die() to stop the page and display the error message
    // This prevents the application from continuing with a broken connection
    die("Database connection failed: " . $error->getMessage());
}
?>