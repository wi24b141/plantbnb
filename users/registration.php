<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';


$errors = [];
$successMessage = '';
$username = '';
$email = '';
$password = '';
$passwordConfirm = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['password_confirm']);

    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    }
    if (strlen($username) > 50) {
        $errors[] = 'Username must not exceed 50 characters.';
    }

    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if (empty($passwordConfirm)) {
        $errors[] = 'Please confirm your password.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    
    if (count($errors) === 0) {
        try {
            
            $checkEmailQuery = "SELECT user_id FROM users WHERE email = :email";
            $checkEmailStatement = $connection->prepare($checkEmailQuery);
            
            $checkEmailStatement->bindParam(':email', $email, PDO::PARAM_STR);
            $checkEmailStatement->execute();
            $existingUser = $checkEmailStatement->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                $errors[] = 'This email is already registered.';
            } else {
                
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                
                $insertUserQuery = "INSERT INTO users (username, email, password_hash, is_verified, created_at) VALUES (:username, :email, :password_hash, 0, NOW())";
                $insertUserStatement = $connection->prepare($insertUserQuery);
                
                $insertUserStatement->bindParam(':username', $username, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':email', $email, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
                $insertUserStatement->execute();

                $successMessage = 'Registration successful! You can now log in.';
                
                
                $username = '';
                $email = '';
                $password = '';
                $passwordConfirm = '';
            }

        } catch (PDOException $error) {
            
            $errors[] = 'A database error occurred. Please try again later.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PlantBnB</title>
</head>
<body>
    <!-- Main container with vertical spacing -->
    <div class="container mt-5 mb-5">
        <div class="row">
            <!-- Responsive column: full width on mobile (col-12), half width centered on desktop (col-md-6 offset-md-3) -->
            <div class="col-12 col-md-6 offset-md-3">
                
                <!-- Bootstrap card component with green branded header -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Create Account</h3>
                    </div>

                    <div class="card-body">
                        
                        <?php
                        
                        if (!empty($successMessage)) {
                            echo "<div class=\"alert alert-success\">";
                            
                            echo htmlspecialchars($successMessage);
                            echo "</div>";
                        }
                        ?>

                        <?php
                        
                        if (count($errors) > 0) {
                            echo "<div class=\"alert alert-danger\">";
                            echo "<strong>Please fix the following errors:</strong>";
                            echo "<ul>";
                            foreach ($errors as $error) {
                                
                                echo "<li>" . htmlspecialchars($error) . "</li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                        }
                        ?>

                        <!-- Registration form submits to self via POST method for security -->
                        <form method="POST" action="">
                            
                            <!-- Username input with Bootstrap styling and HTML5 validation -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required
                                >
                            </div>

                            <!-- Email input with HTML5 type="email" for mobile keyboard optimization -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    required
                                >
                            </div>

                            <!-- Password input with masked characters (never pre-filled for security) -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    required
                                >
                            </div>

                            <!-- Password confirmation to prevent typos -->
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    required
                                >
                            </div>

                            <!-- Full-width submit button using d-grid for better mobile accessibility -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Card footer with centered login link -->
                    <div class="card-footer bg-light text-center">
                        <p>
                            Already have an account? 
                            <a href="login.php">Log in here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>