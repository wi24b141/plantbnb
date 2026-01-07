<?php
// ============================================
// STEP 1: INCLUDE REQUIRED FILES
// ============================================
// WHY: We need these files to make this page work
// - header.php: Contains the Bootstrap CSS link and starts the session
// - user-auth.php: Checks if user is logged in (redirects if not)
// - db.php: Contains the database connection variable ($connection)
// - file-upload-helper.php: Contains the uploadFile() function for handling file uploads
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

// ============================================
// STEP 2: GET THE LOGGED-IN USER'S ID
// ============================================
// WHY: We need to know which user is editing their profile
// intval() converts the session value to an integer for security
// This prevents SQL injection if someone tampers with the session
$userID = intval($_SESSION['user_id']);

// ============================================
// STEP 3: INITIALIZE VARIABLES
// ============================================
// WHY: We set all variables to empty values BEFORE using them
// This prevents "undefined variable" errors in PHP
$user = null;
$bio = '';
$currentProfilePhotoPath = '';
$successMessage = '';
$errorMessage = '';

// ============================================
// STEP 4: FETCH CURRENT USER DATA FROM DATABASE
// ============================================
// WHY: We need to display the user's current bio and photo in the form
// We use a try-catch block to handle database errors safely

try {
    // Write the SQL query to get the user's current profile information
    // WHY: We need the bio and profile_photo_path to pre-fill the form
    $userQuery = "
        SELECT 
            user_id,
            username,
            email,
            bio,
            profile_photo_path
        FROM users
        WHERE user_id = :userID
    ";

    // Prepare the SQL statement
    // WHY: Using prepare() prevents SQL injection attacks
    // NEVER put variables directly in the SQL string!
    $userStatement = $connection->prepare($userQuery);

    // Bind the user ID to the placeholder
    // WHY: This safely inserts the userID into the query
    // PDO::PARAM_INT tells PDO that this is an integer
    $userStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

    // Execute the query
    // WHY: This actually runs the SQL command on the database
    $userStatement->execute();

    // Fetch the result as an associative array
    // WHY: This gives us an array like ['user_id' => 5, 'bio' => 'Hello', ...]
    // fetch() returns one row (the user's data)
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found in the database
    // WHY: If user_id doesn't exist, something is wrong (maybe user was deleted)
    if (!$user) {
        // User not found, destroy the session and send them to login
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Pre-fill the form fields with current user data
    // WHY: When the page loads, we want to show the user their existing bio and photo
    
    // Check if bio exists in the database
    if (isset($user['bio'])) {
        // Bio exists, use it
        $bio = $user['bio'];
    } else {
        // Bio is NULL in database, use empty string
        $bio = '';
    }
    
    // Check if profile_photo_path exists in the database
    if (isset($user['profile_photo_path'])) {
        // Photo path exists, use it
        $currentProfilePhotoPath = $user['profile_photo_path'];
    } else {
        // Photo path is NULL in database, use empty string
        $currentProfilePhotoPath = '';
    }

} catch (PDOException $error) {
    // If a database error occurs, catch it here
    // WHY: This prevents the entire page from crashing
    // Instead, we show a friendly error message
    $errorMessage = "Database error: " . $error->getMessage();
}

// ============================================
// STEP 5: HANDLE FORM SUBMISSION
// ============================================
// WHY: This section runs ONLY when the user clicks "Save Changes" button
// We check if the form was submitted using POST method

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the update
    
    // --------------------------------------------
    // STEP 5A: GET THE BIO FROM THE FORM
    // --------------------------------------------
    
    // Check if 'bio' exists in the $_POST array
    // WHY: Always check if a form field exists before using it
    if (isset($_POST['bio'])) {
        // Get the bio value from the form
        $newBio = $_POST['bio'];
    } else {
        // Bio field doesn't exist, use empty string
        $newBio = '';
    }
    
    // Remove extra whitespace from the beginning and end
    // WHY: Users might accidentally add spaces, we clean them up
    $newBio = trim($newBio);

    // Sanitize the bio to prevent XSS attacks
    // WHY: htmlspecialchars() converts < > & " ' to safe HTML entities
    // Example: <script> becomes &lt;script&gt; (displays but doesn't execute)
    $sanitizedBio = htmlspecialchars($newBio);

    // --------------------------------------------
    // STEP 5B: PREPARE FOR FILE UPLOAD
    // --------------------------------------------
    
    // Start with the current photo path
    // WHY: If the user doesn't upload a new photo, keep the old one
    $newProfilePhotoPath = $currentProfilePhotoPath;

    // --------------------------------------------
    // STEP 5C: HANDLE PROFILE PHOTO UPLOAD
    // --------------------------------------------
    // WHY: We need to upload the photo file to the server
    // The uploadFile() function (from file-upload-helper.php) does the work
    
    // Call the uploadFile() function with these parameters:
    // 1. 'profile_photo' = the name of the file input field in the form
    // 2. Upload directory = where to save the file (uploads/profiles folder)
    // 3. Allowed types = only JPG and PNG images
    // 4. Max size = 2 MB (2 * 1024 * 1024 bytes)
    $profilePhotoResult = uploadFile(
        'profile_photo',
        __DIR__ . '/../uploads/profiles',
        ['image/jpeg', 'image/png'],
        2 * 1024 * 1024
    );

    // Now we need to check what uploadFile() returned:
    // - If it returns a STRING with "/" = success (file path like "uploads/profiles/photo.jpg")
    // - If it returns a STRING without "/" = error message (like "File too large")
    // - If it returns NULL = no file was uploaded (user left it blank)
    
    // Check if a result was returned (not null)
    if ($profilePhotoResult !== null) {
        // Something was returned (either success or error)
        
        // Check if the result contains a forward slash
        // WHY: File paths have "/" but error messages don't
        // Example: "uploads/profiles/photo.jpg" has "/" = success
        // Example: "File too large" has no "/" = error
        if (strpos($profilePhotoResult, '/') !== false) {
            // The result contains "/", so it's a file path = SUCCESS
            $newProfilePhotoPath = $profilePhotoResult;
        } else {
            // The result doesn't contain "/", so it's an error message = FAILURE
            $errorMessage = "Profile photo: " . $profilePhotoResult;
        }
    }
    // If $profilePhotoResult is null, the user didn't upload anything (left field empty)
    // That's okay! We just keep the old photo ($newProfilePhotoPath is already set)

    // --------------------------------------------
    // STEP 5D: UPDATE DATABASE
    // --------------------------------------------
    // WHY: Save the new bio and photo path to the database
    // Only do this if there are no errors from file upload
    
    if (empty($errorMessage)) {
        // No errors, we can proceed with the database update
        
        try {
            // Write the SQL UPDATE query
            // WHY: This changes the bio and profile_photo_path for this user
            $updateQuery = "
                UPDATE users
                SET 
                    bio = :bio,
                    profile_photo_path = :profilePhotoPath
                WHERE user_id = :userID
            ";

            // Prepare the statement
            // WHY: Using prepare() prevents SQL injection attacks
            $updateStatement = $connection->prepare($updateQuery);

            // Bind the bio parameter
            // WHY: This safely inserts the sanitized bio into the query
            // PDO::PARAM_STR tells PDO this is a string (text)
            $updateStatement->bindParam(':bio', $sanitizedBio, PDO::PARAM_STR);
            
            // Bind the profile photo path parameter
            // WHY: This safely inserts the photo path into the query
            $updateStatement->bindParam(':profilePhotoPath', $newProfilePhotoPath, PDO::PARAM_STR);
            
            // Bind the user ID parameter
            // WHY: This tells the database which user to update
            $updateStatement->bindParam(':userID', $userID, PDO::PARAM_INT);

            // Execute the update query
            // WHY: This actually runs the SQL command and updates the database
            $updateStatement->execute();

            // If we get here, the update was successful!
            // Set a success message to show to the user
            $successMessage = "Your profile has been updated successfully!";

            // Update the local variables
            // WHY: So the form displays the NEW data (not the old data)
            $bio = $newBio;
            $currentProfilePhotoPath = $newProfilePhotoPath;

        } catch (PDOException $error) {
            // If a database error occurs, catch it here
            // WHY: This prevents the entire page from crashing
            $errorMessage = "Failed to update profile: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding for proper text display -->
    <meta charset="UTF-8">
    
    <!-- Viewport meta tag for mobile responsiveness -->
    <!-- WHY: Without this, mobile browsers display desktop version (tiny text) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title shown in browser tab -->
    <title>Edit Profile - PlantBnB</title>
</head>
<body>
    <!-- ============================================
         HTML VIEW SECTION (BOTTOM OF FILE)
         ============================================
         WHY: All PHP logic is at the top, all HTML is at the bottom
         This makes the code easier to understand and debug
         ============================================ -->
         
    <!-- Main container - centers content and adds padding -->
    <!-- WHY: Bootstrap's "container" class provides responsive width -->
    <!-- mt-4 = margin-top (spacing from top of page) -->
    <div class="container mt-4">
        
        <!-- ============================================
             BACK BUTTON SECTION
             ============================================ -->
        <!-- Row for the back button -->
        <!-- WHY: Bootstrap grid system uses rows and columns -->
        <!-- mb-3 = margin-bottom for spacing below this section -->
        <div class="row mb-3">
            <!-- Column that holds the back button -->
            <!-- col-12 = full width on mobile (phone screens) -->
            <!-- col-md-8 = 2/3 width on desktop (medium screens and up) -->
            <!-- offset-md-2 = push 2 columns from left on desktop (centers the content) -->
            <div class="col-12 col-md-8 offset-md-2">
                <!-- Link styled as a button to go back to dashboard -->
                <!-- WHY: We use <a> tag for navigation (no JavaScript needed) -->
                <!-- btn-outline-secondary = gray outlined button style -->
                <!-- btn-sm = small button size -->
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
        <!-- ============================================
             MAIN EDIT PROFILE CARD
             ============================================ -->
        <!-- Row to hold the main card -->
        <!-- WHY: Bootstrap grid system requires a row -->
        <!-- mb-5 = large bottom margin for spacing -->
        <div class="row mb-5">
            <!-- Column for the card -->
            <!-- col-12 = full width on mobile -->
            <!-- col-md-8 = 2/3 width on desktop -->
            <!-- offset-md-2 = centers the card on desktop (pushes it 2 columns from left) -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- Bootstrap card component -->
                <!-- WHY: Cards provide a nice contained box for content -->
                <!-- shadow-sm = small shadow effect (makes it look slightly elevated) -->
                <div class="card shadow-sm">
                    
                    <!-- Card Header (top colored section) -->
                    <!-- WHY: Visually separates the title from the form -->
                    <!-- bg-success = green background color -->
                    <!-- text-white = white text color -->
                    <div class="card-header bg-success text-white">
                        <!-- Page heading -->
                        <!-- mb-0 = no bottom margin (removes extra space) -->
                        <h3 class="mb-0">Edit Your Profile</h3>
                    </div>

                    <!-- Card Body (main content area) -->
                    <!-- WHY: This is where the form and photo go -->
                    <div class="card-body">
                        
                        <!-- ============================================
                             CURRENT PROFILE PHOTO SECTION
                             ============================================ -->
                        <!-- Container for current photo display -->
                        <!-- mb-4 = bottom margin for spacing -->
                        <div class="mb-4">
                            <!-- Section heading -->
                            <!-- mb-3 = bottom margin for spacing -->
                            <h5 class="mb-3">Current Profile Photo</h5>

                            <!-- Profile photo display area -->
                            <!-- We center it horizontally using text-center -->
                            <div class="text-center">
                                <?php
                                    // Check if user has a profile photo
                                    if (!empty($currentProfilePhotoPath)) {
                                        // Build the correct path for the browser
                                        // WHY: We're in users/ folder, so we need ../ to go up to project root
                                        $displayPath = '../' . htmlspecialchars($currentProfilePhotoPath);
                                        
                                        // Display the user's current profile photo
                                        // The image is displayed in a circle using rounded-circle class
                                        // object-fit: cover ensures the image fills the circle without distortion
                                        echo "<img src=\"" . $displayPath . "\" alt=\"Current profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    } else {
                                        // No profile photo yet, display a placeholder
                                        // Using a placeholder service for demo purposes
                                        echo "<img src=\"https://via.placeholder.com/120?text=No+Photo\" alt=\"No profile photo\" class=\"rounded-circle\" style=\"width: 120px; height: 120px; object-fit: cover; border: 3px solid #e9ecef;\">";
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- ============================================
                             EDIT PROFILE FORM
                             ============================================ -->
                        <!-- Form to update bio and photo -->
                        <!-- WHY: method="POST" sends data securely (data not visible in URL) -->
                        <!-- WHY: action="" submits to the same page (profile-edit.php) -->
                        <!-- CRITICAL: enctype="multipart/form-data" is REQUIRED for file uploads -->
                        <!-- Without enctype="multipart/form-data", file upload will NOT work! -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            
                            <!-- ============================================
                                 BIO TEXTAREA FIELD
                                 ============================================ -->
                            <!-- Container for bio field -->
                            <!-- mb-3 = bottom margin for touch-friendly spacing on mobile -->
                            <div class="mb-3">
                                <!-- Label for the textarea -->
                                <!-- WHY: for="bio" connects the label to the textarea -->
                                <!-- Clicking the label will focus the textarea -->
                                <label for="bio" class="form-label">Short Bio</label>
                                
                                <!-- Textarea for multi-line text input -->
                                <!-- WHY: textarea allows multiple lines (unlike <input type="text">) -->
                                <!-- id="bio" matches the label's for="bio" -->
                                <!-- name="bio" is how we access this in $_POST['bio'] -->
                                <!-- class="form-control" applies Bootstrap styling -->
                                <!-- rows="5" sets the initial height (5 lines tall) -->
                                <textarea 
                                    id="bio" 
                                    name="bio" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Tell other users a bit about yourself and your interest in plants (optional)"
                                ><?php echo htmlspecialchars($bio); ?></textarea>
                                
                                <!-- Helper text below the textarea -->
                                <!-- WHY: text-muted makes it gray (less prominent) -->
                                <!-- d-block makes it display on its own line -->
                                <!-- mt-1 = small top margin for spacing -->
                                <small class="text-muted d-block mt-1">
                                    Write a short bio to help other users get to know you (max 500 characters recommended)
                                </small>
                            </div>

                            <!-- ============================================
                                 PROFILE PHOTO UPLOAD FIELD
                                 ============================================ -->
                            <!-- Container for file upload field -->
                            <!-- mb-3 = bottom margin for touch-friendly spacing -->
                            <div class="mb-3">
                                <!-- Label for the file input -->
                                <label for="profile_photo" class="form-label">Update Profile Photo</label>
                                
                                <!-- File input for uploading images -->
                                <!-- WHY: type="file" creates a file picker button -->
                                <!-- name="profile_photo" is how we access this in $_FILES['profile_photo'] -->
                                <!-- accept=".jpg, .jpeg, .png" helps user select correct file types -->
                                <!-- This doesn't prevent wrong types (we validate in PHP) but guides the user -->
                                <input 
                                    type="file" 
                                    id="profile_photo" 
                                    name="profile_photo" 
                                    class="form-control" 
                                    accept=".jpg, .jpeg, .png"
                                >
                                
                                <!-- Helper text explaining file requirements -->
                                <small class="text-muted d-block mt-1">
                                    JPG or PNG format. Maximum file size: 2MB
                                </small>
                            </div>

                            <!-- ============================================
                                 HORIZONTAL DIVIDER LINE
                                 ============================================ -->
                            <!-- Horizontal line to separate form sections -->
                            <!-- WHY: Visually separates input fields from submit button -->
                            <!-- my-4 = margin top and bottom (vertical spacing) -->
                            <hr class="my-4">

                            <!-- ============================================
                                 SUBMIT BUTTON
                                 ============================================ -->
                            <!-- Container for the submit button -->
                            <!-- WHY: d-grid makes button full width on mobile (touch-friendly) -->
                            <!-- gap-2 adds internal spacing -->
                            <!-- mb-2 = bottom margin -->
                            <div class="d-grid gap-2 mb-2">
                                <!-- Submit button -->
                                <!-- WHY: type="submit" sends the form data when clicked -->
                                <!-- btn btn-primary = Bootstrap's blue button style -->
                                <!-- btn-lg = large button size (easier to click on mobile) -->
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Save Changes
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