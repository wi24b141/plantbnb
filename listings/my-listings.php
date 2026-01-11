<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/user-auth.php';

$userListings = array();
$errorMessage = '';

try {
    // NOTE: This query uses a parameterized WHERE clause to filter listings by the current user's ID.
    // The JOIN is not necessary here as we only need data from the listings table.
    $sql = "
        SELECT
            listings.listing_id,
            listings.title,
            listings.location_approx,
            listings.start_date,
            listings.end_date,
            listings.listing_type,
            listings.listing_photo_path,
            listings.status
        FROM listings
        WHERE listings.user_id = :current_user_id
        ORDER BY listings.created_at DESC
    ";

    // NOTE: PDO prepared statements with bound parameters protect against SQL Injection attacks.
    // The :current_user_id placeholder is safely replaced with the actual user ID.
    $stmt = $connection->prepare($sql);
    $currentUserId = intval($_SESSION['user_id']);
    $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Catch and handle database exceptions to prevent application crashes
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings</title>
</head>
<body>
    <!-- Main Content Container: Uses Bootstrap 'container' class for responsive centered layout -->
    <!-- mt-5 applies top margin for spacing from navbar, mb-4 adds bottom margin to heading -->
    <div class="container mt-5">
        <h2 class="mb-4">My Listings</h2>

        <?php if ($errorMessage !== '') { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php } ?>

        <?php 
        // NOTE: Output is escaped using htmlspecialchars() to prevent XSS (Cross-Site Scripting) attacks.
        if (count($userListings) > 0) {
            // Bootstrap grid: row-cols-1 = 1 column on mobile, row-cols-md-3 = 3 columns on medium+ screens
            // g-4 adds consistent gutters (spacing) between cards for responsive design
            echo '<div class="row row-cols-1 row-cols-md-3 g-4">';
            foreach ($userListings as $listing) {
                // Sanitize all output data to prevent XSS attacks
                $listingID = intval($listing['listing_id']);
                $safeTitle = htmlspecialchars($listing['title']);
                $safeLocation = htmlspecialchars($listing['location_approx']);
                $safeStartDate = htmlspecialchars($listing['start_date']);
                $safeEndDate = htmlspecialchars($listing['end_date']);
                $safeListingType = htmlspecialchars($listing['listing_type']);
                $safeStatus = htmlspecialchars($listing['status']);

                // Construct safe photo path or set to null if no photo exists
                if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                    $photoPath = $listing['listing_photo_path'];
                    $fullPhotoPath = "/plantbnb/" . $photoPath;
                    $safePhotoPath = htmlspecialchars($fullPhotoPath);
                } else {
                    $safePhotoPath = null;
                }

                // Assign Bootstrap badge color: green for 'offer', yellow for 'need'
                if ($safeListingType === 'offer') {
                    $badgeColor = 'success';
                } else {
                    $badgeColor = 'warning';
                }

                // Bootstrap card layout: h-100 ensures all cards in the row have equal height
                echo '<div class="col">';
                echo '  <div class="card h-100">';
                // Display listing photo or placeholder with consistent 200px height
                if ($safePhotoPath !== null) {
                    echo '    <img src="' . $safePhotoPath . '" alt="' . $safeTitle . '" class="card-img-top" style="height:200px;">';
                } else {
                    // d-flex with align-items-center and justify-content-center centers the placeholder text
                    echo '    <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">';
                    echo '      <span class="text-muted">No photo</span>';
                    echo '    </div>';
                }

                // Card header: displays listing type badge and status
                // float-end aligns status text to the right side
                echo '    <div class="card-header bg-light">';
                echo '      <span class="badge bg-' . $badgeColor . '">' . ucfirst($safeListingType) . '</span>';
                echo '      <span class="float-end text-muted">' . $safeStatus . '</span>';
                echo '    </div>';

                echo '    <div class="card-body">';
                echo '      <h5 class="card-title">' . $safeTitle . '</h5>';
                echo '      <p class="card-text">';
                echo '        <strong>Location:</strong> ' . $safeLocation . '<br>';
                echo '        <strong>Available:</strong> ' . $safeStartDate . ' to ' . $safeEndDate;
                echo '      </p>';
                // NOTE: Responsive button layout demonstrates mobile-first design principles.
                // d-grid gap-2 = stacked buttons with spacing on mobile
                // d-md-flex = horizontal flexbox layout on medium+ screens
                // flex-md-fill = equal-width buttons on medium+ screens
                echo '      <div class="d-grid gap-2 d-md-flex">';
                echo '        <a href="listing-details.php?id=' . $listingID . '" class="btn btn-success flex-md-fill me-md-2">View Details</a>';
                echo '        <a href="listing-editor.php?id=' . $listingID . '" class="btn btn-success flex-md-fill">Edit Listing</a>';
                echo '      </div>';
                echo '    </div>';

                echo '  </div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-info">Du hast noch keine Listings.</div>';
        } ?>

    </div>
    <!-- End Main Content Container -->
</body>
</html>
