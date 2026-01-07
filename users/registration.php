<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 2: INITIALIZE ALL VARIABLES
// ============================================
// Why: We must declare variables before using them to avoid PHP warnings

// This array will hold all error messages if validation fails
$errors = [];

// This string will hold the success message after registration
$successMessage = '';

// These variables will store the user's form input
// We initialize them as empty strings so they exist from the beginning
$username = '';
$email = '';
$password = '';
$passwordConfirm = '';

// ============================================
// STEP 3: CHECK IF FORM WAS SUBMITTED
// ============================================
// Why: We only want to process the form when the user clicks "Create Account"
// The form uses method="POST", so we check if the request method is POST

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The user submitted the form, so we process it now

    // ============================================
    // STEP 4: GET FORM DATA
    // ============================================
    // Why: We need to get the data the user typed into the form fields
    // $_POST is an array that contains all form data sent via POST method
    // trim() removes extra spaces from the beginning and end of the text
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['password_confirm']);

    // ============================================
    // STEP 5: VALIDATE USERNAME
    // ============================================
    // Why: We must check if the username meets our requirements
    
    // Check if username is empty
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    
    // Check if username is too short
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    }
    
    // Check if username is too long
    if (strlen($username) > 50) {
        $errors[] = 'Username must not exceed 50 characters.';
    }

    // ============================================
    // STEP 6: VALIDATE EMAIL
    // ============================================
    // Why: We need a valid email address to contact the user
    
    // Check if email is empty
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    
    // Check if email format is valid (must contain @ and proper structure)
    // filter_var() is a PHP function that validates email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // ============================================
    // STEP 7: VALIDATE PASSWORD
    // ============================================
    // Why: We need a password that is secure enough
    
    // Check if password is empty
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    // Check if password is too short
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // ============================================
    // STEP 8: VALIDATE PASSWORD CONFIRMATION
    // ============================================
    // Why: We want to make sure the user typed their password correctly twice
    
    // Check if password confirmation is empty
    if (empty($passwordConfirm)) {
        $errors[] = 'Please confirm your password.';
    }
    
    // Check if both passwords match exactly
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    // ============================================
    // STEP 9: SAVE TO DATABASE (ONLY IF NO ERRORS)
    // ============================================
    // Why: We only want to save the user if all validation passed
    // count($errors) returns how many errors we have
    
    if (count($errors) === 0) {
        // No errors, so we can proceed with database operations
        
        try {
            // ============================================
            // STEP 9A: CHECK IF EMAIL ALREADY EXISTS
            // ============================================
            // Why: Each email can only be registered once
            // We search the users table to see if this email is already taken
            
            $checkEmailQuery = "SELECT user_id FROM users WHERE email = :email";
            
            // prepare() creates a prepared statement to prevent SQL injection
            $checkEmailStatement = $connection->prepare($checkEmailQuery);
            
            // bindParam() safely attaches the email value to the query
            // PDO::PARAM_STR means we are binding a string type
            $checkEmailStatement->bindParam(':email', $email, PDO::PARAM_STR);
            
            // execute() runs the query on the database
            $checkEmailStatement->execute();
            
            // fetch() gets one row from the results
            // If the email exists, we get a row. If not, we get false
            $existingUser = $checkEmailStatement->fetch(PDO::FETCH_ASSOC);

            // ============================================
            // STEP 9B: HANDLE EMAIL CHECK RESULT
            // ============================================
            
            if ($existingUser) {
                // The email already exists in the database
                $errors[] = 'This email is already registered.';
            } else {
                // The email is available, so we can create the new user
                
                // ============================================
                // STEP 9C: HASH THE PASSWORD
                // ============================================
                // Why: We never store passwords in plain text for security
                // password_hash() encrypts the password so even admins cannot see it
                // PASSWORD_BCRYPT is the encryption algorithm (very secure)
                
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                // ============================================
                // STEP 9D: INSERT NEW USER INTO DATABASE
                // ============================================
                // Why: This creates the new user account in the users table
                
                $insertUserQuery = "INSERT INTO users (username, email, password_hash, is_verified, created_at) VALUES (:username, :email, :password_hash, 0, NOW())";
                
                // Prepare the insert statement
                $insertUserStatement = $connection->prepare($insertUserQuery);
                
                // Bind all the values safely
                $insertUserStatement->bindParam(':username', $username, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':email', $email, PDO::PARAM_STR);
                $insertUserStatement->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
                
                // Execute the insert to save the user
                $insertUserStatement->execute();

                // ============================================
                // STEP 9E: SHOW SUCCESS MESSAGE
                // ============================================
                
                $successMessage = 'Registration successful! You can now log in.';
                
                // Clear all form fields so they appear empty after success
                $username = '';
                $email = '';
                $password = '';
                $passwordConfirm = '';
            }

        } catch (PDOException $error) {
            // If any database error happens, we catch it here
            // Why: We don't want to show technical database errors to users
            $errors[] = 'A database error occurred. Please try again later.';
        }
    }
}

