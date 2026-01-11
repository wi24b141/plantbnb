<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';



$currentUserID = intval($_SESSION['user_id']);


$allUsers = [];
$successMessage = '';
$errorMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    $ratedUserID = intval($_POST['rated_user_id']);
    $rating = intval($_POST['rating']);
    
    
    if ($ratedUserID === 0) {
        $errorMessage = 'Please select a user to rate.';
    }
    else if ($rating < 1 || $rating > 5) {
        $errorMessage = 'Please select a rating between 1 and 5.';
    }
    else if ($ratedUserID === $currentUserID) {
        $errorMessage = 'You cannot rate yourself.';
    }
    else {
        
        
        try {
            
            $checkQuery = "SELECT rating_id FROM ratings WHERE rater_user_id = :raterUserID AND rated_user_id = :ratedUserID";
            
            
            $checkStmt = $connection->prepare($checkQuery);
            $checkStmt->bindParam(':raterUserID', $currentUserID, PDO::PARAM_INT);
            $checkStmt->bindParam(':ratedUserID', $ratedUserID, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $errorMessage = 'You have already rated this user.';
            } else {
                
                $insertQuery = "
                    INSERT INTO ratings 
                    (rater_user_id, rated_user_id, rating)
                    VALUES
                    (:raterUserID, :ratedUserID, :rating)
                ";

                
                $insertStatement = $connection->prepare($insertQuery);
                $insertStatement->bindParam(':raterUserID', $currentUserID, PDO::PARAM_INT);
                $insertStatement->bindParam(':ratedUserID', $ratedUserID, PDO::PARAM_INT);
                $insertStatement->bindParam(':rating', $rating, PDO::PARAM_INT);
                $insertStatement->execute();

                $successMessage = 'Rating submitted successfully!';
            }

        } catch (PDOException $error) {
            
            $errorMessage = 'Database error: ' . $error->getMessage();
        }
    }
}


try {
    
    
    $usersQuery = "
        SELECT 
            user_id,
            username
        FROM users
        WHERE user_id != :currentUserID
        ORDER BY username ASC
    ";
    
    
    $usersStatement = $connection->prepare($usersQuery);
    $usersStatement->bindParam(':currentUserID', $currentUserID, PDO::PARAM_INT);
    $usersStatement->execute();
    
    
    $allUsers = $usersStatement->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $error) {
    $errorMessage = 'Database error: ' . $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Viewport meta tag ensures proper scaling on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate User</title>
</head>
<body>
    <!-- Main content container: Bootstrap container class provides responsive width and horizontal padding -->
    <div class="container mt-4">
        
        <!-- Page heading with bottom margin (mb-4) for visual spacing -->
        <h1 class="mb-4">Rate User</h1>
        
        <!-- Success alert: Bootstrap alert-success provides semantic green styling -->
        <?php
            if (!empty($successMessage)) {
                echo '<div class="alert alert-success" role="alert">';
                
                echo htmlspecialchars($successMessage);
                echo '</div>';
            }
        ?>
        
        <!-- Error alert: Bootstrap alert-danger provides semantic red styling -->
        <?php
            if (!empty($errorMessage)) {
                echo '<div class="alert alert-danger" role="alert">';
                
                echo htmlspecialchars($errorMessage);
                echo '</div>';
            }
        ?>
        
        <!-- Rating form: Centered using Bootstrap grid system -->
        <div class="row">
            <!-- NOTE: Bootstrap responsive grid - col-12 (full width on mobile), col-md-8 (66% width on medium+ screens), offset-md-2 (centers by adding 16.67% left margin) -->
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- Bootstrap card with subtle shadow for visual elevation -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        
                        <!-- NOTE: Empty action attribute submits to current page. POST method prevents sensitive data from appearing in URL. -->
                        <form action="" method="POST">
                            
                            <!-- User selection dropdown with Bootstrap styling (mb-3 adds vertical spacing) -->
                            <div class="mb-3">
                                <label for="rated_user_id" class="form-label">Select User to Rate</label>
                                
                                <!-- HTML5 required attribute enforces client-side validation -->
                                <select name="rated_user_id" id="rated_user_id" class="form-select" required>
                                    <option value="">-- Choose a user --</option>
                                    
                                    <?php
                                        
                                        foreach ($allUsers as $user) {
                                            $userID = intval($user['user_id']);
                                            
                                            $username = htmlspecialchars($user['username']);
                                            
                                            echo '<option value="' . $userID . '">' . $username . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- Star rating selection with descriptive labels -->
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating (1 to 5 Stars)</label>
                                
                                <select name="rating" id="rating" class="form-select" required>
                                    <option value="">-- Choose rating --</option>
                                    <option value="1">⭐ (1 Star - Poor)</option>
                                    <option value="2">⭐⭐ (2 Stars - Fair)</option>
                                    <option value="3">⭐⭐⭐ (3 Stars - Good)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 Stars - Very Good)</option>
                                    <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Excellent)</option>
                                </select>
                            </div>
                            
                            <!-- Submit button: d-grid makes button full-width, gap-2 adds spacing for button groups -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Submit Rating
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
