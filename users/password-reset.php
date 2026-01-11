<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

$error = '';
$success = '';
$showForm = false;
$token = trim($_GET['token'] ?? '');

// Basic token validation
if (empty($token) || strlen($token) < 16) {
    $error = 'Invalid or missing token.';
} else {
    try {
        // Find token and ensure not expired
        $stmt = $connection->prepare('SELECT pr.id, pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.user_id WHERE pr.token = :token');
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Token not found or already used.';
        } else if (strtotime($row['expires_at']) < time()) {
            $error = 'This reset link has expired.';
        } else {
            $showForm = true;
            // Process password change
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = trim($_POST['password'] ?? '');
                $passwordConfirm = trim($_POST['password_confirm'] ?? '');

                if (empty($password) || strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else if ($password !== $passwordConfirm) {
                    $error = 'Passwords do not match.';
                } else {
                    // Update user's password
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $update = $connection->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :user_id');
                    $update->bindParam(':hash', $newHash, PDO::PARAM_STR);
                    $update->bindParam(':user_id', $row['user_id'], PDO::PARAM_INT);
                    $update->execute();

                    // Invalidate token
                    $del = $connection->prepare('DELETE FROM password_resets WHERE id = :id');
                    $del->bindParam(':id', $row['id'], PDO::PARAM_INT);
                    $del->execute();

                    $success = 'Your password has been reset. You can now log in.';
                    $showForm = false;
                }
            }
        }

    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set a new password</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12 col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Set a new password</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>'; } ?>
                        <?php if (!empty($success)) { echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>'; } ?>

                        <?php if ($showForm) { ?>
                        <form method="POST" action="?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm new password</label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success">Set password</button>
                            </div>
                        </form>
                        <?php } ?>
                    </div>
                    <div class="card-footer bg-light text-center">
                        <a href="/plantbnb/users/login.php">Back to login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
