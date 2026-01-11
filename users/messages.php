<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// NOTE: Session variable accessed after user-auth.php validates authentication state.
$currentUserId = $_SESSION['user_id'];

// Query retrieves all conversations for the current user by finding users they have exchanged messages with.
// Uses UNION to combine sender and receiver relationships, eliminating duplicates automatically.
$conversationSql = "
    SELECT DISTINCT u.user_id, u.username
    FROM users u
    WHERE u.user_id IN (
        SELECT receiver_id FROM messages WHERE sender_id = :current_user_id
        UNION
        SELECT sender_id FROM messages WHERE receiver_id = :current_user_id
    )
    ORDER BY u.username ASC
";

// NOTE: PDO prepared statements protect against SQL injection by separating SQL logic from user data.
$conversationStatement = $connection->prepare($conversationSql);
$conversationStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$conversationStatement->execute();
$conversations = $conversationStatement->fetchAll(PDO::FETCH_ASSOC);

// Query retrieves all users except the current user for the recipient dropdown.
$allUsersSql = "SELECT user_id, username FROM users WHERE user_id != :current_user_id ORDER BY username ASC";

// NOTE: Using parameterized queries ensures user input cannot alter the SQL structure.
$allUsersStatement = $connection->prepare($allUsersSql);
$allUsersStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$allUsersStatement->execute();
$allUsers = $allUsersStatement->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - PlantBnB</title>
</head>
<body>

    <!-- Main Content Container -->
    <div class="container mt-4">
        
        <h1 class="mb-4">My Messages</h1>

        <!-- New Message Composition Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Send New Message</h5>
            </div>
            <div class="card-body">
                <form action="message-send.php" method="POST">
                    
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Send message to:</label>
                        <select name="recipient_id" id="recipient" class="form-select" required>
                            <option value="">-- Choose a user --</option>
                            <?php
                            // Dynamically populate recipient dropdown with all users except current user.
                            foreach ($allUsers as $user) {
                                // NOTE: htmlspecialchars() prevents XSS attacks by encoding HTML entities.
                                echo '<option value="' . htmlspecialchars($user['user_id']) . '">';
                                echo htmlspecialchars($user['username']);
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="message_text" class="form-label">Your message:</label>
                        <textarea name="message_text" id="message_text" class="form-control" rows="4" required></textarea>
                    </div>

                    <!-- Uses d-grid to make button span full width on mobile devices. -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Send Message</button>
                    </div>
                    
                </form>
            </div>
        </div>

        <!-- Conversation List Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Your Conversations</h5>
            </div>
            <div class="card-body">
                <?php
                if (count($conversations) === 0) {
                    echo '<p class="text-muted">You have no messages yet. Send a message above to start a conversation.</p>';
                } else {
                    // Display conversations using Bootstrap list-group component for consistent styling.
                    echo '<div class="list-group">';
                    
                    foreach ($conversations as $conversation) {
                        $otherUserId = $conversation['user_id'];
                        $otherUsername = htmlspecialchars($conversation['username']);
                        
                        // NOTE: URL encoding with htmlspecialchars() prevents injection attacks via query parameters.
                        echo '<a href="message-conversation.php?user_id=' . htmlspecialchars($otherUserId) . '" class="list-group-item list-group-item-action">';
                        echo '<strong>' . $otherUsername . '</strong>';
                        echo '</a>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>

    </div>

</body>
</html>