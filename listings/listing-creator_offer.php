<?php
/**
 * Offer Listing Creator - PlantBnB
 * 
 * Allows verified users to create "offer" type listings where they advertise
 * their availability to provide plant care services to others.
 * 
 * @requires header.php - Navigation and Bootstrap dependencies
 * @requires user-auth.php - Session validation and authentication
 * @requires db.php - PDO database connection
 * @requires file-upload-helper.php - File handling utilities (not used for offers)
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/file-upload-helper.php';

// NOTE: intval() provides type safety by ensuring user_id is strictly an integer,
// preventing type juggling vulnerabilities in comparisons and queries.
$userID = intval($_SESSION['user_id']);

// Initialize form variables to prevent "undefined variable" notices and support form persistence.
// Pre-populating with empty strings ensures clean re-rendering after validation failures.
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

// Separate error array from success string to accommodate multiple validation issues.
$errors = [];
$successMessage = '';

/**
 * Form Submission Handler
 * 
 * Processes POST requests containing offer listing data. Validates all inputs
 * before persisting to the database.
 */
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

    /**
     * Server-Side Input Validation
     * 
     * NOTE: Client-side validation (HTML5 required attributes) provides UX feedback,
     * but server-side validation is critical for security. Attackers can bypass
     * client-side checks by manipulating HTTP requests directly.
     */
    
    // Enforce listing type constraint for this specific form.
    if ($listingType !== 'offer') {
        $errors[] = 'Invalid listing type.';
    }

    // Title validation: presence and length constraints.
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

    // Description validation: minimum length ensures substantive content.
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } else {
        if (strlen($description) < 20) {
            $errors[] = 'Description must be at least 20 characters long.';
        }
    }

    // Location and date validations: required fields.
    if (empty($locationApprox)) {
        $errors[] = 'Location is required.';
    }

    if (empty($startDate)) {
        $errors[] = 'Start Date is required.';
    }

    if (empty($endDate)) {
        $errors[] = 'End Date is required.';
    }

    // Service details (plant_type, watering_needs, light_needs) remain optional
    // to allow flexibility in offer descriptions.

    // Logical date validation: end date must occur after start date.
    if (!empty($startDate) && !empty($endDate)) {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        if ($endTimestamp <= $startTimestamp) {
            $errors[] = 'End date must be after start date.';
        }
    }

    // Offer listings do not require photo uploads or care sheet documents.
    $listingPhotoPath = null;
    $careSheetPath = null;

    // Aggregate optional service fields into description for storage.
    if (!empty($plantType)) {
        $description .= "\n\nServices Offered: " . $plantType;
    }
    if (!empty($wateringNeeds)) {
        $description .= "\n\nAvailability/Terms: " . $wateringNeeds;
    }
    if (!empty($lightNeeds)) {
        $description .= "\n\nAdditional Notes: " . $lightNeeds;
    }

    /**
     * Database Persistence
     * 
     * NOTE: PDO (PHP Data Objects) provides a database-agnostic interface and supports
     * prepared statements, which are essential for preventing SQL injection attacks.
     * 
     * NOTE: try-catch blocks handle PDOException gracefully, preventing sensitive error
     * details from being exposed to end users while logging issues for developers.
     */
    if (empty($errors)) {
        try {
            // NOTE: Prepared statements with named placeholders (:userID) separate SQL logic
            // from user data, preventing SQL injection. Even if malicious input like
            // "'; DROP TABLE listings; --" is provided, it's treated as literal string data.
            
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

            // Prepare statement: compiles SQL with placeholders but does not execute.
            $insertListingStatement = $connection->prepare($insertListingQuery);

            // Bind PHP variables to SQL placeholders with explicit type declarations.
            // PDO::PARAM_INT and PDO::PARAM_STR enforce type safety at the database driver level.
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

            // Execute the prepared statement to persist the listing.
            $insertListingStatement->execute();

            // Retrieve auto-incremented primary key for the newly created listing.
            $newListingID = $connection->lastInsertId();

            // Offer listings do not create corresponding plant table entries.

            $successMessage = "Your listing has been created successfully!";

            // Reset form variables to empty state for subsequent listing creation.
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
            // Catch database exceptions and provide user-friendly error feedback.
            // NOTE: In production, detailed error messages should be logged server-side
            // rather than displayed to users to prevent information disclosure.
            $errors[] = "Database error: " . $error->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Viewport meta tag enables responsive design by setting viewport width to device width -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Offer - PlantBnB</title>
</head>
<body>
    <!-- Main Container: Bootstrap container class provides responsive fixed-width layout with horizontal padding -->
    <div class="container mt-4">
        
        <!-- Navigation: Back to Dashboard -->
        <div class="row mb-3">
            <!-- Bootstrap grid: col-12 (full width mobile), col-md-10 offset-md-1 (centered on desktop) -->
            <div class="col-12 col-md-10 offset-md-1">
                <a href="/plantbnb/users/dashboard.php" class="btn btn-outline-secondary btn-sm">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success Message Display -->
        <?php
            if (!empty($successMessage)) {
                // Bootstrap alert-success provides visual feedback for successful operations.
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">";
                
                // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) by encoding
                // HTML special characters. This ensures user input is rendered as text, not code.
                echo htmlspecialchars($successMessage);
                
                echo "      <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
        ?>

        <!-- Error Message Display -->
        <?php
            if (!empty($errors)) {
                // Bootstrap alert-danger provides visual emphasis for validation failures.
                echo "<div class=\"row mb-3\">";
                echo "  <div class=\"col-12 col-md-10 offset-md-1\">";
                echo "    <div class=\"alert alert-danger\" role=\"alert\">";
                echo "      <strong>Please fix the following errors:</strong>";
                echo "      <ul class=\"mb-0 mt-2\">";

                // Iterate through error array and sanitize each message for output.
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

        <!-- Main Form Card -->
        <div class="row mb-5">
            <div class="col-12 col-md-10 offset-md-1">
                <!-- Bootstrap card component provides structured layout with header/body sections -->
                <div class="card shadow-sm">
                    
                    <!-- Card Header -->
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">Create New Offer</h3>
                        <p class="mb-0 small">Post a plant care offer to the community</p>
                    </div>

                    <!-- Card Body: Form Content -->
                    <div class="card-body">
                        
                        <!-- Form submits to self via POST for server-side processing -->
                        <form method="POST" action="">
                            
                            <!-- Section 1: Basic Listing Information -->
                            <h5 class="mb-3">Basic Information</h5>

                            <!-- Listing Type: Fixed to 'offer' for this form -->
                            <div class="mb-3">
                                <label for="listing_type" class="form-label">Listing Type *</label>
                                
                                <!-- Hidden input ensures POST data contains listing_type='offer' -->
                                <input type="hidden" id="listing_type" name="listing_type" value="offer">
                                <select class="form-select" disabled>
                                    <option value="offer" selected>Offer (I can provide plant care)</option>
                                </select>
                                <small class="text-muted d-block mt-1">This form creates an "Offer" listing only.</small>
                            </div>

                            <!-- Title Input -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-control" 
                                    placeholder="E.g., Experienced plant-sitter available in Berlin" 
                                    value="<?php echo htmlspecialchars($title); ?>"
                                    required
                                >
                                <small class="text-muted d-block mt-1">Short, descriptive title (5-150 characters). Include location and availability.</small>
                            </div>

                            <!-- Description Textarea -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                
                                <!-- Textarea value is placed between opening and closing tags -->
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-control" 
                                    rows="5" 
                                    placeholder="Describe your services, experience, travel area, typical rates and availability..."
                                    required
                                ><?php echo htmlspecialchars($description); ?></textarea>
                                <small class="text-muted d-block mt-1">
                                    Describe your services, experience, availability, and any conditions (minimum 20 characters)
                                </small>
                            </div>

                            <!-- Offer listings do not require photo/care sheet uploads -->

                            <hr class="my-4">

                            <!-- Section 2: Location and Availability -->
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

                            <!-- Date Range: Responsive grid layout (stacked mobile, side-by-side desktop) -->
                            <div class="row">
                                
                                <!-- Start Date -->
                                <div class="col-12 col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    
                                    <!-- HTML5 date input provides native browser date picker -->
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

                            <!-- Section 3: Service Details (Optional) -->
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

                            <hr class="my-4">

                            <!-- Section 4: Additional Details (Optional) -->
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

                            <hr class="my-4">

                            <!-- Submit Button: d-grid ensures full-width mobile-friendly layout -->
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