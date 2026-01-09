<?php
// ============================================
// STEP 1: INCLUDE REQUIRED FILES
// ============================================
// WHY: We need these files to make this page work
// - header.php: Contains the Bootstrap CSS link and starts the session
// - user-auth.php: Checks if user is logged in (redirects if not)
// - db.php: Contains the database connection variable ($connection)
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ============================================
// STEP 2: GET THE LOGGED-IN USER'S ID
// ============================================
// WHY: We need to know WHO is giving the rating (the rater)
// intval() converts the session value to an integer for security
$currentUserID = intval($_SESSION['user_id']);

// ============================================
// STEP 3: INITIALIZE VARIABLES
// ===========================================
// WHY: We set all variables to empty values BEFORE using them
// This prevents "undefined variable" errors in PHP
$allUsers = [];           // Will hold all users we can rate
$successMessage = '';     // Will hold success message after rating
$errorMessage = '';       // Will hold error message if something goes wrong

// ============================================
// STEP 4: PROCESS FORM SUBMISSION
// ============================================
// WHY: When user clicks "Submit Rating" button, we need to save the rating to database
// We check if the form was submitted using POST method

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // STEP 4A: Get the form data
    // WHY: We need to know which user to rate and what rating (1-5)
    
    // Get the user ID of the person being rated
    $ratedUserID = intval($_POST['rated_user_id']);
    
    // Get the rating value (1 to 5)
    $rating = intval($_POST['rating']);
    
    // STEP 4B: Validate the form data
    // WHY: We need to check if user filled in required fields correctly
    
    // Check if a user was selected
    if ($ratedUserID === 0) {
        $errorMessage = 'Please select a user to rate.';
    }
    // Check if rating is between 1 and 5
    else if ($rating < 1 || $rating > 5) {
        $errorMessage = 'Please select a rating between 1 and 5.';
    }
    // Check if user is trying to rate themselves
    else if ($ratedUserID === $currentUserID) {
        $errorMessage = 'You cannot rate yourself.';
    }
    // Everything looks good
    else {
        
        // STEP 4C: Save the rating to database
        // WHY: We use try-catch to handle database errors safely
        
        try {
            
            // Write the SQL query to insert a new rating
            // WHY: We need to save who rated whom and the rating value
            $insertQuery = "
                INSERT INTO ratings 
                (rater_user_id, rated_user_id, rating)
                VALUES
                (:raterUserID, :ratedUserID, :rating)
            ";
            
            // PREPARE the SQL query
            // WHY: This separates SQL code from data to prevent SQL injection attacks
            $insertStatement = $connection->prepare($insertQuery);
            
            // BIND all the parameters
            // WHY: This safely inserts our data into the SQL query
            $insertStatement->bindParam(':raterUserID', $currentUserID, PDO::PARAM_INT);
            $insertStatement->bindParam(':ratedUserID', $ratedUserID, PDO::PARAM_INT);
            $insertStatement->bindParam(':rating', $rating, PDO::PARAM_INT);
            
            // EXECUTE the query
            // WHY: This actually saves the rating to the database
            $insertStatement->execute();
            
            // Show success message
            $successMessage = 'Rating submitted successfully!';
            
        } catch (PDOException $error) {
            // If database error occurs, save the error message
            $errorMessage = 'Database error: ' . $error->getMessage();
        }
    }
}

// ============================================
// STEP 5: FETCH ALL USERS FROM DATABASE
// ============================================
// WHY: We need to show a dropdown list of all users that can be rated
// We use try-catch to handle database errors safely

