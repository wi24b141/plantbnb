<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';

// Simple password reset request page
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            // Find user by email
            $stmt = $connection->prepare('SELECT user_id FROM users WHERE email = :email');
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate token and expiry (1 hour)
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                // Insert token into password_resets table
                // NOTE: The application expects the following table to exist:
                // CREATE TABLE password_resets (
                //   id INT AUTO_INCREMENT PRIMARY KEY,
                //   user_id INT NOT NULL,
                //   token VARCHAR(128) NOT NULL,
                //   expires_at DATETIME NOT NULL,
                //   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                // );
                $insert = $connection->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
                $insert->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);
                $insert->bindParam(':token', $token, PDO::PARAM_STR);
                $insert->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
                $insert->execute();

                // Minimal flow: display the reset link so email sending is not required
                $resetLink = '/plantbnb/users/password-reset.php?token=' . $token;
                $message = 'If that email exists, a reset link was generated. Use this link (for demo): ' . htmlspecialchars($resetLink);
            } else {
                // Do not reveal whether the email exists — generic message
                $message = 'If that email exists, a reset link was generated. Check your email.';
            }

        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12 col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Reset your password</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)) { echo '<div class="alert alert-info">' . htmlspecialchars($message) . '</div>'; } ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success">Request reset</button>
                            </div>
                        </form>
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
