<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /plantbnb/users/messages.php");
    exit();
}


if (isset($_POST['recipient_id']) === false) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}


$recipientId = (int)$_POST['recipient_id'];


if (isset($_POST['message_text']) === false) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}


$messageText = trim($_POST['message_text']);


if ($messageText === '') {
    header("Location: /plantbnb/users/messages.php");
    exit();
}


$senderId = $_SESSION['user_id'];


if ($recipientId === $senderId) {
    header("Location: /plantbnb/users/messages.php");
    exit();
}



$insertSql = "
    INSERT INTO messages (sender_id, receiver_id, message_text, is_read, created_at)
    VALUES (:sender_id, :receiver_id, :message_text, 0, NOW())
";


$insertStatement = $connection->prepare($insertSql);


$insertStatement->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
$insertStatement->bindParam(':receiver_id', $recipientId, PDO::PARAM_INT);
$insertStatement->bindParam(':message_text', $messageText, PDO::PARAM_STR);


$insertStatement->execute();


header("Location: /plantbnb/users/message-conversation.php?user_id=" . $recipientId);
exit();
?>
