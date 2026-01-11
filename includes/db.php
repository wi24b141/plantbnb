<?php
// Database connection parameters for local development environment
$databaseHost = 'localhost';
$databaseName = 'plantbnb3';
$databaseUser = 'root';
$databasePassword = '';

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