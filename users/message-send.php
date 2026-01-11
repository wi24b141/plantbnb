<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// Enforce POST-only access to prevent direct URL navigation to this endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// Validate recipient_id exists in POST data
if (isset($_POST['recipient_id']) === false) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// NOTE: Type casting to integer prevents SQL injection by ensuring numeric input
$recipientId = (int)$_POST['recipient_id'];

// Validate message_text exists in POST data
if (isset($_POST['message_text']) === false) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// Sanitize user input by removing leading and trailing whitespace
$messageText = trim($_POST['message_text']);

// Reject empty messages after sanitization
if ($messageText === '') {
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// NOTE: Session state is maintained across requests to track authenticated user identity
$senderId = $_SESSION['user_id'];

// Prevent self-messaging as a business logic constraint
if ($recipientId === $senderId) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// NOTE: Prepared statements with named placeholders protect against SQL injection attacks
// by separating SQL logic from user-supplied data
$insertSql = "
    INSERT INTO messages (sender_id, receiver_id, message_text, is_read, created_at)
    VALUES (:sender_id, :receiver_id, :message_text, 0, NOW())
";

// Prepare the SQL statement for secure execution
$insertStatement = $connection->prepare($insertSql);

// Bind parameters with explicit type declarations for additional security
$insertStatement->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
$insertStatement->bindParam(':receiver_id', $recipientId, PDO::PARAM_INT);
$insertStatement->bindParam(':message_text', $messageText, PDO::PARAM_STR);

// Execute the prepared statement to persist the message
$insertStatement->execute();

// Redirect to conversation view with recipient ID as query parameter
header("Location: /plantbnb/users/message-conversation.php?user_id=" . $recipientId);
exit();
?>
