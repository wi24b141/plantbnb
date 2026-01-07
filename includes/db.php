<?php
$databaseHost = 'localhost';
$databaseName = 'plantbnb3';
$databaseUser = 'root';
$databasePassword = '';

try {
    $connection = new PDO ('mysql:host=' . $databaseHost . ';dbname=' . $databaseName, $databaseUser, $databasePassword);

} catch (PDOException $error) {
    echo "Database connection failed: " . $error->getMessage();
    exit();
}