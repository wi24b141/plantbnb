<?php
// This is for development purposes only because this is a critical security flaw!
// This includes config.development.php to make the databse easily sharable over GitHub in our group
require_once __DIR__ . '/config.development.php';
// This has to be changed to config.php in production with real credentials to prevent a critical security flaw
// The config.php file contains the real password and is NOT uploaded to GitHub
// require_once __DIR__ . '/config.php';

try {
    // Establish connection using DSN (Data Source Name) format
    $connection = new PDO ('mysql:host=' . $databaseHost . ';dbname=' . $databaseName, $databaseUser, $databasePassword);
    
    // NOTE: PDO will be used throughout the application with prepared statements
    // to protect against SQL Injection attacks by separating SQL logic from user data.
} catch (PDOException $error) {
    // Catch and handle any connection failures gracefully
    // Exposing the error message here is acceptable in development but should be logged in production
    echo "Database connection failed: " . $error->getMessage();
    exit(); // Terminate script execution as the application cannot function without database access
}