try {
    
    // Write the SQL query to get all users EXCEPT the current user
    // WHY: We don't want user to rate themselves
    $usersQuery = "
        SELECT 
            user_id,
            username
        FROM users
        WHERE user_id != :currentUserID
        ORDER BY username ASC
    ";
    
    // PREPARE the SQL query
    $usersStatement = $connection->prepare($usersQuery);
    
    // BIND the current user ID
    // WHY: This excludes the logged-in user from the list
    $usersStatement->bindParam(':currentUserID', $currentUserID, PDO::PARAM_INT);
    
    // EXECUTE the query
    $usersStatement->execute();
    
    // FETCH all users as an array
    // WHY: We need all users to display in the dropdown menu
    $allUsers = $usersStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    // If database error occurs, save the error message
    $errorMessage = 'Database error: ' . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Mobile responsive meta tag -->
    <!-- WHY: This makes the page scale correctly on mobile phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate User</title>
</head>
<body>
    <!-- Main Container -->
    <!-- WHY: container class adds padding and centers content on all screen sizes -->
    <div class="container mt-4">
        
        <!-- Page Title -->
        <!-- WHY: mb-4 adds bottom margin spacing -->
        <h1 class="mb-4">Rate User</h1>
        
        <!-- =============================================================================
             SECTION 1: Success Message (if rating was submitted successfully)
             ============================================================================= -->
        <?php
            // Check if there is a success message to display
            if (!empty($successMessage)) {
                // Display the success message in a green alert box
                echo '<div class="alert alert-success" role="alert">';
                // Use htmlspecialchars to prevent XSS attacks
                echo htmlspecialchars($successMessage);
                echo '</div>';
            }
        ?>
        
        <!-- =============================================================================
             SECTION 2: Error Message (if any error occurred)
             ============================================================================= -->
        <?php
            // Check if there is an error message to display
            if (!empty($errorMessage)) {
                // Display the error message in a red alert box
                echo '<div class="alert alert-danger" role="alert">';
                // Use htmlspecialchars to prevent XSS attacks
                echo htmlspecialchars($errorMessage);
                echo '</div>';
            }
        ?>
        
        <!-- =============================================================================
             SECTION 3: Rating Form
             WHY: This form lets user select a user to rate, choose rating, and add comment
             ============================================================================= -->
        <div class="row">
            <!-- col-12 = full width on mobile -->
            <!-- col-md-8 = 2/3 width on desktop -->
            <!-- offset-md-2 = center the form on desktop by adding left margin -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- Card for better visual separation -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        
                        <!-- Form starts here -->
                        <!-- WHY: action="" means form submits to the same page (rate-user.php) -->
                        <!-- WHY: method="POST" is safer than GET for submitting data -->
                        <form action="" method="POST">
                            
                            <!-- =============================================================================
                                 FIELD 1: Select User to Rate (Dropdown Menu)
                                 ============================================================================= -->
                            <!-- WHY: mb-3 adds bottom margin spacing between form fields -->
                            <div class="mb-3">
                                <!-- Label for dropdown -->
                                <label for="rated_user_id" class="form-label">Select User to Rate</label>
                                
                                <!-- Dropdown menu -->
                                <!-- WHY: form-select makes dropdown look nice with Bootstrap styling -->
                                <!-- WHY: required means user MUST select a user before submitting -->
                                <select name="rated_user_id" id="rated_user_id" class="form-select" required>
                                    <!-- Default option when no user is selected -->
                                    <option value="">-- Choose a user --</option>
                                    
                                    <?php
                                        // Loop through all users and create an option for each
                                        foreach ($allUsers as $user) {
                                            // Get user ID and username
                                            $userID = intval($user['user_id']);
                                            $username = htmlspecialchars($user['username']);
                                            
                                            // Create dropdown option
                                            // WHY: value is the user_id, display text is the username
                                            echo '<option value="' . $userID . '">' . $username . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- =============================================================================
                                 FIELD 2: Rating (1 to 5 Stars)
                                 ============================================================================= -->
                            <div class="mb-3">
                                <!-- Label for rating dropdown -->
                                <label for="rating" class="form-label">Rating (1 to 5 Stars)</label>
                                
                                <!-- Dropdown for rating -->
                                <!-- WHY: User selects between 1 and 5 stars -->
                                <select name="rating" id="rating" class="form-select" required>
                                    <option value="">-- Choose rating --</option>
                                    <option value="1">⭐ (1 Star - Poor)</option>
                                    <option value="2">⭐⭐ (2 Stars - Fair)</option>
                                    <option value="3">⭐⭐⭐ (3 Stars - Good)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 Stars - Very Good)</option>
                                    <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Excellent)</option>
                                </select>
                            </div>
                            
                            <!-- =============================================================================
                                 SUBMIT BUTTON
                                 ============================================================================= -->
                            <!-- WHY: d-grid makes the button full-width on mobile -->
                            <!-- WHY: gap-2 adds spacing if there are multiple buttons -->
                            <div class="d-grid gap-2">
                                <!-- Submit button -->
                                <!-- WHY: type="submit" makes this button submit the form -->
                                <button type="submit" class="btn btn-success btn-lg">
                                    Submit Rating
                                </button>
                            </div>
                        </form>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</body>
</html>