// ============================================
// STEP 10: DISPLAY THE HTML PAGE
// ============================================
// Everything below this line is the HTML that the user sees in their browser
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Why: viewport makes the page work on mobile phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PlantBnB</title>
</head>
<body>
    <!-- container = Bootstrap class that centers content and adds padding -->
    <!-- mt-5 = margin-top (spacing from the top) -->
    <!-- mb-5 = margin-bottom (spacing from the bottom) -->
    <div class="container mt-5 mb-5">
        
        <!-- row = Bootstrap class that creates a horizontal row for columns -->
        <div class="row">
            
            <!-- col-12 = full width (12 out of 12 columns) on mobile phones -->
            <!-- col-md-6 = half width (6 out of 12 columns) on desktop -->
            <!-- offset-md-3 = push 3 columns to the right on desktop to center the form -->
            <div class="col-12 col-md-6 offset-md-3">
                
                <!-- card = Bootstrap component that creates a box with a border -->
                <div class="card">
                    
                    <!-- card-header = top section of the card -->
                    <!-- bg-success = green background color -->
                    <!-- text-white = white text color -->
                    <div class="card-header bg-success text-white">
                        <h3>Create Account</h3>
                    </div>

                    <!-- card-body = main content area of the card -->
                    <div class="card-body">
                        
                        <?php
                        // ============================================
                        // DISPLAY SUCCESS MESSAGE
                        // ============================================
                        // Why: If registration succeeded, we show a green success box
                        
                        if (!empty($successMessage)) {
                            // alert = Bootstrap class for message boxes
                            // alert-success = green color for success
                            echo "<div class=\"alert alert-success\">";
                            // htmlspecialchars() prevents XSS attacks by escaping HTML characters
                            echo htmlspecialchars($successMessage);
                            echo "</div>";
                        }
                        ?>

                        <?php
                        // ============================================
                        // DISPLAY ERROR MESSAGES
                        // ============================================
                        // Why: If validation failed, we show all errors in a red box
                        
                        if (count($errors) > 0) {
                            // alert-danger = red color for errors
                            echo "<div class=\"alert alert-danger\">";
                            echo "<strong>Please fix the following errors:</strong>";
                            echo "<ul>";
                            
                            // Loop through each error and display it as a list item
                            // Why: We want to show all errors at once so user can fix them all
                            foreach ($errors as $error) {
                                // htmlspecialchars() prevents XSS attacks
                                echo "<li>" . htmlspecialchars($error) . "</li>";
                            }
                            
                            echo "</ul>";
                            echo "</div>";
                        }
                        ?>

                        <!-- ============================================ -->
                        <!-- REGISTRATION FORM -->
                        <!-- ============================================ -->
                        <!-- Why: This form collects username, email, and password from the user -->
                        <!-- method="POST" = sends data securely (not visible in URL) -->
                        <!-- action="" = submit to this same page (registration.php) -->
                        
                        <form method="POST" action="">
                            
                            <!-- ============================================ -->
                            <!-- USERNAME FIELD -->
                            <!-- ============================================ -->
                            <!-- mb-3 = margin-bottom for spacing (important on mobile touch screens) -->
                            <div class="mb-3">
                                <!-- for="username" connects this label to the input field below -->
                                <label for="username" class="form-label">Username</label>
                                
                                <!-- type="text" = regular text input -->
                                <!-- form-control = Bootstrap class that styles the input -->
                                <!-- name="username" = this is the key used in $_POST['username'] -->
                                <!-- value = pre-fills the field with what the user typed before -->
                                <!-- required = HTML5 validation (browser checks if field is empty) -->
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="username" 
                                    name="username" 
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required
                                >
                            </div>

                            <!-- ============================================ -->
                            <!-- EMAIL FIELD -->
                            <!-- ============================================ -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                
                                <!-- type="email" = special input that shows email keyboard on mobile -->
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    required
                                >
                            </div>

                            <!-- ============================================ -->
                            <!-- PASSWORD FIELD -->
                            <!-- ============================================ -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                
                                <!-- type="password" = hides characters as user types (shows dots) -->
                                <!-- Why: We never pre-fill passwords for security -->
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    required
                                >
                            </div>

                            <!-- ============================================ -->
                            <!-- PASSWORD CONFIRMATION FIELD -->
                            <!-- ============================================ -->
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                
                                <!-- Why: User must type password twice to avoid typos -->
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password_confirm" 
                                    name="password_confirm" 
                                    required
                                >
                            </div>

                            <!-- ============================================ -->
                            <!-- SUBMIT BUTTON -->
                            <!-- ============================================ -->
                            <!-- d-grid = makes button full width -->
                            <!-- gap-2 = adds internal spacing -->
                            <!-- Why: Full-width buttons are easier to tap on mobile phones -->
                            <div class="d-grid gap-2">
                                <!-- type="submit" = clicking this button submits the form -->
                                <!-- btn = Bootstrap button class -->
                                <!-- btn-success = green button color -->
                                <!-- btn-lg = large size button -->
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ============================================ -->
                    <!-- CARD FOOTER WITH LOGIN LINK -->
                    <!-- ============================================ -->
                    <!-- Why: If user already has account, they can go to login page -->
                    <!-- card-footer = bottom section of the card -->
                    <!-- bg-light = light gray background -->
                    <!-- text-center = centers the text horizontally -->
                    <div class="card-footer bg-light text-center">
                        <p>
                            Already have an account? 
                            <!-- Standard link to login.php -->
                            <a href="login.php">Log in here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>