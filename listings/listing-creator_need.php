<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

$userID = intval($_SESSION['user_id']);

// Initialize form variables to prevent "undefined variable" notices and support form persistence.
// Pre-populating with empty strings ensures clean re-rendering after validation failures.
$listingType = 'need';
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

// Separate error array from success string to accommodate multiple validation issues.
$errors = [];
$successMessage = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Retrieve and sanitize input using null coalescing operator (??) for safe defaults.
    // trim() prevents whitespace-based validation bypasses.
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


    // SERVER-SIDE VALIDATION
    // NOTE: Server-side validation is essential because client-side validation can be bypassed
    // Validate listing type matches expected value for this form
    if ($listingType !== 'need') {
        $errors[] = 'Invalid listing type.';
    }

    // Validate title presence and length constraints
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } else {
        if (strlen($title) < 5) {
            $errors[] = 'Title must be at least 5 characters long.';
        }
        if (strlen($title) > 150) {
            $errors[] = 'Title must be 150 characters or less.';
        }
    }

    // Validate description with minimum length requirement
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } else {
        if (strlen($description) < 20) {
            $errors[] = 'Description must be at least 20 characters long.';
        }
    }

    // Validate required fields
    if (empty($locationApprox)) {
        $errors[] = 'Location is required.';
    }

    if (empty($startDate)) {
        $errors[] = 'Start Date is required.';
    }

    if (empty($endDate)) {
        $errors[] = 'End Date is required.';
    }

    if (empty($plantType)) {
        $errors[] = 'Plant Type is required.';
    }

    if (empty($wateringNeeds)) {
        $errors[] = 'Watering Needs is required.';
    }

    if (empty($lightNeeds)) {
        $errors[] = 'Light Needs is required.';
    }

    // Validate logical date range: end date must be after start date
    if (!empty($startDate) && !empty($endDate)) {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        if ($endTimestamp <= $startTimestamp) {
            $errors[] = 'End date must be after start date.';
        }
    }

    // FILE UPLOAD PROCESSING
    // NOTE: File upload helper validates MIME types and file size to prevent malicious uploads
    // Process optional listing photo (JPEG/PNG only, max 3MB)
    $listingPhotoResult = uploadFile(
        'listing_photo',
        __DIR__ . '/../uploads/listings',
        ['image/jpeg', 'image/png'],
        3 * 1024 * 1024
    );
    $listingPhotoPath = null;
    if ($listingPhotoResult !== null) {
        if (strpos($listingPhotoResult, '/') === false) {
            $errors[] = "Listing photo: " . $listingPhotoResult;
        } else {
            $listingPhotoPath = $listingPhotoResult;
        }
    }

    // Process optional care sheet PDF (max 3MB)
    $careSheetResult = uploadFile(
        'care_sheet',
        __DIR__ . '/../uploads/caresheets',
        ['application/pdf'],
        3 * 1024 * 1024
    );
    $careSheetPath = null;
    if ($careSheetResult !== null) {
        if (strpos($careSheetResult, '/') === false) {
            $errors[] = "Care sheet: " . $careSheetResult;
        } else {
            $careSheetPath = $careSheetResult;
        }
    }

    // DATABASE TRANSACTION
    // Proceed with database insertion only if validation passed
    if (empty($errors)) {
        // NOTE: try-catch blocks handle PDOException for graceful error handling
        try {
            // Prepare SQL INSERT statement for listings table
            // NOTE: PDO prepared statements with parameterized queries prevent SQL injection attacks
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

            // Prepare statement to separate SQL logic from data
            $insertListingStatement = $connection->prepare($insertListingQuery);

            // Bind parameters with explicit type casting for added security
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

            // Execute the prepared statement
            $insertListingStatement->execute();

            // Retrieve auto-incremented primary key for foreign key reference
            $newListingID = $connection->lastInsertId();

            // ============================================
            // PLANT ENTRY INSERTION
            // ============================================
            
            // Insert associated plant record with foreign key reference to listing
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

            $insertPlantStatement = $connection->prepare($insertPlantQuery);

            $insertPlantStatement->bindParam(':listingID', $newListingID, PDO::PARAM_INT);
            $insertPlantStatement->bindParam(':plantType', $plantType, PDO::PARAM_STR);
            $insertPlantStatement->bindParam(':wateringNeeds', $wateringNeeds, PDO::PARAM_STR);
            $insertPlantStatement->bindParam(':lightNeeds', $lightNeeds, PDO::PARAM_STR);

            $insertPlantStatement->execute();

            $successMessage = "Your listing has been created successfully!";

            // Reset form fields after successful submission
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
            // Catch and log database exceptions for debugging while protecting sensitive information
            $errors[] = "Database error: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Viewport meta tag enables responsive design for mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Need - PlantBnB</title>
</head>
<body>
    <!-- Main container: Bootstrap responsive grid with top margin -->
    <div class="container mt-4">
        
        <!-- Navigation: Back to Dashboard -->
        <div class="row mb-3">
            <!-- col-md-10 offset-md-1 centers content horizontally on medium+ screens -->
            <div class="col-12 col-md-10 offset-md-1">
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success Feedback -->
        <?php
            if (!empty($successMessage)) {
                // Bootstrap alert-success component with dismiss functionality
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                
                // NOTE: htmlspecialchars() prevents XSS by encoding HTML entities
                echo htmlspecialchars($successMessage);
                
                echo "      <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Validation Error Feedback -->
        <?php
            if (!empty($errors)) {
                // Bootstrap alert-danger component for validation errors
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-danger\" role=\"alert\">";
                echo "      <strong>Please fix the following errors:</strong>";
                echo "      <ul class=\"mb-0 mt-2\">";

                $errorCount = count($errors);
                $i = 0;
                
                while ($i < $errorCount) {
                    echo "        <li>" . htmlspecialchars($errors[$i]) . "</li>";
                    $i = $i + 1;
                }

                echo "      </ul>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Main Content: Create Listing Form Card -->
        <div class="row mb-5">
            <div class="col-12 col-md-10 offset-md-1">
                <!-- Bootstrap card component with subtle shadow -->
                <div class="card shadow-sm">
                    
                    <!-- Card Header Section -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Create New Need</h3>
                        <p class="mb-0 small">Post a plant care need to the community</p>
                    </div>

                    <!-- Card Body: Form Content -->
                    <div class="card-body">
                        
                        <!-- ====================================== -->
                        <!-- CREATE LISTING FORM -->
                        <!-- ====================================== -->
                        
                        <!-- NOTE: enctype="multipart/form-data" is required for file uploads to work correctly -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            
                            <!-- SECTION 1: Basic Listing Information -->
                            <h5 class="mb-3">Basic Information</h5>

                            <!-- Listing Type Field -->
                            <div class="mb-3">
                                <label for="listing_type" class="form-label">Listing Type *</label>
                                
                                <!-- Hidden input enforces 'need' type; disabled select provides visual feedback -->
                                <input type="hidden" id="listing_type" name="listing_type" value="need">
                                <select class="form-select" disabled>
                                    <option value="need" selected>Need (I am looking for plant care)</option>
                                </select>
                                <small class="text-muted d-block mt-1">This form creates a "Need" listing only.</small>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                            
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-control" 
                                    placeholder="E.g., Need plant-sitter in Berlin for 2 weeks" 
                                    value="<?php echo htmlspecialchars($title); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">Short, descriptive title (5-150 characters). Include location and duration if relevant.</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Describe your plant(s), required care, preferred handover (pickup/dropoff), and any constraints..."
                                    required
                                ><?php echo htmlspecialchars($description); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    Include plant types, special care needs, handover preferences, and any timing constraints (minimum 20 characters)
                                </small>
                            </div>

                            <hr class="my-4">

                            <!-- SECTION 2: Location and Availability -->
                            <h5 class="mb-3">Location & Availability</h5>

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

                                <!-- Listing Photo Upload -->
                                <div class="mb-3">
                                    <label for="listing_photo" class="form-label">Listing Photo (Optional)</label>
                                    <input 
                                        type="file" 
                                        id="listing_photo" 
                                        name="listing_photo" 
                                        class="form-control" 
                                        accept=".jpg, .jpeg, .png"
                                    >
                                    <small class="text-muted d-block mt-1">
                                        JPG or PNG format. Maximum file size: 3MB. A clear photo helps responders understand your plant's needs.
                                    </small>
                                </div>

                                <!-- Care Sheet PDF Upload -->
                                <div class="mb-3">
                                    <label for="care_sheet" class="form-label">Care Sheet PDF (Optional)</label>
                                    <input 
                                        type="file" 
                                        id="care_sheet" 
                                        name="care_sheet" 
                                        class="form-control" 
                                        accept=".pdf"
                                    >
                                    <small class="text-muted d-block mt-1">
                                        PDF format only. Maximum file size: 3MB. Upload a care guide for your plants if available.
                                    </small>
                                </div>
                            <!-- Date Range: Responsive two-column layout -->
                            <!-- Uses Bootstrap grid: stacks on mobile, side-by-side on medium+ screens -->
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

                            <hr class="my-4">

                            <!-- SECTION 3: Plant Details -->
                            <h5 class="mb-3">Plant Information</h5>

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

                            <hr class="my-4">

                            <!-- SECTION 4: Optional Details -->
                            <h5 class="mb-3">Additional Details (Optional)</h5>

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

                            <hr class="my-4">

                            <!-- Submit Button: Full-width on mobile using d-grid -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Create Listing
                                </button>
                            </div>

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