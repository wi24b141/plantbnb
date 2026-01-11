<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Initialize form state variables
$errors = [];
$successMessage = '';
$username = '';
$email = '';
$password = '';
$passwordConfirm = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize user input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['password_confirm']);

    // Validate username length and presence
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    }
    if (strlen($username) > 50) {
        $errors[] = 'Username must not exceed 50 characters.';
    }

    // Validate email format using PHP's built-in filter
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    // NOTE: FILTER_VALIDATE_EMAIL ensures RFC-compliant email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password strength and confirmation
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

    // Proceed with database operations only if all validation passes
    if (count($errors) === 0) {
        try {
            // Check for duplicate email addresses
            $checkEmailQuery = "SELECT user_id FROM users WHERE email = :email";
            $checkEmailStatement = $connection->prepare($checkEmailQuery);
            // NOTE: Using PDO prepared statements protects against SQL Injection attacks
            $checkEmailStatement->bindParam(':email', $email, PDO::PARAM_STR);
            $checkEmailStatement->execute();
            $existingUser = $checkEmailStatement->fetch(PDO::FETCH_ASSOC);

            if ($existingUser) {
                $errors[] = 'This email is already registered.';
            } else {
                // NOTE: password_hash() uses BCRYPT, a one-way hashing algorithm that prevents password recovery
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user with hashed password and unverified status
                $insertUserQuery = "INSERT INTO users (username, email, password_hash, is_verified, created_at) VALUES (:username, :email, :password_hash, 0, NOW())";
                $insertUserStatement = $connection->prepare($insertUserQuery);
                // NOTE: PDO prepared statements with bound parameters prevent SQL Injection
                $insertUserStatement->bindParam(':username', $username, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':email', $email, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
                $insertUserStatement->execute();

                $successMessage = 'Registration successful! You can now log in.';
                
                // Clear form fields after successful registration
                $username = '';
                $email = '';
                $password = '';
                $passwordConfirm = '';
            }

        } catch (PDOException $error) {
            // Catch database exceptions and display generic error message for security
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
                        // Display success message if registration completed
                        if (!empty($successMessage)) {
                            echo "<div class=\"alert alert-success\">";
                            // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) attacks by escaping HTML
                            echo htmlspecialchars($successMessage);
                            echo "</div>";
                        }
                        ?>

                        <?php
                        // Display validation errors if present
                        if (count($errors) > 0) {
                            echo "<div class=\"alert alert-danger\">";
                            echo "<strong>Please fix the following errors:</strong>";
                            echo "<ul>";
                            foreach ($errors as $error) {
                                // NOTE: htmlspecialchars() escapes special characters to prevent XSS attacks
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