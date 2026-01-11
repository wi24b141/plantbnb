<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';






if (!isset($isLoggedIn) || !$isLoggedIn) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Meine Listings</title>
    </head>
    <body>
        <div class="container mt-5">
            <div class="alert alert-info">Please <a href="/plantbnb/users/login.php">log in</a> to view your listings.</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}


$userListings = array();
$errorMessage = '';

try {
    
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

    $stmt = $connection->prepare($sql);
    $currentUserId = intval($_SESSION['user_id']);
    $stmt->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
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
    <div class="container mt-5">
        <h2 class="mb-4">My Listings</h2>

        <?php if ($errorMessage !== '') { ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php } ?>

        <?php if (count($userListings) > 0) {
            echo '<div class="row row-cols-1 row-cols-md-3 g-4">';
            foreach ($userListings as $listing) {
                $listingID = intval($listing['listing_id']);
                $safeTitle = htmlspecialchars($listing['title']);
                $safeLocation = htmlspecialchars($listing['location_approx']);
                $safeStartDate = htmlspecialchars($listing['start_date']);
                $safeEndDate = htmlspecialchars($listing['end_date']);
                $safeListingType = htmlspecialchars($listing['listing_type']);
                $safeStatus = htmlspecialchars($listing['status']);

                if ($listing['listing_photo_path'] !== null && $listing['listing_photo_path'] !== '') {
                    $photoPath = $listing['listing_photo_path'];
                    $fullPhotoPath = "/plantbnb/" . $photoPath;
                    $safePhotoPath = htmlspecialchars($fullPhotoPath);
                } else {
                    $safePhotoPath = null;
                }

                if ($safeListingType === 'offer') {
                    $badgeColor = 'success';
                } else {
                    $badgeColor = 'warning';
                }

                echo '<div class="col">';
                echo '  <div class="card h-100">';
                if ($safePhotoPath !== null) {
                    echo '    <img src="' . $safePhotoPath . '" alt="' . $safeTitle . '" class="card-img-top" style="height:200px;">';
                } else {
                    echo '    <div class="bg-light d-flex align-items-center justify-content-center" style="height:200px;">';
                    echo '      <span class="text-muted">No photo</span>';
                    echo '    </div>';
                }

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
</body>
</html>
