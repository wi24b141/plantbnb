<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\listing-creator.php

// ============================================
// CREATE LISTING PAGE - PHP LOGIC (TOP)
// ============================================

// Start the session to access $_SESSION variables
// session_start() must be called before any HTML output
session_start();

// Include the database connection
require_once 'db.php';

// ============================================
// SECURITY CHECK: VERIFY USER IS LOGGED IN
// ============================================

// Check if user_id exists in the session
// If the user is not logged in, redirect to the login page immediately
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Store the user_id from the session for use in queries
// We use intval() to ensure it's an integer for extra safety
$userID = intval($_SESSION['user_id']);

// ============================================
// INITIALIZE VARIABLES
// ============================================

// Initialize variables to store form data and feedback messages
$errors = [];
$successMessage = '';

// Initialize form field variables so they exist from the start
// This prevents "Undefined variable" warnings when the page first loads
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

// ============================================
// HANDLE FORM SUBMISSION
// ============================================

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the new listing

    // Get all form data from $_POST and trim whitespace
    // trim() removes spaces before and after the input
    // ?? '' provides a default empty string if the key doesn't exist
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
    // VALIDATION LOGIC
    // ============================================

    // Validate Listing Type
    // Must be either 'offer' or 'need'
    if (empty($listingType)) {
        $errors[] = 'Please select a listing type (Offer or Need).';
    } else if ($listingType !== 'offer' && $listingType !== 'need') {
        $errors[] = 'Invalid listing type. Please select either Offer or Need.';
    }

    // Validate Title
    // Title is required and must be at least 5 characters
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } else if (strlen($title) < 5) {
        $errors[] = 'Title must be at least 5 characters long.';
    } else if (strlen($title) > 150) {
        $errors[] = 'Title must not exceed 150 characters.';
    }

    // Validate Description
    // Description is required and must be at least 20 characters
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } else if (strlen($description) < 20) {
        $errors[] = 'Description must be at least 20 characters long.';
    }

    // Validate Location
    // Location is required
    if (empty($locationApprox)) {
        $errors[] = 'Approximate location is required.';
    }

    // Validate Start Date
    // Start date is required and must be a valid date
    if (empty($startDate)) {
        $errors[] = 'Start date is required.';
    }

    // Validate End Date
    // End date is required and must be after start date
    if (empty($endDate)) {
        $errors[] = 'End date is required.';
    } else if (!empty($startDate) && strtotime($endDate) <= strtotime($startDate)) {
        // strtotime() converts date string to Unix timestamp for comparison
        // End date must be AFTER start date
        $errors[] = 'End date must be after start date.';
    }

    // Validate Plant Type
    // Plant type is required
    if (empty($plantType)) {
        $errors[] = 'Plant type is required.';
    }

    // Validate Watering Needs
    // Watering needs are required
    if (empty($wateringNeeds)) {
        $errors[] = 'Watering needs are required.';
    }

    // Validate Light Needs
    // Light needs are required
    if (empty($lightNeeds)) {
        $errors[] = 'Light needs are required.';
    }

    // ============================================
    // HANDLE FILE UPLOAD
    // ============================================


    // Initialize variable to store listing photo path
    // This will be updated if a file is uploaded
    $listingPhotoPath = null;

    // Check if a file was uploaded in the listing_photo field
    // isset($_FILES['listing_photo']) checks if the file input exists
    // $_FILES['listing_photo']['error'] == UPLOAD_ERR_OK checks if upload was successful (0 = no error)
    if (isset($_FILES['listing_photo']) && $_FILES['listing_photo']['error'] === UPLOAD_ERR_OK) {
        // A file was uploaded successfully, now validate it

        // Extract file upload information
        // $_FILES['listing_photo']['tmp_name'] = temporary file location on server
        // $_FILES['listing_photo']['name'] = original filename from user's computer
        // $_FILES['listing_photo']['size'] = file size in bytes
        // $_FILES['listing_photo']['type'] = MIME type (e.g., image/jpeg)
        $uploadedFileName = $_FILES['listing_photo']['name'];
        $uploadedFileSize = $_FILES['listing_photo']['size'];
        $uploadedFileTmpPath = $_FILES['listing_photo']['tmp_name'];
        $uploadedFileMimeType = $_FILES['listing_photo']['type'];

        // Validate file size
        // Maximum allowed size is 3MB = 3 * 1024 * 1024 = 3145728 bytes
        // Listing photos can be larger than profile photos to show plant details
        $maxFileSize = 3 * 1024 * 1024;

        if ($uploadedFileSize > $maxFileSize) {
            // File is too large, add error message
            $errors[] = "Photo file size exceeds 3MB limit. Please choose a smaller file.";
        } else if ($uploadedFileMimeType !== 'image/jpeg' && $uploadedFileMimeType !== 'image/png') {
            // File type is not allowed (only JPG and PNG allowed)
            $errors[] = "Only JPG and PNG files are allowed for listing photos.";
        } else {
            // File passed validation, now process the upload

            // Create the uploads/listings directory if it doesn't exist
            // This ensures the directory is ready to receive the file
            if (!is_dir('uploads/listings')) {
                // Directory doesn't exist, so create it
                // 0777 = permissions (readable, writable, executable for everyone)
                // true = create parent directories if needed
                mkdir('uploads/listings', 0777, true);
            }

            // Generate a unique filename to prevent overwriting existing files
            // uniqid() creates a unique ID based on current time (13 characters)
            // This ensures no two files will have the same name
            // basename() extracts just the filename from the full path
            $uniqueFileName = uniqid() . "_" . basename($uploadedFileName);

            // Build the full path where the file will be saved
            // This path is relative to the web root (e.g., uploads/listings/someid_photo.jpg)
            $destinationPath = 'uploads/listings/' . $uniqueFileName;

            // Move the uploaded file from temporary location to permanent location
            // move_uploaded_file() is the secure way to handle file uploads
            // It validates that the file was actually uploaded via HTTP POST
            if (move_uploaded_file($uploadedFileTmpPath, $destinationPath)) {
                // File was successfully moved, save the path
                $listingPhotoPath = $destinationPath;
            } else {
                // File move failed for some reason
                $errors[] = "Failed to save the listing photo. Please try again.";
            }
        }
    }

    // ============================================
    // HANDLE CARE SHEET PDF UPLOAD
    // ============================================

    // Initialize variable to store care sheet PDF path
    // This will be updated if a PDF is uploaded
    $careSheetPath = null;

    // Check if a PDF file was uploaded in the care_sheet field
    // isset($_FILES['care_sheet']) checks if the file input exists
    // $_FILES['care_sheet']['error'] == UPLOAD_ERR_OK checks if upload was successful (0 = no error)
    if (isset($_FILES['care_sheet']) && $_FILES['care_sheet']['error'] === UPLOAD_ERR_OK) {
        // A file was uploaded successfully, now validate it

        // Extract file upload information
        // $_FILES['care_sheet']['tmp_name'] = temporary file location on server
        // $_FILES['care_sheet']['name'] = original filename from user's computer
        // $_FILES['care_sheet']['size'] = file size in bytes
        // $_FILES['care_sheet']['type'] = MIME type (should be application/pdf)
        $uploadedCareSheetName = $_FILES['care_sheet']['name'];
        $uploadedCareSheetSize = $_FILES['care_sheet']['size'];
        $uploadedCareSheetTmpPath = $_FILES['care_sheet']['tmp_name'];
        $uploadedCareSheetMimeType = $_FILES['care_sheet']['type'];

        // Validate file size
        // Maximum allowed size is 5MB = 5 * 1024 * 1024 = 5242880 bytes
        // PDFs can contain detailed care instructions so we allow larger size
        $maxCareSheetSize = 5 * 1024 * 1024;

        if ($uploadedCareSheetSize > $maxCareSheetSize) {
            // File is too large, add error message
            $errors[] = "Care sheet file size exceeds 5MB limit. Please choose a smaller file.";
        } else if ($uploadedCareSheetMimeType !== 'application/pdf') {
            // File type is not allowed (only PDF allowed)
            $errors[] = "Only PDF files are allowed for care sheets.";
        } else {
            // File passed validation, now process the upload

            // Create the uploads/caresheets directory if it doesn't exist
            // This ensures the directory is ready to receive the file
            if (!is_dir('uploads/caresheets')) {
                // Directory doesn't exist, so create it
                // 0777 = permissions (readable, writable, executable for everyone)
                // true = create parent directories if needed
                mkdir('uploads/caresheets', 0777, true);
            }

            // Generate a unique filename to prevent overwriting existing files
            // uniqid() creates a unique ID based on current time (13 characters)
            // This ensures no two files will have the same name
            // basename() extracts just the filename from the full path
            $uniqueCareSheetName = uniqid() . "_" . basename($uploadedCareSheetName);

            // Build the full path where the file will be saved
            // This path is relative to the web root (e.g., uploads/caresheets/someid_care.pdf)
            $careSheetDestination = 'uploads/caresheets/' . $uniqueCareSheetName;

            // Move the uploaded file from temporary location to permanent location
            // move_uploaded_file() is the secure way to handle file uploads
            // It validates that the file was actually uploaded via HTTP POST
            if (move_uploaded_file($uploadedCareSheetTmpPath, $careSheetDestination)) {
                // File was successfully moved, save the path
                $careSheetPath = $careSheetDestination;
            } else {
                // File move failed for some reason
                $errors[] = "Failed to save the care sheet PDF. Please try again.";
            }
        }
    }

    // ============================================
    // INSERT INTO DATABASE
    // ============================================

    // Only insert into database if there are no validation errors
    if (empty($errors)) {
        try {
            // Query to insert a new listing into the listings table
            // We use :placeholders for all values to prevent SQL injection
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

            // Prepare the insert statement
            $insertListingStatement = $connection->prepare($insertListingQuery);

            // Bind all the parameters to prevent SQL injection
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


            // Execute the insert
            $insertListingStatement->execute();

            // Get the ID of the newly created listing
            // lastInsertId() returns the auto-generated ID from the INSERT
            // We need this to link the plant entry to this listing
            $newListingID = $connection->lastInsertId();

            // ============================================
            // INSERT PLANT ENTRY
            // ============================================

            // Now insert the plant details into the plants table
            // Each listing must have at least one plant entry
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

            // Both inserts were successful!
            $successMessage = "Your listing has been created successfully!";

            // Clear the form fields after successful creation
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
            // If a database error occurs, add it to the errors array
            $errors[] = "Database error: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Listing - PlantBnB</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         CREATE LISTING PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <!-- This button allows users to easily navigate back -->
        <!-- col-12 = full width on mobile, col-md-10 = narrower on desktop -->
        <div class="row mb-3">
            <div class="col-12 col-md-10 offset-md-1">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Display success message if listing was created -->
        <?php
            if (!empty($successMessage)) {
                // Success alert - green background
                // alert-dismissible allows user to close the alert
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                echo htmlspecialchars($successMessage);
                echo "      <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Display error messages if validation fails -->
        <?php
            if (!empty($errors)) {
                // Error alert container - red background
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-danger\" role=\"alert\">";
                echo "      <strong>Please fix the following errors:</strong>";
                echo "      <ul class=\"mb-0 mt-2\">";

                // Loop through each error and display it as a list item
                foreach ($errors as $error) {
                    echo "        <li>" . htmlspecialchars($error) . "</li>";
                }

                echo "      </ul>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Main Create Listing Card -->
        <!-- col-12 = full width on mobile, col-md-10 = most of width on desktop -->
        <!-- offset-md-1 = centers the card on desktop -->
        <div class="row mb-5">
            <div class="col-12 col-md-10 offset-md-1">
                <div class="card shadow-sm">
                    <!-- Card Header -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Create New Listing</h3>
                        <p class="mb-0 small">Post a plant care need or offer to the community</p>
                    </div>

                    <!-- Card Body with form -->
                    <div class="card-body">
                        <!-- Create Listing Form -->
                        <!-- CRITICAL: enctype="multipart/form-data" is REQUIRED for file uploads -->
                        <!-- Without this, the file upload will not work -->
                        <!-- method="POST" sends data securely -->
                        <!-- action="" submits to the same page for processing -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- SECTION 1: BASIC LISTING INFORMATION -->
                            <!-- This section contains the core listing details -->
                            <h5 class="mb-3">Basic Information</h5>

                            <!-- Listing Type Selection -->
                            <!-- mb-3 = adds bottom margin for touch-friendly spacing -->
                            <div class="mb-3">
                                <label for="listing_type" class="form-label">Listing Type *</label>
                                <!-- select dropdown allows user to choose between offer or need -->
                                <select 
                                    id="listing_type" 
                                    name="listing_type" 
                                    class="form-select" 
                                    required
                                >
                                    <option value="">-- Select Type --</option>
                                    <!-- Check if 'offer' was previously selected to preserve form data after validation -->
                                    <option value="offer" <?php if ($listingType === 'offer') { echo 'selected'; } ?>>
                                        Offer (I can provide plant care)
                                    </option>
                                    <!-- Check if 'need' was previously selected -->
                                    <option value="need" <?php if ($listingType === 'need') { echo 'selected'; } ?>>
                                        Need (I am looking for plant care)
                                    </option>
                                </select>
                                <small class="text-muted d-block mt-1">
                                    Choose "Offer" if you can take care of plants, or "Need" if you're looking for someone to care for your plants
                                </small>
                            </div>

                            <!-- Title Input -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <!-- value keeps the entered text in the field if validation fails -->
                                <!-- Wrap value in htmlspecialchars() to prevent XSS attacks -->
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
                                <!-- textarea allows multi-line text input -->
                                <!-- rows="5" sets the initial height (5 lines) -->
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
                                <!-- type="file" creates a file upload input -->
                                <!-- accept=".jpg, .jpeg, .png" restricts file selection to image types -->
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

                            <!-- Care Sheet PDF Upload (NEW) -->
                            <!-- This allows users to attach detailed plant care instructions as a PDF -->
                            <div class="mb-3">
                                <label for="care_sheet" class="form-label">Care Sheet PDF (Optional)</label>
                                <!-- type="file" creates a file upload input -->
                                <!-- accept=".pdf" restricts file selection to PDF files only -->
                                <input 
                                    type="file" 
                                    id="care_sheet" 
                                    name="care_sheet" 
                                    class="form-control" 
                                    accept=".pdf"
                                >
                                <small class="text-muted d-block mt-1">
                                    PDF format only. Maximum file size: 5MB. Upload a detailed care guide for your plants (watering schedule, feeding instructions, etc.)
                                </small>
                            </div>

                            <!-- Form Divider -->
                            <hr class="my-4">

                            <!-- SECTION 2: LOCATION AND DATES -->
                            <!-- This section contains location and time details -->
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
                            <!-- row and col-12 col-md-6 creates two columns on desktop, stacked on mobile -->
                            <div class="row">
                                <!-- Start Date -->
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <!-- type="date" creates a date picker input -->
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

                            <!-- SECTION 3: PLANT DETAILS -->
                            <!-- This section contains plant care information -->
                            <h5 class="mb-3">Plant Information</h5>

                            <!-- Plant Type Input -->
                            <div class="mb-3">
                                <label for="plant_type" class="form-label">Plant Type *</label>
                                <input 
                                    type="text" 
                                    id="plant_type" 
                                    name="plant_type" 
                                    class="form-control" 
                                    placeholder="E.g., Monstera Deliciosa, Snake Plant, Pothos" 
                                    value="<?php echo htmlspecialchars($plantType); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">
                                    What type of plant(s) need care? You can list multiple types separated by commas
                                </small>
                            </div>

                            <!-- Watering Needs Textarea -->
                            <div class="mb-3">
                                <label for="watering_needs" class="form-label">Watering Needs *</label>
                                <textarea 
                                    id="watering_needs" 
                                    name="watering_needs" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="E.g., Water when soil is dry to touch. Reduce watering in winter."
                                    required
                                ><?php echo htmlspecialchars($wateringNeeds); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    How often and how much should the plant(s) be watered?
                                </small>
                            </div>

                            <!-- Light Needs Textarea -->
                            <div class="mb-3">
                                <label for="light_needs" class="form-label">Light Needs *</label>
                                <textarea 
                                    id="light_needs" 
                                    name="light_needs" 
                                    class="form-control" 
                                    rows="3" 
                                    placeholder="E.g., Bright indirect light. Avoid direct sunlight."
                                    required
                                ><?php echo htmlspecialchars($lightNeeds); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    What kind of light does the plant(s) need?
                                </small>
                            </div>

                            <!-- Form Divider -->
                            <hr class="my-4">

                            <!-- SECTION 4: OPTIONAL DETAILS -->
                            <!-- This section contains optional fields -->
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
                            <!-- d-grid = full width button on mobile -->
                            <!-- gap-2 = adds spacing inside the button area -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Listing
                                </button>
                            </div>

                            <!-- Help text explaining what happens after submit -->
                            <small class="text-muted d-block text-center mt-3">
                                * Required fields. Your listing will be visible to all users immediately after creation.
                            </small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the site footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>