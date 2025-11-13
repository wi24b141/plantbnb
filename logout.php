<?php
session_start();

// this line is basically the same as $_SESSION =[];
session_unset();

session_destroy();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
</head>
<body>
    You have been logged out!
</body>
</html>