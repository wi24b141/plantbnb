<?php
// ============================================
// REGISTRATION PAGE - PHP LOGIC (TOP)
// ============================================

// Include the database connection from db.php
require_once 'db.php';

// Initialize variables to track the registration process
$errors = [];
$successMessage = '';
$formSubmitted = false;

// Initialize form field variables so they exist from the start
// This prevents "Undefined variable" warnings when the page first loads
// Before form submission, these are empty strings
// After form submission, they contain the user's input
$username = '';
$email = '';
$password = '';
$passwordConfirm = '';

// Check if the form was submitted via POST method
// We use $_SERVER['REQUEST_METHOD'] to check the HTTP method safely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process it
    $formSubmitted = true;

    // Get form data from $_POST and trim whitespace
    // trim() removes spaces before and after the input
    // Using ?? "" provides a default empty string if the key doesn't exist
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    // ============================================
    // VALIDATION LOGIC
    // ============================================

    // Validate Username
    // Username is required and must be at least 3 characters
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must not exceed 50 characters.';
    }

    // Validate Email
    // Email is required and must be in valid format
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var() checks if email format is valid
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate Password
    // Password is required and must be at least 6 characters
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // Validate Password Confirmation
    // The two password fields must match exactly
    if (empty($passwordConfirm)) {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    // ============================================
    // DATABASE CHECK & REGISTRATION
    // ============================================

    // Only proceed with database operations if there are no validation errors
    if (empty($errors)) {
        try {
            // Check if the email already exists in the database
            // We use a prepared statement to prevent SQL injection
            $checkEmailQuery = "SELECT user_id FROM users WHERE email = :email";
            $checkEmailStatement = $connection->prepare($checkEmailQuery);
            $checkEmailStatement->bindParam(':email', $email, PDO::PARAM_STR);
            $checkEmailStatement->execute();

            // fetch() returns one row or null if not found
            $existingUser = $checkEmailStatement->fetch(PDO::FETCH_ASSOC);

            // If the email already exists, add an error message
            if ($existingUser) {
                $errors[] = 'This email is already registered. Please use a different email or try logging in.';
            } else {
                // Email doesn't exist, so we can create the new user
                // Hash the password using password_hash() for security
                // password_hash() uses bcrypt algorithm to securely encrypt the password
                // We can never recover the original password from this hash
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                // Insert the new user into the database
                $insertUserQuery = "
                    INSERT INTO users (username, email, password_hash, is_verified, created_at)
                    VALUES (:username, :email, :password_hash, 0, NOW())
                ";

                // Prepare the insert statement
                $insertUserStatement = $connection->prepare($insertUserQuery);

                // Bind the parameters to prevent SQL injection
                $insertUserStatement->bindParam(':username', $username, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':email', $email, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);

                // Execute the insert
                $insertUserStatement->execute();

                // Registration was successful!
                $successMessage = 'Registration successful! You can now log in.';

                // Clear the form fields after successful registration
                $username = '';
                $email = '';
                $password = '';
                $passwordConfirm = '';
            }

        } catch (PDOException $error) {
            // If a database error occurs, catch it and display a friendly message
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
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         REGISTRATION PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <div class="container mt-5 mb-5">
        <!-- Row with responsive column sizing -->
        <!-- col-12 = full width on mobile -->
        <!-- col-md-6 = half width on desktop -->
        <!-- offset-md-3 = centers the form on desktop -->
        <div class="row">
            <div class="col-12 col-md-6 offset-md-3">
                <!-- Registration Card -->
                <div class="card shadow-sm">
                    <!-- Card Header with title -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Create Account</h3>
                    </div>

                    <!-- Card Body with form -->
                    <div class="card-body">
                        <!-- Display success message if registration was successful -->
                        <?php
                            if (!empty($successMessage)) {
                                // Success alert - green background
                                // alert-dismissible allows user to close the alert
                                echo "<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                                echo htmlspecialchars($successMessage);
                                echo "  <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                                echo "</div>";
                            }
                        ?>

                        <!-- Display error messages if validation fails -->
                        <?php
                            if (!empty($errors)) {
                                // Error alert container - red background
                                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                                echo "  <strong>Please fix the following errors:</strong>";
                                echo "  <ul class=\"mb-0 mt-2\">";

                                // Loop through each error and display it as a list item
                                foreach ($errors as $error) {
                                    echo "    <li>" . htmlspecialchars($error) . "</li>";
                                }

                                echo "  </ul>";
                                echo "</div>";
                            }
                        ?>

                        <!-- Registration Form -->
                        <!-- method="POST" sends data to this same file for processing -->
                        <!-- action="" means submit to the current page -->
                        <form method="POST" action="">
                            <!-- Username Field -->
                            <!-- mb-3 = adds margin-bottom for spacing on touch screens -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <!-- value keeps the entered text in the field if validation fails -->
                                <!-- Wrap value in htmlspecialchars() to prevent XSS attacks -->
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    placeholder="Choose a username (3-50 characters)" 
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">Must be at least 3 characters</small>
                            </div>

                            <!-- Email Field -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="your@email.com" 
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">We'll never share your email</small>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <!-- type="password" hides the characters as you type for security -->
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Create a strong password" 
                                    required
                                >
                                <small class="text-muted d-block mt-1">Must be at least 6 characters</small>
                            </div>

                            <!-- Password Confirmation Field -->
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    placeholder="Re-enter your password" 
                                    required
                                >
                                <small class="text-muted d-block mt-1">Must match the password above</small>
                            </div>

                            <!-- Submit Button -->
                            <!-- d-grid = full width button on mobile -->
                            <!-- gap-2 = adds spacing inside the button area -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Card Footer with login link -->
                    <div class="card-footer bg-light text-center">
                        <small class="text-muted">
                            Already have an account? 
                            <a href="login.php">Log in here</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>