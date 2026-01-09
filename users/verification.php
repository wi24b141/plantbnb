<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

// ============================================
// STEP 2: GET THE CURRENT USER'S ID
// ============================================

// The user-auth.php file puts the user_id in the session
// We get it here so we can use it in our database queries
// intval() converts it to an integer (this is a security measure)
$userID = intval($_SESSION['user_id']);

// ============================================
// STEP 3: INITIALIZE VARIABLES
// ============================================

// These variables will store data we need throughout the page
// We initialize them now so they exist even if errors occur later

// Will store all the user's information from the database
$user = null;

// Will store the path to the user's verification document (if they uploaded one)
$currentVerificationDocPath = '';

// Will store if the user is verified (1) or not (0)
$isVerified = 0;

// Will store success messages to show the user
$successMessage = '';

// Will store error messages to show the user
$errorMessage = '';

// ============================================
// STEP 4: FETCH CURRENT USER DATA FROM DATABASE
// ============================================

// We wrap database code in try-catch to handle errors safely
try {
    // We need to get the user's current verification status
    // This query gets: user_id, username, email, is_verified, verification_document_path
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            is_verified,
            verification_document_path
        FROM users
        WHERE user_id = :userID
    ";

    // Prepare the query to prevent SQL injection attacks
    // SQL injection is when hackers try to put malicious code in your query
    $userStatement = $connection->prepare($userQuery);

    // Replace :userID placeholder with the actual user ID
    // PDO::PARAM_INT tells PDO this is an integer
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the query (this actually runs it on the database)
    $userStatement->execute();

    // Get the result as an associative array
    // FETCH_ASSOC means we can access data like $user['username']
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found
    // If not found, something is wrong (maybe account was deleted)
    if (!$user) {
        // Destroy the session (log them out)
        session_destroy();
        // Send them to the login page
        header('Location: login.php');
        // Stop the script (nothing after this runs)
        exit();
    }

    // Now we extract the verification data from the user array
    
    // Get the is_verified value
    // If it is null, we set it to 0 (not verified)
    if ($user['is_verified'] === null) {
        $isVerified = 0;
    } else {
        $isVerified = intval($user['is_verified']);
    }
    
    // Get the verification document path
    // If it is null, we set it to empty string
    if ($user['verification_document_path'] === null) {
        $currentVerificationDocPath = '';
    } else {
        $currentVerificationDocPath = $user['verification_document_path'];
    }

} catch (PDOException $error) {
    // If the database query fails, we catch the error here
    // PDOException is the type of error that PDO throws
    // We store the error message so we can show it to the user
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 5: HANDLE FORM SUBMISSION
// ============================================

// Check if the form was submitted
// $_SERVER['REQUEST_METHOD'] tells us if this is a POST or GET request
// POST means the user submitted the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // The form was submitted, so we need to process the file upload
    
    // This variable will store the new document path
    // We start with the current path (in case upload fails, we keep the old one)
    $newVerificationDocPath = $currentVerificationDocPath;

    // ============================================
    // STEP 5A: UPLOAD THE VERIFICATION DOCUMENT
    // ============================================

    // Call the uploadFile() function from file-upload-helper.php
    // This function handles all the complex file upload logic for us
    // It returns:
    //   - A file path (string with /) if upload succeeded
    //   - An error message (string without /) if upload failed
    //   - null if no file was selected
    $verificationDocResult = uploadFile(
        'verification_document',                        // The name="" attribute from the form
        __DIR__ . '/../uploads/verification',          // Where to save the file
        ['image/jpeg', 'image/png', 'application/pdf'], // Allowed file types
        5 * 1024 * 1024                                 // Maximum size: 5MB (in bytes)
    );

    // Now we need to check what the uploadFile() function returned
    
    // Check if it's an error message
    // Error messages are strings that don't contain a / character
    // File paths are strings that DO contain a / character
    if (is_string($verificationDocResult) && strpos($verificationDocResult, '/') === false) {
        // Upload failed, store the error message
        $errorMessage = "Verification document: " . $verificationDocResult;
        
    } else if ($verificationDocResult !== null) {
        // Upload succeeded! The function returned a file path
        // Update our variable with the new path
        $newVerificationDocPath = $verificationDocResult;
        
    } else {
        // $verificationDocResult is null, which means no file was selected
        $errorMessage = "Please select an ID document to upload.";
    }

    // ============================================
    // STEP 5B: UPDATE DATABASE WITH NEW DOCUMENT PATH
    // ============================================

    // Only update the database if there are no errors
    // empty() returns true if the string is empty ("")
    if (empty($errorMessage)) {
        
        // Wrap database code in try-catch to handle errors
        try {
            // This query updates the user's verification_document_path in the database
            // IMPORTANT: We do NOT set is_verified = 1 here
            // The admin has to manually verify the document and set is_verified = 1
            // We just store the document path so the admin can review it later
            $updateQuery = "
                UPDATE users
                SET 
                    verification_document_path = :verificationDocPath
                WHERE user_id = :userID
            ";

            // Prepare the query to prevent SQL injection
            $updateStatement = $connection->prepare($updateQuery);

            // Bind the parameters
            // :verificationDocPath will be replaced with $newVerificationDocPath
            // :userID will be replaced with $userID
            $updateStatement->bindParam(':verificationDocPath', $newVerificationDocPath, PDO::PARAM_STR);
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Execute the query (this actually updates the database)
            $updateStatement->execute();

            // If we reach this line, the update was successful!
            // Set a success message to show the user
            $successMessage = "Your verification document has been uploaded successfully! An admin will review it soon.";

            // Update the local variable so the page shows the new document
            // This way the user sees the updated status immediately
            $currentVerificationDocPath = $newVerificationDocPath;

        } catch (PDOException $error) {
            // If the database update fails, catch the error
            $errorMessage = "Failed to upload verification document: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding (UTF-8 supports all languages/symbols) -->
    <meta charset="UTF-8">
    
    <!-- Viewport makes the page mobile-friendly -->
    <!-- width=device-width means use the phone's screen width -->
    <!-- initial-scale=1.0 means don't zoom in or out by default -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title shown in the browser tab -->
    <title>Account Verification - PlantBnB</title>
</head>
<body>
    <!-- Container centers content and adds padding on sides -->
    <!-- mt-4 = margin-top (adds space at the top) -->
    <div class="container mt-4">
        
        <!-- ============================================ -->
        <!-- SECTION 1: BACK TO DASHBOARD BUTTON -->
        <!-- ============================================ -->
        
        <!-- row = Bootstrap grid row -->
        <!-- mb-3 = margin-bottom (adds space below) -->
        <div class="row mb-3">
            <!-- col-12 = full width on mobile (12 out of 12 columns) -->
            <!-- col-md-8 = 8 out of 12 columns on desktop (narrower) -->
            <!-- offset-md-2 = push 2 columns from left on desktop (centers it) -->
            <div class="col-12 col-md-8 offset-md-2">
                <!-- Link styled as a button to go back to dashboard -->
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SECTION 2: SUCCESS MESSAGE (IF ANY) -->
        <!-- ============================================ -->
        
        <?php
            // Check if there is a success message to display
            // empty() returns false if the string has content
            if (!empty($successMessage)) {
                // Show a green success alert box
                // alert = Bootstrap alert box
                // alert-success = green background
                // role="alert" = tells screen readers this is an alert
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                
                // Display the success message
                // htmlspecialchars() prevents XSS attacks (security)
                echo htmlspecialchars($successMessage);
                
                // Close the div
                echo "</div>";
            }
        ?>

        <!-- ============================================ -->
        <!-- SECTION 3: ERROR MESSAGE (IF ANY) -->
        <!-- ============================================ -->
        
        <?php
            // Check if there is an error message to display
            if (!empty($errorMessage)) {
                // Show a red error alert box
                // alert-danger = red background
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                
                // Display the error message
                // htmlspecialchars() prevents XSS attacks
                echo htmlspecialchars($errorMessage);
                
                // Close the div
                echo "</div>";
            }
        ?>

        <!-- ============================================ -->
        <!-- SECTION 4: MAIN VERIFICATION CARD -->
        <!-- ============================================ -->
        
        <!-- mb-5 = large margin-bottom (space below) -->
        <div class="row mb-5">
            <!-- col-12 = full width on mobile -->
            <!-- col-md-8 = 8 columns on desktop (2/3 width) -->
            <!-- offset-md-2 = 2 columns offset on desktop (centers it) -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- card = Bootstrap card component (box with border and shadow) -->
                <!-- shadow-sm = small shadow (makes it look raised) -->
                <div class="card shadow-sm">
                    
                    <!-- ============================================ -->
                    <!-- CARD HEADER -->
                    <!-- ============================================ -->
                    
                    <!-- card-header = top section of card -->
                    <!-- bg-success = green background -->
                    <!-- text-white = white text -->
                    <div class="card-header bg-success text-white">
                        <!-- mb-0 = no margin-bottom (removes space below heading) -->
                        <h3 class="mb-0">Account Verification</h3>
                    </div>

                    <!-- ============================================ -->
                    <!-- CARD BODY -->
                    <!-- ============================================ -->
                    
                    <div class="card-body">
                        
                        <!-- ============================================ -->
                        <!-- SUBSECTION 4A: VERIFICATION STATUS -->
                        <!-- ============================================ -->
                        
                        <!-- mb-4 = medium margin-bottom -->
                        <div class="mb-4">
                            <h5 class="mb-3">Current Verification Status</h5>

                            <!-- text-center = center the text horizontally -->
                            <div class="text-center">
                                <?php
                                    // We need to show different messages based on verification status
                                    
                                    // Check if user is verified
                                    if ($isVerified == 1) {
                                        // User is verified - show green success message
                                        echo "<div class=\"alert alert-success\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Verified Account</h4>";
                                        echo "  <p class=\"mb-0\">Your account has been verified by an admin.</p>";
                                        echo "</div>";
                                        
                                    } else if (!empty($currentVerificationDocPath)) {
                                        // User uploaded a document but admin hasn't verified yet
                                        // Show yellow warning message
                                        echo "<div class=\"alert alert-warning\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Pending Verification</h4>";
                                        echo "  <p class=\"mb-0\">Your verification document has been uploaded and is waiting for admin review.</p>";
                                        echo "</div>";
                                        
                                    } else {
                                        // User has not uploaded any document yet
                                        // Show blue info message
                                        echo "<div class=\"alert alert-info\" role=\"alert\">";
                                        echo "  <h4 class=\"alert-heading\">Not Verified</h4>";
                                        echo "  <p class=\"mb-0\">Your account is not verified yet. Please upload an ID document below.</p>";
                                        echo "</div>";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 4B: WHY GET VERIFIED -->
                        <!-- ============================================ -->
                        
                        <div class="mb-4">
                            <h5 class="mb-3">Why Get Verified?</h5>
                            <!-- small = smaller text size -->
                            <ul class="small">
                                <li>Build trust with other PlantBnB users</li>
                                <li>Show a verified badge on your profile</li>
                                <li>Help create a safer plant-swapping community</li>
                            </ul>
                        </div>

                        <!-- ============================================ -->
                        <!-- SUBSECTION 4C: UPLOAD FORM (ONLY IF NOT VERIFIED) -->
                        <!-- ============================================ -->
                        
                        <?php
                            // Only show the upload form if user is not verified yet
                            if ($isVerified == 0) {
                                // User is not verified, show the form
                        ?>
                        
                            <!-- hr = horizontal line (divider) -->
                            <!-- my-4 = vertical margin (top and bottom) -->
                            <hr class="my-4">

                            <h5 class="mb-3">Upload ID Document</h5>

                            <!-- Instructions box -->
                            <!-- alert-light = light gray background -->
                            <div class="alert alert-light" role="alert">
                                <small>
                                    <strong>Accepted Documents:</strong> Government-issued ID, Driver's License, Passport<br>
                                    <strong>Privacy:</strong> Your document is only visible to administrators<br>
                                    <strong>File Format:</strong> JPG, PNG, or PDF (maximum 5MB)
                                </small>
                            </div>

                            <!-- ============================================ -->
                            <!-- UPLOAD FORM -->
                            <!-- ============================================ -->
                            
                            <!-- method="POST" = send data securely (not visible in URL) -->
                            <!-- action="" = submit to the same page (verification.php) -->
                            <!-- enctype="multipart/form-data" = REQUIRED for file uploads -->
                            <!-- Without enctype, file uploads will NOT work! -->
                            <form method="POST" action="" enctype="multipart/form-data">
                                
                                <!-- File input field -->
                                <!-- mb-3 = margin-bottom (spacing for touch screens) -->
                                <div class="mb-3">
                                    <!-- Label for the file input -->
                                    <!-- for="verification_document" links label to input -->
                                    <label for="verification_document" class="form-label">Select ID Document</label>
                                    
                                    <!-- File upload input -->
                                    <!-- type="file" = creates a file upload button -->
                                    <!-- name="verification_document" = this is how we access the file in PHP -->
                                    <!-- accept = limits which files can be selected -->
                                    <!-- required = user must select a file before submitting -->
                                    <input 
                                        type="file" 
                                        id="verification_document" 
                                        name="verification_document" 
                                        class="form-control" 
                                        accept=".jpg, .jpeg, .png, .pdf"
                                        required
                                    >
                                    
                                    <!-- Helper text below the input -->
                                    <!-- text-muted = gray text color -->
                                    <!-- d-block = display as block element (takes full width) -->
                                    <!-- mt-1 = small margin-top -->
                                    <small class="text-muted d-block mt-1">
                                        JPG, PNG, or PDF format. Maximum file size: 5MB
                                    </small>
                                </div>

                                <!-- Submit Button -->
                                <!-- d-grid = makes button full width -->
                                <!-- gap-2 = adds padding inside button area -->
                                <div class="d-grid gap-2">
                                    <!-- type="submit" = submits the form when clicked -->
                                    <!-- btn-success = green button -->
                                    <!-- btn-lg = large button (easier to tap on mobile) -->
                                    <button type="submit" class="btn btn-success btn-lg">
                                        Upload ID Document
                                    </button>
                                </div>
                            </form>

                        <?php
                            } else {
                                // User is already verified
                                // Show a message instead of the form
                                echo "<div class=\"alert alert-success text-center\" role=\"alert\">";
                                echo "  <p class=\"mb-0\">Your account is fully verified! No further action is needed.</p>";
                                echo "</div>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>