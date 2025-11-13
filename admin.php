<?php
session_start();

if (!isset($_SESSION["loggedIn"])) {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminPanel</title>
</head>
<body>

<?php
    echo $_SESSION["loggedIn"];
    //echo $_SESSION["color"];


?>

    <h1>Welcome Admin!</h1>
    
</body>
</html>
