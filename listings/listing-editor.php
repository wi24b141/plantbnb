<?php
require_once __DIR__ . '/../includes/header.php';        
require_once __DIR__ . '/../includes/user-auth.php';   
require_once __DIR__ . '/../includes/db.php';            
require_once __DIR__ . '/../includes/file-upload-helper.php';  






$userID = intval($_SESSION['user_id']);









if (!isset($_GET['id'])) {
    
    
    header('Location: /plantbnb/users/dashboard.php');
    exit; 
}



$listingID = intval($_GET['id']);


if ($listingID <= 0) {
    
    header('Location: /plantbnb/users/dashboard.php');
    exit;
}








try {
    
    
    
    $fetchQuery = "
        SELECT 
            listings.listing_id,
            listings.user_id,
            listings.listing_type,
            listings.title,
            listings.description,
            listings.listing_photo_path,
            listings.care_sheet_path,
            listings.location_approx,
            listings.start_date,
            listings.end_date,
            listings.experience,
            listings.price_range,
            plants.plant_type,
            plants.watering_needs,
            plants.light_needs
        FROM listings
        LEFT JOIN plants ON listings.listing_id = plants.listing_id
        WHERE listings.listing_id = :listingID
    ";
    
    
    $fetchStatement = $connection->prepare($fetchQuery);
    
    
    $fetchStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
    
    
    $fetchStatement->execute();
    
    
    
    
    
    $listing = $fetchStatement->fetch(PDO::FETCH_ASSOC);
    
    
    if (!$listing) {
        
        header('Location: /plantbnb/users/dashboard.php');
        exit;
    }
    
    
    
    if ($listing['user_id'] !== $userID) {
        
        
        header('Location: /plantbnb/users/dashboard.php');
        exit;
    }
    
} catch (PDOException $error) {
    
    die("Database error: " . htmlspecialchars($error->getMessage()));
}








$listingType = $listing['listing_type'];
$title = $listing['title'];
$description = $listing['description'];
$locationApprox = $listing['location_approx'];
$startDate = $listing['start_date'];
$endDate = $listing['end_date'];



$experience = $listing['experience'] ?? '';
$priceRange = $listing['price_range'] ?? '';


$plantType = $listing['plant_type'] ?? '';
$wateringNeeds = $listing['watering_needs'] ?? '';
$lightNeeds = $listing['light_needs'] ?? '';



$errors = [];

