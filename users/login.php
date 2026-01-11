<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// Initialize error message variable for form validation feedback
$loginError = '';

// Process POST request when user submits login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve and sanitize user input from POST superglobal
    // NOTE: trim() removes leading/trailing whitespace to prevent authentication bypass
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    // Check if "Remember Me" checkbox was selected for persistent authentication
    $rememberMe = isset($_POST["remember_me"]);
    
    // Server-side validation to ensure both credentials are provided
    if (empty($username) || empty($password)) {
        $loginError = "Please enter both username and password.";
        
    } else {
        // NOTE: Using PDO prepared statements protects against SQL injection attacks by separating
        // SQL logic from user data. The :username placeholder is bound to the variable separately.
        $query = "SELECT user_id, username, password_hash FROM users WHERE username = :username";
        $statement = $connection->prepare($query);
        $statement->bindParam(':username', $username, PDO::PARAM_STR);
        $statement->execute();
        
        // Retrieve user record; returns false if no matching username exists
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        
        // NOTE: password_verify() uses one-way hashing (BCRYPT) to securely compare passwords.
        // Plain-text passwords are never stored in the database, protecting user credentials.
        if ($user && password_verify($password, $user['password_hash'])) {
            // Authentication successful - establish user session
            // NOTE: Sessions maintain state across HTTP requests (which are stateless by default).
            // Session data is stored server-side and referenced via a session ID cookie.
            $_SESSION["loggedIn"] = true;
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];
            
            
            // Implement persistent authentication if "Remember Me" was selected
            if ($rememberMe) {
                // NOTE: random_bytes(32) generates cryptographically secure random tokens.
                // This prevents token prediction attacks. bin2hex() converts to hexadecimal string.
                $rememberToken = bin2hex(random_bytes(32));
                
                // Store hashed token in database for server-side verification
                // NOTE: Using prepared statements here also prevents SQL injection
                $updateQuery = "UPDATE users SET remember_token = :token WHERE user_id = :user_id";
                $updateStatement = $connection->prepare($updateQuery);
                $updateStatement->bindParam(':token', $rememberToken, PDO::PARAM_STR);
                $updateStatement->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
                $updateStatement->execute();
                
                // Set cookie with 30-day expiration (2,592,000 seconds)
                // Cookie path "/" ensures availability across entire application
                setcookie("remember_token", $rememberToken, time() + (30 * 24 * 60 * 60), "/");
            }
            
            // Redirect authenticated user to main listings page
            // NOTE: exit() after header() prevents code execution after redirect
            header("Location: /plantbnb/listings/listings.php");
            exit();
            
        } else {
            // Authentication failed - generic error message prevents username enumeration attacks
            // NOTE: Not revealing whether username or password is incorrect is a security best practice
            $loginError = "Invalid username or password.";
        }
    }
}
?>

<!-- HTML Presentation Layer: Login Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlantBnB</title>
</head>
<body>
    <!-- Bootstrap container with vertical spacing utilities (mt-5, mb-5) -->
    <div class="container mt-5 mb-5">
        <!-- Bootstrap grid: col-md-6 creates 50% width on medium+ screens,
             offset-md-3 centers by adding 25% left margin, col-12 ensures full width on mobile -->
        <div class="row">
            <div class="col-12 col-md-6 offset-md-3">
                <!-- Bootstrap card component for structured form layout -->
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">Login</h3>
                    </div>

                    <div class="card-body">
                        <?php
                            // Display validation errors with Bootstrap alert styling
                            // NOTE: htmlspecialchars() prevents XSS attacks by escaping HTML entities
                            if (!empty($loginError)) {
                                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                                echo htmlspecialchars($loginError);
                                echo "</div>";
                            }
                        ?>

                        <!-- POST method ensures credentials are not exposed in URL -->
                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <!-- Repopulate username on validation error for better UX -->
                                <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <!-- Password field never repopulated for security reasons -->
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input" <?php if(isset($_POST['remember_me'])) { echo 'checked'; } ?>>
                                    <label for="remember_me" class="form-check-label">Remember me for 30 days</label>
                                </div>
                            </div>

                            <!-- d-grid creates full-width button layout -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Login</button>
                            </div>
                        </form>

                        <!-- Forgot password link -->
                        <div class="text-center mt-3">
                            <a href="password-reset-request.php">Forgot password?</a>
                        </div>
                    </div>

                    <div class="card-footer bg-light text-center">
                        <p class="mb-0">Don't have an account? <a href="registration.php">Register here</a></p>
                    </div>
                </div>
            </div> <!-- End: Centered column -->
        </div> <!-- End: Bootstrap row -->
    </div> <!-- End: Container -->
</body>
</html>