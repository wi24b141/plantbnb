<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$currentUserId = $_SESSION['user_id'];

// ============================================================================
// STEP 4: GET THE OTHER USER'S ID FROM URL
// ============================================================================
// WHY: Every conversation is between TWO people
// We need to know who the OTHER person is
// This comes from the URL like: message-conversation.php?user_id=5

// Check if the user_id parameter exists in the URL
if (isset($_GET['user_id']) === false) {
    // No user_id in URL - we cannot show a conversation
    // Send user back to messages inbox
    header("Location: messages.php");
    exit();
}

// Get the other user's ID from the URL
// (int) converts it to an integer (whole number)
// WHY: This protects against SQL injection attacks
$otherUserId = (int)$_GET['user_id'];

// ============================================================================
// STEP 5: VALIDATE THE OTHER USER ID
// ============================================================================
// WHY: We need to make sure the user is not trying to message themselves
// A conversation with yourself makes no sense
if ($otherUserId === $currentUserId) {
    // User tried to message themselves
    // Send them back to inbox
    header("Location: messages.php");
    exit();
}

// ============================================================================
// STEP 6: GET OTHER USER'S INFORMATION FROM DATABASE
// ============================================================================
// WHY: We need to show the other user's name at the top of the page

// Write the SQL query
// SELECT means we want to GET data
// We want the user_id and username columns
// FROM the users table
// WHERE the user_id equals the other user's ID
// LIMIT 1 means only return one result
$otherUserSql = "SELECT user_id, username FROM users WHERE user_id = :other_user_id LIMIT 1";

// Prepare the SQL statement
// WHY: Prepared statements protect against SQL injection
// :other_user_id is a placeholder we will fill in
$otherUserStatement = $connection->prepare($otherUserSql);

// Bind the parameter
// WHY: Replace the :other_user_id placeholder with the actual value
// PDO::PARAM_INT means the value must be an integer
$otherUserStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);

// Execute the query
// WHY: This actually runs the SQL query
$otherUserStatement->execute();

// Fetch the result
// WHY: Get the data that the query returned
// fetch() returns one row as an associative array
$otherUser = $otherUserStatement->fetch(PDO::FETCH_ASSOC);

// Check if the user exists
// WHY: The user_id from the URL might not exist in the database
if ($otherUser === false) {
    // User does not exist
    // Send back to inbox
    header("Location: messages.php");
    exit();
}

// ============================================================================
// STEP 7: GET ALL MESSAGES IN THIS CONVERSATION
// ============================================================================
// WHY: We need to show all messages between these two users
// A conversation has two directions:
// 1. Messages the current user SENT to the other user
// 2. Messages the other user SENT to the current user

// Write the SQL query
// SELECT: Get these columns from the database
//   - message_id (unique ID for each message)
//   - sender_id (who sent the message)
//   - receiver_id (who received the message)
//   - message_text (the actual message content)
//   - created_at (when the message was sent)
// FROM messages: Get data from the messages table
// WHERE: Only get messages where EITHER:
//   - sender is current user AND receiver is other user
//   OR
//   - sender is other user AND receiver is current user
// ORDER BY created_at ASC: Sort by time, oldest first
//   ASC means "ascending" (oldest to newest)
//   This makes the chat read from top to bottom
$messagesSql = "
    SELECT message_id, sender_id, receiver_id, message_text, created_at
    FROM messages
    WHERE 
        (sender_id = :current_user_id AND receiver_id = :other_user_id)
        OR
        (sender_id = :other_user_id AND receiver_id = :current_user_id)
    ORDER BY created_at ASC
";

// Prepare the SQL statement
$messagesStatement = $connection->prepare($messagesSql);

// Bind both user IDs to the placeholders
$messagesStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
$messagesStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);

// Execute the query
$messagesStatement->execute();

// Fetch all messages as an array
// WHY: fetchAll() returns ALL rows from the query as an array
// Each message will be one element in the array
$messages = $messagesStatement->fetchAll(PDO::FETCH_ASSOC);

// ============================================================================
// STEP 8: MARK MESSAGES AS READ
// ============================================================================
// WHY: When the user opens this conversation, they are reading the messages
// We mark messages as read so we can show unread counts elsewhere

// Write the UPDATE query
// UPDATE messages: Change data in the messages table
// SET is_read = 1: Change the is_read column to 1
//   (1 means true/read, 0 means false/unread)
// WHERE: Only update messages where:
//   - sender is the OTHER user (they sent it to us)
//   - receiver is the CURRENT user (we received it)
//   - is_read is 0 (currently unread)
$markReadSql = "
    UPDATE messages 
    SET is_read = 1 
    WHERE sender_id = :other_user_id 
    AND receiver_id = :current_user_id 
    AND is_read = 0
";

// Prepare the SQL statement
$markReadStatement = $connection->prepare($markReadSql);

// Bind the parameters
$markReadStatement->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
$markReadStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);

// Execute the query
// WHY: This updates the is_read column in the database
$markReadStatement->execute();

