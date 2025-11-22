<?php
// This command is always necessary when dealing with sessions
// starts or RESUMES the session
session_start();

// Include the database connection
require_once 'db.php';

// Initialize error variable so it exists from page load
$loginError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from the login form
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    // Check if both fields are filled
    if (empty($username) || empty($password)) {
        $loginError = "Please enter both username and password.";
    } else {
        // Query the database to find the user by username
        // We use a prepared statement to prevent SQL injection attacks
        $query = "SELECT user_id, username, password_hash FROM users WHERE username = :username";
        $statement = $connection->prepare($query);
        $statement->bindParam(':username', $username, PDO::PARAM_STR);
        $statement->execute();

        // Fetch the user from the database
        // fetch() returns one row or null if not found
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        // Check if user was found AND password is correct
        // password_verify() checks if the entered password matches the hashed password in the database
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful! Store user info in session
            $_SESSION["loggedIn"] = true;
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];

            // Redirect to dashboard or home page after successful login
            header("Location: listings.php");
            exit();
        } else {
            // If user not found or password is wrong, show error
            $loginError = "Invalid username or password.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head-includes.php'; ?>
<body>
    <header class="container text-center my-4">
        <h1 class="site-brand" id="site-title">
            <span class="brand-text">&#x1FAB4;plantbnb</span>
        </h1>
    </header>

    <main class="container py-4">
        <!-- Mobile-first responsive grid -->
        <!-- col-12 = full width on phone, col-md-8 col-lg-5 = narrower on desktop -->
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <h2 class="mb-3 text-center">Login</h2>

                <!-- Display error message if login failed -->
                <?php
                    if (!empty($loginError)) {
                        echo "<div class=\"alert alert-danger\" role=\"alert\">";
                        echo htmlspecialchars($loginError);
                        echo "</div>";
                    }
                ?>

                <!-- Login form -->
                <!-- action="" submits to the same page for processing -->
                <!-- method="post" sends data securely without showing it in the URL -->
                <form action="" method="post" class="card p-4 shadow-sm">
                    <!-- Username input field -->
                    <!-- mb-3 adds bottom margin for touch-friendly spacing -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    <!-- Password input field -->
                    <!-- type="password" hides characters for security -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    <!-- Submit button -->
                    <!-- d-grid makes button full width on mobile -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Login</button>
                    </div>

                    <!-- Link to registration page for new users -->
                    <hr class="my-3">
                    <p class="text-center mb-0">
                        Don't have an account? 
                        <a href="registration.php">Register here</a>
                    </p>
                </form>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>