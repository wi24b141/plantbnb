<?php


// PDO = "PHP Data Objects" (a PHP interface to connect to databases like MySQL, PostgreSQL, SQLite, etc.)
// DSN = "Data Source Name" (a connection string that tells PDO which driver + host + database to use)

// Database connection settings (host, database name, user, password)
$databaseHost = 'localhost';
$databaseName = 'plantbnb3';
$databaseUser = 'root';
$databasePassword = '';

try {
     // DSN example (fully built): mysql:host=localhost;dbname=plantbnb3
    // new PDO(DSN, username, password) creates the database connection
    $connection = new PDO ('mysql:host=' . $databaseHost . ';dbname=' . $databaseName, $databaseUser, $databasePassword);

} catch (PDOException $error) {
      // If the connection fails, PDO throws a PDO Exception
    echo "Database connection failed: " . $error->getMessage();
    exit(); // Stop the script if there is no DB connection
}