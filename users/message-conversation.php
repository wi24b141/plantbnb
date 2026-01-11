<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$currentUserId = $_SESSION['user_id'];

// Validate recipient user ID from URL parameter
// NOTE: Using isset() prevents undefined index errors and enforces required parameters.
if (isset($_GET['user_id']) === false) {
    header("Location: messages.php");
    exit();
}

// Type-cast to integer to sanitize input and prevent SQL injection
$otherUserId = (int)$_GET['user_id'];

// Prevent users from messaging themselves (business logic validation)
if ($otherUserId === $currentUserId) {
    header("Location: messages.php");
    exit();
}

// Retrieve recipient user information for display
// NOTE: PDO prepared statements protect against SQL Injection by separating SQL logic from data.
$otherUserSql = "SELECT user_id, username FROM users WHERE user_id = :other_user_id LIMIT 1";
$otherUserStatement = $connection->prepare($otherUserSql);
$otherUserStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
$otherUserStatement->execute();
$otherUser = $otherUserStatement->fetch(PDO::FETCH_ASSOC);

// Validate that the recipient exists in the database
if ($otherUser === false) {
    header("Location: messages.php");
    exit();
}

// Retrieve all messages in bidirectional conversation
// NOTE: The OR condition captures both directions (sent and received) to build a complete thread.
// Chronological ordering (ASC) ensures proper conversational flow from oldest to newest.
$messagesSql = "
    SELECT message_id, sender_id, receiver_id, message_text, created_at
    FROM messages
    WHERE 
        (sender_id = :current_user_id AND receiver_id = :other_user_id)
        OR
        (sender_id = :other_user_id AND receiver_id = :current_user_id)
    ORDER BY created_at ASC
";

// NOTE: Parameterized queries prevent SQL Injection by treating user input as data, not executable code.
$messagesStatement = $connection->prepare($messagesSql);
$messagesStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$messagesStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
$messagesStatement->execute();
$messages = $messagesStatement->fetchAll(PDO::FETCH_ASSOC);

// Update read status for received messages
// NOTE: This UPDATE only affects messages WHERE the current user is the receiver,
// ensuring unread counts in the inbox remain accurate across the application.
$markReadSql = "
    UPDATE messages 
    SET is_read = 1 
    WHERE sender_id = :other_user_id 
    AND receiver_id = :current_user_id 
    AND is_read = 0
";

$markReadStatement = $connection->prepare($markReadSql);
$markReadStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
$markReadStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$markReadStatement->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- UTF-8 encoding ensures compatibility with international characters and special symbols -->
    <meta charset="UTF-8">
    
    <!-- Viewport meta tag enables responsive design by setting device width and initial scale -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Conversation with <?php echo htmlspecialchars($otherUser['username']); ?> - PlantBnB</title>
    
    <!-- 
        Custom CSS: Message bubble styling differentiates sent vs. received messages.
        Sent messages align right with primary color; received messages align left with neutral color.
    -->
    <style>
        
        .message-sent {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            margin-left: 30%;
        }
        
        
        .message-received {
            background-color: #e9ecef;
            color: black;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            margin-right: 30%;
        }
        
        
        .message-time {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        
        .message-sent .message-time {
            color: #e9ecef;
        }
    </style>
</head>
<body>

    <!-- Main layout container: Bootstrap "container" centers content with responsive padding -->
    <!-- Bootstrap "mt-4" adds top margin (1.5rem) for spacing from navbar -->
    <div class="container mt-4">
        
        <!-- Navigation: Back link to inbox using Bootstrap "btn-outline-secondary" for secondary action styling -->
        <div class="mb-3">
            <a href="messages.php" class="btn btn-outline-secondary">← Back to all conversations</a>
        </div>

        <!-- Page heading with recipient username (XSS protection via htmlspecialchars) -->
        <h1 class="mb-4">Conversation with <?php echo htmlspecialchars($otherUser['username']); ?></h1>

        <!-- MESSAGE DISPLAY SECTION -->
        <!-- Bootstrap "card" component provides structured content container with header/body sections -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Messages</h5>
            </div>
            <!-- 
                Scrollable message area: max-height limits vertical space,
                overflow-y enables scrolling for lengthy conversations
            -->
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php
                // Display messages or empty state
                if (count($messages) === 0) {
                    echo '<p class="text-muted">No messages yet. Send the first message below!</p>';
                } else {
                    // Iterate through messages and render with conditional styling based on sender
                    foreach ($messages as $message) {
                        $senderId = $message['sender_id'];
                        
                        // NOTE: htmlspecialchars() prevents XSS (Cross-Site Scripting) by escaping HTML entities.
                        // User-generated content is never rendered as executable code.
                        $messageText = htmlspecialchars($message['message_text']);
                        
                        $timestamp = $message['created_at'];
                        $formattedTimestamp = date('M j, Y \a\t g:i A', strtotime($timestamp));
                        
                        // Conditional rendering: sent messages styled differently from received messages
                        if ($senderId === $currentUserId) {
                            echo '<div class="message-sent">';
                            echo '<div>' . $messageText . '</div>';
                            echo '<div class="message-time">You • ' . $formattedTimestamp . '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="message-received">';
                            echo '<div>' . $messageText . '</div>';
                            echo '<div class="message-time">' . htmlspecialchars($otherUser['username']) . ' • ' . $formattedTimestamp . '</div>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <!-- REPLY FORM SECTION -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Send a Reply</h5>
            </div>
            <div class="card-body">
                <!-- 
                    Form submission via POST method (more secure than GET for sensitive data).
                    NOTE: POST requests do not expose form data in URL, preventing accidental data leakage.
                -->
                <form action="message-send.php" method="POST">
                    
                    <!-- Hidden input field passes recipient ID securely to processing script -->
                    <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($otherUserId); ?>">
                    
                    <!-- Message input field with HTML5 client-side validation -->
                    <div class="mb-3">
                        <label for="message_text" class="form-label">Your message:</label>
                        <!-- 
                            Bootstrap "form-control" applies consistent styling across form inputs.
                            Required attribute enforces non-empty submission (client-side validation).
                        -->
                        <textarea name="message_text" id="message_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- 
                        Submit button: Bootstrap "d-grid" creates full-width button layout
                        for improved mobile usability and visual consistency.
                    -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</body>
</html>
