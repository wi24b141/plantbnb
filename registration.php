<?php 
    $username = $_POST["username"] ?? "Fallback";
    $password = $_POST["password"] ?? "";
    $email = trim($_POST["email"]);


    $errors = [];


    if(empty($email)){
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }

    if (!empty($errors)) {
        foreach($errors as $error) {
            echo "<p>$error</p>";
        }
    }

    


    $safeUsername = htmlspecialchars($username);
    $safePassword = htmlspecialchars($password);
    

    echo "Hello <i>$safeUsername $safePassword</i>! <br>";

    for ($i=0; $i<5; $i++) {
        echo "Number: $i <br>";
    }

    $x=1;
    while ($x <=5) {
        echo "Value: " . $x . " <br>";
        $x++;
    }

    $fruits = ["Apple", "Banana", "Orange"];
    foreach ($fruits as $fruit) {
        echo "Fruit: $fruit <br>";
    }
?>