<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';


// ============================================================
// STEP 1: Initialize variables
// ============================================================

// Create an empty error message variable
$loginError = '';

// ============================================================
// STEP 2: Check if the user submitted the login form
// ============================================================

// $_SERVER["REQUEST_METHOD"] tells HOW the page was loaded
// "GET" = user just visited the page (by clicking a link)
// "POST" = user submitted a form on this page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // -----------------------------------------------------------
    // STEP 3A: Get the data from the form
    // -----------------------------------------------------------
    
    // Get the username that the user typed in the form
    $username = trim($_POST["username"] ?? "");
    
    // Get the password that the user typed in the form
    $password = trim($_POST["password"] ?? "");
    
    // Get the "remember me" checkbox value
    // If the checkbox was checked, $_POST["remember_me"] will be "on"
    // If not checked, it won't exist in $_POST at all
    $rememberMe = isset($_POST["remember_me"]);
    
    // -----------------------------------------------------------
    // STEP 3B: Validate that the user filled in both fields
    // -----------------------------------------------------------
    
    // We check BOTH fields to make sure neither is empty
    if (empty($username) || empty($password)) {
        // If either field is empty, set an error message
        $loginError = "Please enter both username and password.";
        
    } else {
        // Both fields are filled, so we can try to log the user in
        
        // -----------------------------------------------------------
        // STEP 3C: Look up the user in the database
        // -----------------------------------------------------------
        
        // Build a SQL query to find a user with the matching username
        // :username is a placeholder
        $query = "SELECT user_id, username, password_hash FROM users WHERE username = :username";
        
        // Prepare the query for execution
        $statement = $connection->prepare($query);
        
        // Replace the :username placeholder with the actual username from the form
        $statement->bindParam(':username', $username, PDO::PARAM_STR);
        
        // Execute the query (actually run it on the database)
        $statement->execute();
        
        // Fetch the result from the database
        // If no user found, $user will be false (or null)
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        
        // -----------------------------------------------------------
        // STEP 3D: Check if user exists AND password is correct
        // -----------------------------------------------------------
        
        // We need to check TWO things:
        // 1. Did we find a user? ($user will be an array if yes, false if no)
        // 2. Does the password match?
        
        // password_verify() compares the plain text password with the hashed password
        // It returns true if they match, false if they don't
        // Why use this? Passwords are stored as hashes in the database for security
        if ($user && password_verify($password, $user['password_hash'])) {
            // SUCCESS! The username exists AND the password is correct
            
            
            // -------------------------------------------------------
            // STEP 3E: Store user information in the session
            // -------------------------------------------------------
            
            // Sessions let us "remember" the user as they move between pages
            // We store data in the $_SESSION array
            
            // Set a flag to indicate the user is logged in
            $_SESSION["loggedIn"] = true;
            
            // Store the user's ID so we know WHO is logged in
            $_SESSION["user_id"] = $user['user_id'];
            
            // Store the username so we can display it on other pages
            $_SESSION["username"] = $user['username'];
            
            
            // -------------------------------------------------------
            // STEP 3E-2: Handle "Remember Me" functionality
            // -------------------------------------------------------
            
            // Check if the user checked the "Remember Me" checkbox
            if ($rememberMe) {
                // The user wants to stay logged in on this device
                
                // Generate a random token (a secret code)
                // bin2hex() converts binary data to a readable string
                // random_bytes(32) creates 32 random bytes (very secure and unpredictable)
                // The result is a 64-character string like "a3f9d8e7c2b1..."
                $rememberToken = bin2hex(random_bytes(32));
                
                // Save this token in the database for this user
                // We need this so we can verify the token later
                $updateQuery = "UPDATE users SET remember_token = :token WHERE user_id = :user_id";
                $updateStatement = $connection->prepare($updateQuery);
                $updateStatement->bindParam(':token', $rememberToken, PDO::PARAM_STR);
                $updateStatement->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
                $updateStatement->execute();
                
                // Store the token in a cookie on the user's computer
                // setcookie() creates a small file on the user's browser
                // Parameters explained:
                // 1. "remember_token" = the name of the cookie
                // 2. $rememberToken = the value (our random string)
                // 3. time() + (30 * 24 * 60 * 60) = expiration time (30 days from now)
                //    time() = current timestamp in seconds
                //    30 days * 24 hours * 60 minutes * 60 seconds = 2,592,000 seconds
                // 4. "/" = the cookie works for all pages on this website
                setcookie("remember_token", $rememberToken, time() + (30 * 24 * 60 * 60), "/");
            }
            
            
            // -------------------------------------------------------
            // STEP 3F: Redirect to the listings page
            // -------------------------------------------------------
            
            // header("Location: ...") tells the browser to go to a different page
            // This happens automatically - the user's browser will navigate away
            header("Location: /plantbnb/listings/listings.php");
            
            // exit() stops the PHP script immediately
            // Why? Because after a redirect, we don't want any more code to run
            exit();
            
        } else {
            // FAILURE! Either the username doesn't exist OR the password is wrong
            
            // Set an error message
            // We use a generic message for security (don't tell hackers if username exists)
            $loginError = "Invalid username or password.";
        }
    }
}
?>

<!-- ============================================================ -->
<!-- HTML SECTION STARTS HERE                                     -->
<!-- ============================================================ -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- viewport makes the page work on mobile phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlantBnB</title>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-12 col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h3 class="mb-0">Login</h3>
                    </div>

                    <div class="card-body">
                        <?php
                            if (!empty($loginError)) {
                                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                                echo htmlspecialchars($loginError);
                                echo "</div>";
                            }
                        ?>

                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input" <?php if(isset($_POST['remember_me'])) { echo 'checked'; } ?>>
                                    <label for="remember_me" class="form-check-label">Remember me for 30 days</label>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Login</button>
                            </div>
                        </form>
                    </div>

                    <div class="card-footer bg-light text-center">
                        <p class="mb-0">Don't have an account? <a href="registration.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>