// ============================================================================
// END OF PHP LOGIC
// HTML STARTS BELOW
// ============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- 
        CHARACTER ENCODING
        WHY: UTF-8 supports all languages and special characters
    -->
    <meta charset="UTF-8">
    
    <!-- 
        VIEWPORT META TAG (REQUIRED for mobile)
        WHY: This tells mobile browsers to display the page at the correct width
        Without this, phones would show the desktop version zoomed out
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- PAGE TITLE (shows in browser tab) -->
    <title>Conversation with <?php echo htmlspecialchars($otherUser['username']); ?> - PlantBnB</title>
    
    
    <!-- 
        CUSTOM CSS for message bubbles
        WHY: We need to style messages differently based on who sent them
        - Messages YOU sent: right side, blue
        - Messages you RECEIVED: left side, gray
    -->
    <style>
        /* Style for messages sent BY you (right side, blue) */
        .message-sent {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            margin-left: 30%;
        }
        
        /* Style for messages received FROM other user (left side, gray) */
        .message-received {
            background-color: #e9ecef;
            color: black;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            margin-right: 30%;
        }
        
        /* Timestamp style (small gray text) */
        .message-time {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* For messages you sent, make timestamp lighter */
        .message-sent .message-time {
            color: #e9ecef;
        }
    </style>
</head>
<body>

    <!-- 
        MAIN CONTAINER
        Bootstrap "container" class: Centers content and adds padding on sides
        Bootstrap "mt-4" class: Adds margin at the top (spacing from header)
    -->
    <div class="container mt-4">
        
        <!-- 
            BACK BUTTON
            WHY: Users need a way to go back to inbox without browser back button
            Bootstrap "mb-3" class: Adds margin at the bottom (spacing)
        -->
        <div class="mb-3">
            <a href="messages.php" class="btn btn-outline-secondary">← Back to all conversations</a>
        </div>

        <!-- 
            PAGE HEADING
            Shows who we are talking to
            Bootstrap "mb-4" class: Adds margin at the bottom
        -->
        <h1 class="mb-4">Conversation with <?php echo htmlspecialchars($otherUser['username']); ?></h1>

        <!-- ================================================================ -->
        <!-- SECTION 1: DISPLAY ALL MESSAGES -->
        <!-- ================================================================ -->
        <!-- 
            Bootstrap "card" class: Creates a box with a border
            Bootstrap "mb-4" class: Adds margin at the bottom
        -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Messages</h5>
            </div>
            <!-- 
                Card body with scrolling
                WHY: If there are many messages, we want a scrollbar
                max-height: Limits the height to 500 pixels
                overflow-y: auto: Adds a vertical scrollbar if content is too tall
            -->
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php
                // Check if there are any messages in this conversation
                // count() returns the number of elements in the array
                if (count($messages) === 0) {
                    // No messages yet
                    // Bootstrap "text-muted" class: Makes text gray (less prominent)
                    echo '<p class="text-muted">No messages yet. Send the first message below!</p>';
                } else {
                    // There ARE messages, so we display them
                    // Loop through each message one by one
                    foreach ($messages as $message) {
                        
                        // Get the sender ID of this specific message
                        $senderId = $message['sender_id'];
                        
                        // Get the message text
                        // htmlspecialchars() prevents XSS attacks
                        // WHY: If someone types <script> tags, they will be escaped
                        $messageText = htmlspecialchars($message['message_text']);
                        
                        // Get the timestamp
                        // This comes from database like "2026-01-07 15:30:00"
                        $timestamp = $message['created_at'];
                        
                        // Format the timestamp to be human-readable
                        // strtotime() converts the database format to a Unix timestamp
                        // date() then formats it nicely
                        // Example output: "Jan 7, 2026 at 3:45 PM"
                        $formattedTimestamp = date('M j, Y \a\t g:i A', strtotime($timestamp));
                        
                        // Check WHO sent this message
                        // WHY: Messages I sent look different from messages I received
                        if ($senderId === $currentUserId) {
                            // The CURRENT user sent this message
                            // Display it on the RIGHT side in BLUE
                            echo '<div class="message-sent">';
                            echo '<div>' . $messageText . '</div>';
                            echo '<div class="message-time">You • ' . $formattedTimestamp . '</div>';
                            echo '</div>';
                        } else {
                            // The OTHER user sent this message
                            // Display it on the LEFT side in GRAY
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

        <!-- ================================================================ -->
        <!-- SECTION 2: SEND A REPLY FORM -->
        <!-- ================================================================ -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Send a Reply</h5>
            </div>
            <div class="card-body">
                <!-- 
                    HTML FORM
                    WHY: Forms allow users to submit data to the server
                    action="message-send.php": When submitted, data goes to message-send.php
                    method="POST": Data is sent using HTTP POST (more secure than GET)
                -->
                <form action="message-send.php" method="POST">
                    
                    <!-- 
                        HIDDEN INPUT FIELD
                        WHY: We need to tell message-send.php WHO to send the message to
                        type="hidden": User does not see this field
                        But the value is still sent when form is submitted
                        name="recipient_id": Variable name that message-send.php will use
                    -->
                    <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($otherUserId); ?>">
                    
                    <!-- 
                        TEXT AREA FOR MESSAGE
                        WHY: Users need a place to type their message
                        Bootstrap "mb-3" class: Adds margin at the bottom
                    -->
                    <div class="mb-3">
                        <label for="message_text" class="form-label">Your message:</label>
                        <!-- 
                            textarea: Multi-line text input
                            name="message_text": Variable name for message content
                            id="message_text": Connects this field to the label above
                            Bootstrap "form-control" class: Styles the input field
                            rows="3": Text box will be 3 lines tall
                            required: User MUST type something before submitting
                        -->
                        <textarea name="message_text" id="message_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- 
                        SUBMIT BUTTON
                        WHY: Users need a button to click to send the form
                        Bootstrap "d-grid" class: Makes button full-width to match text box
                        WHY: Full-width buttons are easier to tap on touch screens
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
