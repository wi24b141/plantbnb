<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';


// ============================================================
// STEP 2: Initialize variables
// ============================================================

// Create an empty error message variable
// We set it to empty string now, and will fill it with error text if login fails
// Why? Because we need this variable to exist even if the user hasn't submitted the form yet
$loginError = '';


// ============================================================
// STEP 3: Check if the user submitted the login form
// ============================================================

// $_SERVER["REQUEST_METHOD"] tells us HOW the page was loaded
// "GET" = user just visited the page (by clicking a link)
// "POST" = user submitted a form on this page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // -----------------------------------------------------------
    // STEP 3A: Get the data from the form
    // -----------------------------------------------------------
    
    // Get the username that the user typed in the form
    // $_POST["username"] gets the value from the input field with name="username"
    // trim() removes spaces from the beginning and end
    // ?? "" means "if username doesn't exist in $_POST, use empty string instead"
    $username = trim($_POST["username"] ?? "");
    
    // Get the password that the user typed in the form
    // Same process as username
    $password = trim($_POST["password"] ?? "");
    
    // Get the "remember me" checkbox value
    // If the checkbox was checked, $_POST["remember_me"] will be "on"
    // If not checked, it won't exist in $_POST at all
    // isset() checks if the variable exists
    $rememberMe = isset($_POST["remember_me"]);
    
    
    // -----------------------------------------------------------
    // STEP 3B: Validate that the user filled in both fields
    // -----------------------------------------------------------
    
    // empty() returns true if a variable is an empty string
    // We check BOTH fields to make sure neither is empty
    if (empty($username) || empty($password)) {
        // If either field is empty, set an error message
        // This error will be displayed in the HTML below
        $loginError = "Please enter both username and password.";
        
    } else {
        // Both fields are filled, so we can try to log the user in
        
        
        // -----------------------------------------------------------
        // STEP 3C: Look up the user in the database
        // -----------------------------------------------------------
        
        // Build a SQL query to find a user with the matching username
        // We SELECT three columns: user_id, username, and password_hash
        // :username is a placeholder (we fill it in below)
        // Why placeholder? To prevent SQL injection attacks
        $query = "SELECT user_id, username, password_hash FROM users WHERE username = :username";
        
        // Prepare the query for execution
        // This creates a PDO statement object that is safe from SQL injection
        $statement = $connection->prepare($query);
        
        // Replace the :username placeholder with the actual username from the form
        // PDO::PARAM_STR means we are binding a string value
        $statement->bindParam(':username', $username, PDO::PARAM_STR);
        
        // Execute the query (actually run it on the database)
        $statement->execute();
        
        // Fetch the result from the database
        // fetch() gets ONE row from the results
        // PDO::FETCH_ASSOC means "return the row as an associative array"
        // If no user found, $user will be false (or null)
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        
        // -----------------------------------------------------------
        // STEP 3D: Check if user exists AND password is correct
        // -----------------------------------------------------------
        
        // We need to check TWO things:
        // 1. Did we find a user? ($user will be an array if yes, false if no)
        // 2. Does the password match? (use password_verify to check)
        
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
<body>
    <!-- <main> is the main content area of the page -->
    <!-- container: Bootstrap class that centers content and adds padding on sides -->
    <!-- py-4: Bootstrap class that adds padding on top and bottom -->
    <main class="container py-4">
        
        <!-- Bootstrap Grid System (Mobile-First Design) -->
        <!-- row: Creates a horizontal row for grid columns -->
        <!-- justify-content-center: Centers the columns horizontally -->
        <div class="row justify-content-center">
            
            <!-- Column Sizing (MOBILE-FIRST):
                 col-12 = On mobile (small screens), take up all 12 columns (full width)
                 col-md-8 = On tablets (medium screens), take up 8 out of 12 columns
                 col-lg-5 = On desktops (large screens), take up 5 out of 12 columns
                 This makes the form full-width on phones, narrower on tablets/desktops -->
            <div class="col-12 col-md-8 col-lg-5">
                
                <!-- Page Title -->
                <!-- mb-3: Margin bottom (spacing below the heading) -->
                <!-- text-center: Center align the text -->
                <h2 class="mb-3 text-center">Login</h2>

                
                <!-- ============================================ -->
                <!-- Display Error Message (if login failed)      -->
                <!-- ============================================ -->
                <?php
                    // Check if there is an error message to display
                    // empty() returns true if the string is ""
                    if (!empty($loginError)) {
                        // If there IS an error, display it in a Bootstrap alert box
                        
                        // alert: Bootstrap class for a notification box
                        // alert-danger: Makes the box red (for errors)
                        // role="alert": Accessibility feature for screen readers
                        echo "<div class=\"alert alert-danger\" role=\"alert\">";
                        
                        // Display the error message
                        // htmlspecialchars() converts special characters to prevent XSS attacks
                        // Why? If $loginError contained HTML/JavaScript, it could be dangerous
                        echo htmlspecialchars($loginError);
                        
                        echo "</div>";
                    }
                ?>

                
                <!-- ============================================ -->
                <!-- Login Form                                   -->
                <!-- ============================================ -->
                
                <!-- Form element:
                     action="" = Submit to the same page (login.php submits to itself)
                     method="post" = Send data via POST (not visible in URL)
                     
                     Bootstrap classes:
                     card: Creates a box with a border and rounded corners
                     p-4: Adds padding inside the card (spacing around content)
                     shadow-sm: Adds a small shadow effect to the card -->
                <form action="" method="post" class="card p-4 shadow-sm">
                    
                    <!-- ================================ -->
                    <!-- Username Input Field             -->
                    <!-- ================================ -->
                    
                    <!-- mb-3: Margin bottom (spacing below this field) -->
                    <!-- Why? So the inputs aren't crowded together on mobile -->
                    <div class="mb-3">
                        <!-- Label for the input field -->
                        <!-- for="username": Links this label to the input with id="username" -->
                        <!-- form-label: Bootstrap class for form labels -->
                        <label for="username" class="form-label">Username</label>
                        
                        <!-- Input field for username -->
                        <!-- type="text": A normal text input box -->
                        <!-- id="username": Unique identifier (links to the label) -->
                        <!-- name="username": This is the key used in $_POST["username"] -->
                        <!-- form-control: Bootstrap class that styles the input (full width, rounded) -->
                        <!-- required: HTML5 attribute that makes this field mandatory -->
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    
                    <!-- ================================ -->
                    <!-- Password Input Field             -->
                    <!-- ================================ -->
                    
                    <!-- Same structure as username field above -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        
                        <!-- type="password": Makes the input show dots/asterisks instead of text -->
                        <!-- name="password": This is the key used in $_POST["password"] -->
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    
                    <!-- ================================ -->
                    <!-- Remember Me Checkbox             -->
                    <!-- ================================ -->
                    
                    <!-- mb-3: Margin bottom (spacing below this field) -->
                    <div class="mb-3">
                        <!-- form-check: Bootstrap class for checkbox styling -->
                        <div class="form-check">
                            <!-- Checkbox Input:
                                 type="checkbox" = Creates a checkbox (a small box you can click)
                                 id="remember_me" = Unique identifier
                                 name="remember_me" = The key used in $_POST["remember_me"]
                                 class="form-check-input" = Bootstrap styling for checkboxes
                                 
                                 When checked: $_POST["remember_me"] will be "on"
                                 When unchecked: $_POST["remember_me"] won't exist at all -->
                            <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                            
                            <!-- Label for the checkbox -->
                            <!-- for="remember_me" links this label to the checkbox -->
                            <!-- form-check-label: Bootstrap styling for checkbox labels -->
                            <label for="remember_me" class="form-check-label">
                                Remember me for 30 days
                            </label>
                        </div>
                    </div>

                    
                    <!-- ================================ -->
                    <!-- Submit Button                    -->
                    <!-- ================================ -->
                    
                    <!-- d-grid: Bootstrap class that makes the button full-width -->
                    <!-- Why? On mobile, full-width buttons are easier to tap -->
                    <div class="d-grid">
                        <!-- type="submit": When clicked, this button submits the form -->
                        <!-- btn: Bootstrap button class -->
                        <!-- btn-success: Makes the button green -->
                        <!-- btn-lg: Makes the button larger (easier to tap on mobile) -->
                        <button type="submit" class="btn btn-success btn-lg">Login</button>
                    </div>

                    
                    <!-- ================================ -->
                    <!-- Link to Registration Page        -->
                    <!-- ================================ -->
                    
                    <!-- hr: Horizontal line (divider) -->
                    <!-- my-3: Margin top and bottom (spacing above and below the line) -->
                    <hr class="my-3">
                    
                    <!-- text-center: Center align the text -->
                    <!-- mb-0: Remove margin bottom (no extra space at the bottom) -->
                    <p class="text-center mb-0">
                        Don't have an account? 
                        
                        <!-- Link to the registration page -->
                        <!-- href="registration.php": The page to navigate to when clicked -->
                        <a href="registration.php">Register here</a>
                    </p>
                </form>
            </div>
        </div>
    </main>
</body>
</html>