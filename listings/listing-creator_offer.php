<?php
require_once __DIR__ . '/../includes/header.php';        
require_once __DIR__ . '/../includes/user-auth.php';   
require_once __DIR__ . '/../includes/db.php';            
require_once __DIR__ . '/../includes/file-upload-helper.php';  

// STEP 2: Get the logged-in user's ID
// $_SESSION is a special PHP array that remembers data across pages
// intval() converts the value to an integer (whole number)
// We use intval() for security: it ensures the value is a number, not text or code
// Example: intval('42') = 42, intval('hello') = 0
$userID = intval($_SESSION['user_id']);

// STEP 3: Initialize all form fields as empty strings
// We create these variables NOW so they exist even BEFORE the form is submitted
// This prevents PHP errors like "undefined variable"
// Later, if validation fails, these will hold the user's input so they don't have to retype everything
$listingType = 'offer';
$title = '';
$description = '';
$locationApprox = '';
$startDate = '';
$endDate = '';
$experience = '';
$priceRange = '';
$plantType = '';
$wateringNeeds = '';
$lightNeeds = '';

// STEP 4: Initialize error and success message variables
// We use an array for errors because there might be multiple errors
$errors = [];
// We use a simple string for success because there's only one success message
$successMessage = '';

// STEP 5: Check if the form was submitted
// $_SERVER['REQUEST_METHOD'] tells us HOW the page was loaded
// 'POST' means the user submitted a form (as opposed to 'GET' which is a normal page visit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the new listing

    // STEP 6: Get all form data from $_POST and trim whitespace
    // $_POST is a special PHP array containing all form data
    // trim() removes extra spaces before and after the text
    // Example: trim('  hello  ') becomes 'hello'
    
    // The ?? operator means "if not set, use this default value instead"
    // Example: $_POST['title'] ?? '' means "use $_POST['title'], but if it doesn't exist, use empty string ''"
    
    $listingType = trim($_POST['listing_type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $locationApprox = trim($_POST['location_approx'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $priceRange = trim($_POST['price_range'] ?? '');
    $plantType = trim($_POST['plant_type'] ?? '');
    $wateringNeeds = trim($_POST['watering_needs'] ?? '');
    $lightNeeds = trim($_POST['light_needs'] ?? '');

    // ============================================
    // STEP 7: VALIDATION LOGIC
    // ============================================
    // We check all the user's input BEFORE saving to the database
    // If something is wrong, we add an error message to the $errors array
    
    // VALIDATION 1: Ensure listing type is 'offer' for this form
    if ($listingType !== 'offer') {
        $errors[] = 'Invalid listing type.';
    }

    // VALIDATION 2: Check title is not empty
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } else {
        // Title exists, now check length
        // strlen() counts the number of characters in a string
        // Example: strlen('hello') = 5
        if (strlen($title) < 5) {
            $errors[] = 'Title must be at least 5 characters long.';
        }
        if (strlen($title) > 150) {
            $errors[] = 'Title must be 150 characters or less.';
        }
    }

    // VALIDATION 3: Check description is not empty
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } else {
        // Description exists, now check length
        if (strlen($description) < 20) {
            $errors[] = 'Description must be at least 20 characters long.';
        }
    }

    // VALIDATION 4: Check location is not empty
    if (empty($locationApprox)) {
        $errors[] = 'Location is required.';
    }

    // VALIDATION 5: Check start date is not empty
    if (empty($startDate)) {
        $errors[] = 'Start Date is required.';
    }

    // VALIDATION 6: Check end date is not empty
    if (empty($endDate)) {
        $errors[] = 'End Date is required.';
    }

    // VALIDATION 7-9: For offers, service details are optional
    // (these fields are primarily required for 'need' listings)
    // We keep them optional here so providers can describe services but are not forced to.

    // VALIDATION 10: Check end date is after start date
    // We only do this check if BOTH dates were filled in
    if (!empty($startDate) && !empty($endDate)) {
        // strtotime() converts a date string to a Unix timestamp (a big number representing seconds since 1970)
        // Example: strtotime('2026-01-15') = 1768867200
        // We convert both dates to numbers so we can compare them
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        // Check if end date is BEFORE or EQUAL to start date (which is wrong)
        if ($endTimestamp <= $startTimestamp) {
            $errors[] = 'End date must be after start date.';
        }
    }

    // ============================================
    // STEP 8: HANDLE FILE UPLOADS
    // ============================================
    
    // UPLOAD 1: Listing photo (optional)
    // uploadFile() is a custom function defined in file-upload-helper.php
    // It returns:
    //   - A file path string if upload was successful (e.g., "uploads/listings/photo123.jpg")
    //   - An error message string if upload failed (e.g., "File is too large")
    //   - null if no file was uploaded
    
    $listingPhotoResult = uploadFile(
        'listing_photo',                           // The name of the form field
        __DIR__ . '/../uploads/listings',          // Where to save the file
        ['image/jpeg', 'image/png'],               // Allowed file types
        3 * 1024 * 1024                            // Max size = 3 MB (3 × 1024 × 1024 bytes)
    );

    // Now we check if the upload failed
    // If it's a string AND it doesn't contain a forward slash, it's an error message
    // (Successful uploads return paths like "uploads/listings/photo.jpg" which contain /)
    $listingPhotoPath = null;
    if ($listingPhotoResult !== null) {
        // A file was uploaded (either success or error)
        if (strpos($listingPhotoResult, '/') === false) {
            // No forward slash found, so this is an error message
            $errors[] = "Listing photo: " . $listingPhotoResult;
        } else {
            // Forward slash found, so this is a valid file path
            $listingPhotoPath = $listingPhotoResult;
        }
    }

    // UPLOAD 2: Care sheet PDF (optional)
    $careSheetResult = uploadFile(
        'care_sheet',                              // The name of the form field
        __DIR__ . '/../uploads/caresheets',        // Where to save the file
        ['application/pdf'],                       // Allowed file types (only PDF)
        3 * 1024 * 1024                            // Max size = 3 MB
    );

    // Check if the upload failed (same logic as above)
    $careSheetPath = null;
    if ($careSheetResult !== null) {
        // A file was uploaded
        if (strpos($careSheetResult, '/') === false) {
            // This is an error message
            $errors[] = "Care sheet: " . $careSheetResult;
        } else {
            // This is a valid file path
            $careSheetPath = $careSheetResult;
        }
    }

    // ============================================
    // STEP 9: INSERT INTO DATABASE
    // ============================================
    
    // Only insert into database if there are NO validation errors
    // empty($errors) returns true if the array has no items in it
    if (empty($errors)) {
        // CRITICAL: We use try/catch for database operations
        // If something goes wrong with the database, PHP will "throw" an error
        // We "catch" that error and display it nicely instead of crashing the page
        try {
            // STEP 9A: Insert the listing into the listings table
            
            // This is our SQL query (the command we send to the database)
            // INSERT INTO means "add a new row to this table"
            // We use :placeholders instead of putting values directly in the query
            // Why? Security! This prevents "SQL Injection" attacks
            // Example of SQL Injection: A hacker could type: ' OR '1'='1
            // With placeholders, that text is treated as harmless text, not code
            
            $insertListingQuery = "
                INSERT INTO listings (
                    user_id,
                    listing_type,
                    title,
                    description,
                    listing_photo_path,
                    care_sheet_path,
                    location_approx,
                    start_date,
                    end_date,
                    experience,
                    price_range,
                    status,
                    created_at
                ) VALUES (
                    :userID,
                    :listingType,
                    :title,
                    :description,
                    :listingPhotoPath,
                    :careSheetPath,
                    :locationApprox,
                    :startDate,
                    :endDate,
                    :experience,
                    :priceRange,
                    'active',
                    NOW()
                )
            ";

            // STEP 9B: Prepare the query
            // $connection is the database connection object from db.php
            // prepare() gets the query ready but doesn't execute it yet
            // This is like preparing a form letter with blanks to fill in
            $insertListingStatement = $connection->prepare($insertListingQuery);

            // STEP 9C: Bind parameters to the placeholders
            // bindParam() connects each placeholder (:userID) to a PHP variable ($userID)
            // PDO::PARAM_INT means "this is an integer (whole number)"
            // PDO::PARAM_STR means "this is a string (text)"
            // Think of this like filling in the blanks on the form letter
            
            $insertListingStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $insertListingStatement->bindParam(':listingType', $listingType, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':title', $title, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':description', $description, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':listingPhotoPath', $listingPhotoPath, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':careSheetPath', $careSheetPath, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':locationApprox', $locationApprox, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':experience', $experience, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':priceRange', $priceRange, PDO::PARAM_STR);

            // STEP 9D: Execute the query
            // This actually sends the command to the database
            // The database will create a new row with all our data
            $insertListingStatement->execute();

            // STEP 9E: Get the ID of the newly created listing
            // lastInsertId() returns the auto-generated ID from the INSERT
            // Example: If this is the 42nd listing, this will return 42
            // We need this ID to link the plant entry to this listing
            $newListingID = $connection->lastInsertId();

            // ============================================
            // STEP 10: INSERT PLANT ENTRY
            // ============================================
            
            // If the provider filled any service detail, insert a plants row to store them.
            // Otherwise skip inserting an empty plants entry.
            if (!empty($plantType) || !empty($wateringNeeds) || !empty($lightNeeds)) {
                $insertPlantQuery = "
                    INSERT INTO plants (
                        listing_id,
                        plant_type,
                        watering_needs,
                        light_needs
                    ) VALUES (
                        :listingID,
                        :plantType,
                        :wateringNeeds,
                        :lightNeeds
                    )
                ";

                // Prepare the plant insert statement
                $insertPlantStatement = $connection->prepare($insertPlantQuery);

                // Bind the plant parameters
                $insertPlantStatement->bindParam(':listingID', $newListingID, PDO::PARAM_INT);
                $insertPlantStatement->bindParam(':plantType', $plantType, PDO::PARAM_STR);
                $insertPlantStatement->bindParam(':wateringNeeds', $wateringNeeds, PDO::PARAM_STR);
                $insertPlantStatement->bindParam(':lightNeeds', $lightNeeds, PDO::PARAM_STR);

                // Execute the plant insert
                $insertPlantStatement->execute();
            }

            // SUCCESS! Both inserts worked!
            $successMessage = "Your listing has been created successfully!";

            // Clear all form fields so the user can create another listing
            $listingType = '';
            $title = '';
            $description = '';
            $locationApprox = '';
            $startDate = '';
            $endDate = '';
            $experience = '';
            $priceRange = '';
            $plantType = '';
            $wateringNeeds = '';
            $lightNeeds = '';

        } catch (PDOException $error) {
            // If a database error occurs, we end up here
            // PDOException is the type of error that database operations can throw
            // $error->getMessage() gets a description of what went wrong
            $errors[] = "Database error: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- viewport meta tag makes the page mobile-friendly -->
    <!-- width=device-width means "use the device's actual width" -->
    <!-- initial-scale=1.0 means "don't zoom in or out" -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Offer - PlantBnB</title>
</head>
<body>
    <!-- container = Bootstrap class that centers content and adds padding -->
    <!-- mt-4 = margin-top: 1.5rem (adds space at the top) -->
    <div class="container mt-4">
        
        <!-- Back to Dashboard Button -->
        <!-- row = Bootstrap class for horizontal layout -->
        <!-- mb-3 = margin-bottom: 1rem (space below) -->
        <div class="row mb-3">
            <!-- col-12 = full width on mobile (12 out of 12 columns) -->
            <!-- col-md-10 = 10 out of 12 columns on medium screens and up (desktop) -->
            <!-- offset-md-1 = push 1 column to the right on desktop (centers the content) -->
            <div class="col-12 col-md-10 offset-md-1">
                <!-- btn = Bootstrap button class -->
                <!-- btn-outline-secondary = gray border, white background -->
                <!-- btn-sm = small size button -->
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Display success message if listing was created -->
        <?php
            // Check if there is a success message to display
            if (!empty($successMessage)) {
                // Display green success alert
                // alert = Bootstrap class for colored message boxes
                // alert-success = green background (for success messages)
                // alert-dismissible = can be closed by user
                // fade show = animation effects
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                
                // htmlspecialchars() prevents XSS attacks
                // It converts special characters like < > & to safe versions
                // Example: <script> becomes &lt;script&gt; which just displays as text
                echo htmlspecialchars($successMessage);
                
                echo "      <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Display error messages if validation fails -->
        <?php
            // Check if there are any error messages to display
            if (!empty($errors)) {
                // Display red error alert
                // alert-danger = red background (for error messages)
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-danger\" role=\"alert\">";
                echo "      <strong>Please fix the following errors:</strong>";
                echo "      <ul class=\"mb-0 mt-2\">";

                // Loop through each error and display it as a list item
                // We use a simple for loop instead of foreach for clarity
                $errorCount = count($errors);  // count() returns the number of items in an array
                $i = 0;  // Start counter at 0
                
                while ($i < $errorCount) {
                    // $errors[$i] gets the error message at position $i
                    echo "        <li>" . htmlspecialchars($errors[$i]) . "</li>";
                    $i = $i + 1;  // Move to next error
                }

                echo "      </ul>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Main Create Listing Card -->
        <!-- col-12 = full width on mobile (12 out of 12 columns) -->
        <!-- col-md-10 = 10 out of 12 columns on desktop -->
        <!-- offset-md-1 = push 1 column to the right on desktop (centers the card) -->
        <!-- mb-5 = margin-bottom: 3rem (large space below) -->
        <div class="row mb-5">
            <div class="col-12 col-md-10 offset-md-1">
                <!-- card = Bootstrap class for a bordered container with shadow -->
                <!-- shadow-sm = small shadow effect -->
                <div class="card shadow-sm">
                    
                    <!-- Card Header -->
                    <!-- card-header = special header area at top of card -->
                    <!-- bg-success = green background color -->
                    <!-- text-white = white text color -->
                    <div class="card-header bg-success text-white">
                        <!-- mb-0 = margin-bottom: 0 (no space below) -->
                        <h3 class="mb-0">Create New Offer</h3>
                        <!-- small = smaller font size -->
                        <p class="mb-0 small">Post a plant care offer to the community</p>
                    </div>

                    <!-- Card Body with form -->
                    <!-- card-body = main content area of the card with padding -->
                    <div class="card-body">
                        
                        <!-- ====================================== -->
                        <!-- CREATE LISTING FORM -->
                        <!-- ====================================== -->
                        
                        <!-- CRITICAL: enctype="multipart/form-data" is REQUIRED for file uploads -->
                        <!-- Without this, the file upload will not work -->
                        <!-- method="POST" means "send data securely to the server" -->
                        <!-- action="" means submit to the same page (this file) -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            
                            <!-- ====================================== -->
                            <!-- SECTION 1: BASIC LISTING INFORMATION -->
                            <!-- ====================================== -->
                            
                            <h5 class="mb-3">Basic Information</h5>

                            <!-- Listing Type Selection -->
                            <!-- mb-3 = margin-bottom: 1rem (space below for mobile touch-friendly) -->
                            <div class="mb-3">
                                <!-- for="listing_type" connects this label to the input with id="listing_type" -->
                                <!-- form-label = Bootstrap class for form labels -->
                                <label for="listing_type" class="form-label">Listing Type *</label>
                                
                                <!-- select = dropdown menu -->
                                <!-- form-select = Bootstrap class for styled dropdowns -->
                                <!-- required = browser will not allow form submission if empty -->
                                <!-- Force this form to create 'offer' listings only -->
                                <input type="hidden" id="listing_type" name="listing_type" value="offer">
                                <select class="form-select" disabled>
                                    <option value="offer" selected>Offer (I can provide plant care)</option>
                                </select>
                                <small class="text-muted d-block mt-1">This form creates an "Offer" listing only.</small>
                            </div>

                            <!-- Title Input -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                
                                <!-- type="text" = single-line text input -->
                                <!-- form-control = Bootstrap class for styled inputs -->
                                <!-- placeholder = gray hint text that appears when input is empty -->
                                <!-- value = the current value of the input -->
                                <!-- htmlspecialchars() prevents XSS attacks by escaping special characters -->
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-control" 
                                    placeholder="E.g., Monstera care needed for 2 weeks" 
                                    value="<?php echo htmlspecialchars($title); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">Short, descriptive title (5-150 characters)</small>
                            </div>

                            <!-- Description Textarea -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                
                                <!-- textarea = multi-line text input -->
                                <!-- rows="5" = show 5 lines of text by default -->
                                <!-- We put the value BETWEEN the tags, not in a value attribute -->
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Provide detailed information about your plant care listing..."
                                    required
                                ><?php echo htmlspecialchars($description); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    Include important details like special care requirements, location preferences, etc. (minimum 20 characters)
                                </small>
                            </div>

                            <!-- Listing Photo Upload -->
                            <div class="mb-3">
                                <label for="listing_photo" class="form-label">Listing Photo (Optional)</label>
                                
                                <!-- type="file" = creates a file upload button -->
                                <!-- accept=".jpg, .jpeg, .png" = limits what files the user can select -->
                                <!-- The browser will only show JPG and PNG files in the file picker -->
                                <input 
                                    type="file" 
                                    id="listing_photo" 
                                    name="listing_photo" 
                                    class="form-control" 
                                    accept=".jpg, .jpeg, .png"
                                >
                                <small class="text-muted d-block mt-1">
                                    JPG or PNG format. Maximum file size: 3MB. Adding a photo helps attract more interest!
                                </small>
                            </div>

                            <!-- Care Sheet PDF Upload -->
                            <div class="mb-3">
                                <label for="care_sheet" class="form-label">Care Sheet PDF (Optional)</label>
                                
                                <!-- accept=".pdf" = only PDF files can be selected -->
                                <input 
                                    type="file" 
                                    id="care_sheet" 
                                    name="care_sheet" 
                                    class="form-control" 
                                    accept=".pdf"
                                >
                                <small class="text-muted d-block mt-1">
                                    PDF format only. Maximum file size: 3MB. Upload a detailed care guide for your plants
                                </small>
                            </div>

                            <!-- Form Divider -->
                            <!-- hr = horizontal rule (a line across the page) -->
                            <!-- my-4 = margin-top and margin-bottom: 1.5rem (space above and below) -->
                            <hr class="my-4">

                            <!-- ====================================== -->
                            <!-- SECTION 2: LOCATION AND DATES -->
                            <!-- ====================================== -->
                            
                            <h5 class="mb-3">Location & Availability</h5>

                            <!-- Location Input -->
                            <div class="mb-3">
                                <label for="location_approx" class="form-label">Approximate Location *</label>
                                <input 
                                    type="text" 
                                    id="location_approx" 
                                    name="location_approx" 
                                    class="form-control" 
                                    placeholder="E.g., Berlin, Munich, Hamburg" 
                                    value="<?php echo htmlspecialchars($locationApprox); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">
                                    City or region (no need for exact address for privacy)
                                </small>
                            </div>

                            <!-- Date Range Inputs -->
                            <!-- row = creates a horizontal layout container -->
                            <!-- On mobile, both inputs will stack (col-12 = full width) -->
                            <!-- On desktop, they appear side-by-side (col-md-6 = half width each) -->
                            <div class="row">
                                
                                <!-- Start Date -->
                                <!-- col-12 = full width on mobile -->
                                <!-- col-md-6 = half width on medium screens and up (desktop) -->
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    
                                    <!-- type="date" = creates a date picker -->
                                    <!-- The browser shows a calendar popup when clicked -->
                                    <input 
                                        type="date" 
                                        id="start_date" 
                                        name="start_date" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($startDate); ?>"
                                        required
                                    >
                                    <small class="text-muted d-block mt-1">When does this listing become active?</small>
                                </div>

                                <!-- End Date -->
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date *</label>
                                    <input 
                                        type="date" 
                                        id="end_date" 
                                        name="end_date" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($endDate); ?>"
                                        required
                                    >
                                    <small class="text-muted d-block mt-1">When does this listing expire?</small>
                                </div>
                            </div>

                            <!-- Form Divider -->
                            <hr class="my-4">

                            <!-- ====================================== -->
                            <!-- SECTION 3: PLANT DETAILS -->
                            <!-- ====================================== -->
                            
                            <h5 class="mb-3">Service Details (optional)</h5>

                            <!-- Service Types Input (maps to plant_type) -->
                            <div class="mb-3">
                                <label for="plant_type" class="form-label">Services Offered</label>
                                <input 
                                    type="text" 
                                    id="plant_type" 
                                    name="plant_type" 
                                    class="form-control" 
                                    placeholder="E.g., watering, repotting, plant-sitting" 
                                    value="<?php echo htmlspecialchars($plantType); ?>"
                                >
                                <small class="text-muted d-block mt-1">Describe the services you provide (optional)</small>
                            </div>

                            <!-- Availability / Terms Textarea (maps to watering_needs) -->
                            <div class="mb-3">
                                <label for="watering_needs" class="form-label">Availability / Terms</label>
                                <textarea 
                                    id="watering_needs" 
                                    name="watering_needs" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="E.g., Available weekdays, can travel within 10km, min. 2-week bookings"
                                ><?php echo htmlspecialchars($wateringNeeds); ?></textarea>
                                <small class="text-muted d-block mt-1">When and under what terms can you provide services? (optional)</small>
                            </div>

                            <!-- Additional Notes Textarea (maps to light_needs) -->
                            <div class="mb-3">
                                <label for="light_needs" class="form-label">Additional Notes</label>
                                <textarea 
                                    id="light_needs" 
                                    name="light_needs" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="E.g., I have experience with large plants and delicate species."
                                ><?php echo htmlspecialchars($lightNeeds); ?></textarea>
                                <small class="text-muted d-block mt-1">Any other details about your service (optional)</small>
                            </div>

                            <!-- Form Divider -->
                            <hr class="my-4">

                            <!-- ====================================== -->
                            <!-- SECTION 4: OPTIONAL DETAILS -->
                            <!-- ====================================== -->
                            
                            <h5 class="mb-3">Additional Details (Optional)</h5>

                            <!-- Experience Level Input -->
                            <div class="mb-3">
                                <label for="experience" class="form-label">Required Experience Level</label>
                                <input 
                                    type="text" 
                                    id="experience" 
                                    name="experience" 
                                    class="form-control" 
                                    placeholder="E.g., Beginner friendly, Intermediate, Expert" 
                                    value="<?php echo htmlspecialchars($experience); ?>"
                                >
                                <small class="text-muted d-block mt-1">
                                    What level of plant care experience is needed?
                                </small>
                            </div>

                            <!-- Price Range Input -->
                            <div class="mb-3">
                                <label for="price_range" class="form-label">Price Range</label>
                                <input 
                                    type="text" 
                                    id="price_range" 
                                    name="price_range" 
                                    class="form-control" 
                                    placeholder="E.g., €10-20 per week, Free, Negotiable" 
                                    value="<?php echo htmlspecialchars($priceRange); ?>"
                                >
                                <small class="text-muted d-block mt-1">
                                    What compensation (if any) are you offering/expecting?
                                </small>
                            </div>

                            <!-- Form Divider -->
                            <hr class="my-4">

                            <!-- Submit Button -->
                            <!-- d-grid = makes the button container use CSS Grid layout -->
                            <!-- This makes the button full-width (mobile-friendly) -->
                            <!-- gap-2 = adds spacing inside the grid -->
                            <div class="d-grid gap-2">
                                <!-- type="submit" = clicking this button submits the form -->
                                <!-- btn btn-success = Bootstrap button with green color -->
                                <!-- btn-lg = large size button -->
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Listing
                                </button>
                            </div>

                            <!-- Help text explaining what happens after submit -->
                            <!-- text-center = centers the text horizontally -->
                            <!-- mt-3 = margin-top: 1rem -->
                            <small class="text-muted d-block text-center mt-3">
                                * Required fields. Your listing will be visible to all users immediately after creation.
                            </small>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>