$successMessage = '';




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    
    
    
    
    
    
    
    
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

    
    
    
    
    
    
    
    
    if (empty($listingType)) {
        
        $errors[] = 'Please select a valid listing type (Offer or Need).';
    } else {
        
        
        if ($listingType !== 'offer' && $listingType !== 'need') {
            $errors[] = 'Please select a valid listing type (Offer or Need).';
        }
    }

    
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

    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    } else {
        
        if (strlen($description) < 20) {
            $errors[] = 'Description must be at least 20 characters long.';
        }
    }

    
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

    
    
    if (!empty($startDate) && !empty($endDate)) {
        
        
        
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        
        if ($endTimestamp <= $startTimestamp) {
            $errors[] = 'End date must be after start date.';
        }
    }

    
    
    
    
    
    
    
    
    
    
    
    
    
    $listingPhotoResult = uploadFile(
        'listing_photo',                           
        __DIR__ . '/../uploads/listings',          
        ['image/jpeg', 'image/png'],               
        3 * 1024 * 1024                            
    );

    
    
    
    $listingPhotoPath = $listing['listing_photo_path']; 
    if ($listingPhotoResult !== null) {
        
        if (strpos($listingPhotoResult, '/') === false) {
            
            $errors[] = "Listing photo: " . $listingPhotoResult;
        } else {
            
            
            $listingPhotoPath = $listingPhotoResult;
        }
    }

    
    $careSheetResult = uploadFile(
        'care_sheet',                              
        __DIR__ . '/../uploads/caresheets',        
        ['application/pdf'],                       
        3 * 1024 * 1024                            
    );

    
    $careSheetPath = $listing['care_sheet_path']; 
    if ($careSheetResult !== null) {
        
        if (strpos($careSheetResult, '/') === false) {
            
            $errors[] = "Care sheet: " . $careSheetResult;
        } else {
            
            
            $careSheetPath = $careSheetResult;
        }
    }

    
    
    
    
    
    
    if (empty($errors)) {
        
        
        
        try {
            
            
            
            
            
            
            
            
            
            
            
            $updateListingQuery = "
                UPDATE listings 
                SET 
                    listing_type = :listingType,
                    title = :title,
                    description = :description,
                    listing_photo_path = :listingPhotoPath,
                    care_sheet_path = :careSheetPath,
                    location_approx = :locationApprox,
                    start_date = :startDate,
                    end_date = :endDate,
                    experience = :experience,
                    price_range = :priceRange
                WHERE listing_id = :listingID
            ";

            
            
            
            
            $updateListingStatement = $connection->prepare($updateListingQuery);

            
            
            
            
            
            
            $updateListingStatement->bindParam(':listingType', $listingType, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':title', $title, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':description', $description, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':listingPhotoPath', $listingPhotoPath, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':careSheetPath', $careSheetPath, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':locationApprox', $locationApprox, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':experience', $experience, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':priceRange', $priceRange, PDO::PARAM_STR);
            $updateListingStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            
            
            
            $updateListingStatement->execute();

            
            
            
            
            
            
            
            
            $updatePlantQuery = "
                UPDATE plants 
                SET 
                    plant_type = :plantType,
                    watering_needs = :wateringNeeds,
                    light_needs = :lightNeeds
                WHERE listing_id = :listingID
            ";

            
            $updatePlantStatement = $connection->prepare($updatePlantQuery);

            
            $updatePlantStatement->bindParam(':plantType', $plantType, PDO::PARAM_STR);
            $updatePlantStatement->bindParam(':wateringNeeds', $wateringNeeds, PDO::PARAM_STR);
            $updatePlantStatement->bindParam(':lightNeeds', $lightNeeds, PDO::PARAM_STR);
            $updatePlantStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);

            
            $updatePlantStatement->execute();

            
            $successMessage = "Your listing has been updated successfully!";

            
            
            
            
            
            
            
            $refreshQuery = "
                SELECT 
                    listings.listing_id,
                    listings.user_id,
                    listings.listing_type,
                    listings.title,
                    listings.description,
                    listings.listing_photo_path,
                    listings.care_sheet_path,
                    listings.location_approx,
                    listings.start_date,
                    listings.end_date,
                    listings.experience,
                    listings.price_range,
                    plants.plant_type,
                    plants.watering_needs,
                    plants.light_needs
                FROM listings
                LEFT JOIN plants ON listings.listing_id = plants.listing_id
                WHERE listings.listing_id = :listingID
            ";
            
            $refreshStatement = $connection->prepare($refreshQuery);
            $refreshStatement->bindParam(':listingID', $listingID, PDO::PARAM_INT);
            $refreshStatement->execute();
            $listing = $refreshStatement->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $error) {
            
            
            
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
    <title>Edit Listing - PlantBnB</title>
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
            
            if (!empty($successMessage)) {
                
                
                
                
                
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
                    
                    <!-- card-header = special header area at top of card -->
                    <!-- bg-success = green background color -->
                    <!-- text-white = white text color -->
                    <div class="card-header bg-success text-white">
                        <!-- mb-0 = margin-bottom: 0 (no space below) -->
                        <h3 class="mb-0">Edit Listing</h3>
                        <!-- small = smaller font size -->
                        <p class="mb-0 small">Update your plant care listing</p>
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
                        <!-- action="" means "submit to the same page (listing-creator.php)" -->
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
                                <select id="listing_type" name="listing_type" class="form-select" required>
                                    <option value="">-- Select Type --</option>
                                    
                                    <!-- Check if 'offer' was previously selected -->
                                    <!-- If yes, add the 'selected' attribute so it stays selected -->
                                    <option value="offer" <?php if ($listingType === 'offer') { echo 'selected'; } ?>>
                                        Offer (I can provide plant care)
                                    </option>
                                    
                                    <!-- Check if 'need' was previously selected -->
                                    <option value="need" <?php if ($listingType === 'need') { echo 'selected'; } ?>>
                                        Need (I am looking for plant care)
                                    </option>
                                </select>
                                
                                <!-- text-muted = gray text color (for help text) -->
                                <!-- d-block = display: block (makes it appear on its own line) -->
                                <!-- mt-1 = margin-top: 0.25rem (small space above) -->
                                <small class="text-muted d-block mt-1">
                                    Choose "Offer" if you can take care of plants, or "Need" if you're looking for someone to care for your plants
                                </small>
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
                                    Update Listing
                                </button>
                            </div>

                            <!-- Help text explaining what happens after submit -->
                            <!-- text-center = centers the text horizontally -->
                            <!-- mt-3 = margin-top: 1rem -->
                            <small class="text-muted d-block text-center mt-3">
                                * Required fields. Your changes will be saved immediately.
                            </small>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>