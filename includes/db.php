<?php
// Database connection parameters for local development environment
$databaseHost = 'localhost';
$databaseName = 'plantbnb3';
$databaseUser = 'root';
$databasePassword = '';

// NOTE: Using PDO (PHP Data Objects) instead of mysqli because PDO provides:
// 1. Database-agnostic interface (can switch from MySQL to PostgreSQL easily)
// 2. Native support for prepared statements (prevents SQL injection)
// 3. Better exception handling through PDOException class
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