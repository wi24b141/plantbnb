<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

//TODO Intval???
// Get the logged-in user's ID from the session
$userID = intval($_SESSION['user_id']);

// Initialize all form fields as empty strings
// This prevents "undefined variable" errors and preserves user input after validation errors
$formData = [
    'listing_type' => '',
    'title' => '',
    'description' => '',
    'location_approx' => '',
    'start_date' => '',
    'end_date' => '',
    'experience' => '',
    'price_range' => '',
    'plant_type' => '',
    'watering_needs' => '',
    'light_needs' => ''
];

// Initialize error and success message arrays
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The form was submitted, so we process the new listing

    // Get all form data from $_POST and trim whitespace
    // TODO trim() removes spaces before and after the input
    // TODO ?? '' provides a default empty string if the key doesn't exist
    // TODO What does the => mean?
    foreach ($formData as $key => $value) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

    // ============================================
    // VALIDATION LOGIC
    // ============================================

    // Check listing type is valid (must be 'offer' or 'need')
    if (empty($formData['listing_type']) || !in_array($formData['listing_type'], ['offer', 'need'])) {
        $errors[] = 'Please select a valid listing type (Offer or Need).';
    }

    // Check title length (5-150 characters)
    if (empty($formData['title'])) {
        $errors[] = 'Title is required.';
    } else if (strlen($formData['title']) < 5 || strlen($formData['title']) > 150) {
        $errors[] = 'Title must be between 5 and 150 characters.';
    }

    // Check description length (minimum 20 characters)
    if (empty($formData['description'])) {
        $errors[] = 'Description is required.';
    } else if (strlen($formData['description']) < 20) {
        $errors[] = 'Description must be at least 20 characters long.';
    }

    // Check required fields are filled
    $requiredFields = ['location_approx', 'start_date', 'end_date', 'plant_type', 'watering_needs', 'light_needs'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $fieldName = ucwords(str_replace('_', ' ', $field));
            $errors[] = "{$fieldName} is required.";
        }
    }

    // Check end date is after start date
    // strtotime() converts date string to Unix timestamp for comparison
    if (!empty($formData['start_date']) && !empty($formData['end_date'])) {
        if (strtotime($formData['end_date']) <= strtotime($formData['start_date'])) {
            $errors[] = 'End date must be after start date.';
        }
    }

    // ============================================
    // HANDLE FILE UPLOADS
    // ============================================

    // Upload listing photo (JPG or PNG, max 3MB)
    // uploadFile() returns either a file path (success) or an error message (failure) or null (no file)
    $listingPhotoResult = uploadFile(
        'listing_photo',                      // Form field name
        //TODO __DIR__ gives us the current folder (listings) Explain???
        __DIR__ . '/../uploads/listings',                   // Upload directory
        ['image/jpeg', 'image/png'],          // Allowed file types
        3 * 1024 * 1024                       // Max size (3MB in bytes)
    );

    // Check if upload failed (error message returned)
    if (is_string($listingPhotoResult) && strpos($listingPhotoResult, '/') === false) {
        $errors[] = "Listing photo: " . $listingPhotoResult;
        $listingPhotoPath = null;
    } else {
        $listingPhotoPath = $listingPhotoResult;
    }

    // Upload care sheet PDF (only PDF, max 3MB)
    $careSheetResult = uploadFile(
        'care_sheet',                         // Form field name
        __DIR__ . '/../uploads/caresheets',                 // Upload directory
        ['application/pdf'],                  // Allowed file types
        3 * 1024 * 1024                       // Max size (3MB in bytes)
    );

    // Check if upload failed (error message returned)
    if (is_string($careSheetResult) && strpos($careSheetResult, '/') === false) {
        $errors[] = "Care sheet: " . $careSheetResult;
        $careSheetPath = null;
    } else {
        $careSheetPath = $careSheetResult;
    }

    // ============================================
    // INSERT INTO DATABASE
    // ============================================

    // Only insert into database if there are no validation errors
    if (empty($errors)) {
        try {
            // Query to insert a new listing into the listings table
            // TODO What does this mean?? We use :placeholders for all values to prevent SQL injection
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

            //TODO How does this work??? Explain that!!!

            // Prepare the insert statement
            $insertListingStatement = $connection->prepare($insertListingQuery);

            // Bind all the parameters to prevent SQL injection
            $insertListingStatement->bindParam(':userID', $userID, PDO::PARAM_INT);
            $insertListingStatement->bindParam(':listingType', $formData['listing_type'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':title', $formData['title'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':description', $formData['description'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':listingPhotoPath', $listingPhotoPath, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':careSheetPath', $careSheetPath, PDO::PARAM_STR);
            $insertListingStatement->bindParam(':locationApprox', $formData['location_approx'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':startDate', $formData['start_date'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':endDate', $formData['end_date'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':experience', $formData['experience'], PDO::PARAM_STR);
            $insertListingStatement->bindParam(':priceRange', $formData['price_range'], PDO::PARAM_STR);

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
            $insertPlantStatement->bindParam(':plantType', $formData['plant_type'], PDO::PARAM_STR);
            $insertPlantStatement->bindParam(':wateringNeeds', $formData['watering_needs'], PDO::PARAM_STR);
            $insertPlantStatement->bindParam(':lightNeeds', $formData['light_needs'], PDO::PARAM_STR);

            // Execute the plant insert
            $insertPlantStatement->execute();

            // Both inserts were successful!
            $successMessage = "Your listing has been created successfully!";

            // Clear the form fields after successful creation
            $formData = array_fill_keys(array_keys($formData), '');

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
</head>
<body>
    <div class="container mt-4">
        <!-- Back to Dashboard Button -->
        <div class="row mb-3">
            <div class="col-12 col-md-10 offset-md-1">
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
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
                                    <option value="offer" <?php if ($formData['listing_type'] === 'offer') { echo 'selected'; } ?>>
                                        Offer (I can provide plant care)
                                    </option>
                                    <!-- Check if 'need' was previously selected -->
                                    <option value="need" <?php if ($formData['listing_type'] === 'need') { echo 'selected'; } ?>>
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
                                    value="<?php echo htmlspecialchars($formData['title']); ?>"
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
                                ><?php echo htmlspecialchars($formData['description']); ?></textarea>
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

                            <!-- Care Sheet PDF Upload -->
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
                                    PDF format only. Maximum file size: 3MB. Upload a detailed care guide for your plants (watering schedule, lighting needs, etc.)
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
                                    value="<?php echo htmlspecialchars($formData['location_approx']); ?>"
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
                                        value="<?php echo htmlspecialchars($formData['start_date']); ?>"
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
                                        value="<?php echo htmlspecialchars($formData['end_date']); ?>"
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
                                    value="<?php echo htmlspecialchars($formData['plant_type']); ?>"
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
                                ><?php echo htmlspecialchars($formData['watering_needs']); ?></textarea>
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
                                ><?php echo htmlspecialchars($formData['light_needs']); ?></textarea>
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
                                    value="<?php echo htmlspecialchars($formData['experience']); ?>"
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
                                    value="<?php echo htmlspecialchars($formData['price_range']); ?>"
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
</body>
</html>