<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

$currentUserId = $_SESSION['user_id'];

// ===========================
// STEP 4: GET ALL CONVERSATIONS
// ===========================
// WHAT IS A CONVERSATION?
// A conversation is all the messages between the current user and one other user
// For example: If you and Bob sent messages to each other, that is one conversation

// WHY DO WE NEED THIS QUERY?
// We want to show a list of all people the current user has talked to
// We need to find all users that the current user has sent messages to OR received messages from

// HOW THE SQL WORKS:
// 1. We use a subquery (the part in parentheses) to find all user_ids that the current user talked to
// 2. The subquery has two parts connected by UNION:
//    - First part: Find all receiver_id where I (current user) was the sender
//    - Second part: Find all sender_id where I (current user) was the receiver
// 3. UNION combines both lists and removes duplicates automatically
// 4. Then we get the user details (username, profile photo) from the users table
// 5. ORDER BY username ASC sorts the list alphabetically (A to Z)

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

// Prepare the SQL statement
// WHY? This protects us from SQL injection attacks
// Instead of putting variables directly into the SQL string, we use placeholders like :current_user_id
$conversationStatement = $connection->prepare($conversationSql);

// Bind the parameter
// This replaces :current_user_id in the SQL with the actual value from $currentUserId
// PDO::PARAM_INT tells the database this is an integer (whole number, not text)
$conversationStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);

// Execute the query
// This actually runs the SQL statement on the database
$conversationStatement->execute();

// Fetch all the results
// This gets all the rows that matched our query
// PDO::FETCH_ASSOC means each row is an array with column names as keys
// Example: $row['user_id'], $row['username'], $row['profile_photo_path']
$conversations = $conversationStatement->fetchAll(PDO::FETCH_ASSOC);

// ===========================
// STEP 5: GET LIST OF ALL USERS (FOR THE DROPDOWN)
// ===========================
// WHY DO WE NEED THIS?
// We want to show a dropdown where the user can select any other user to send a new message to
// We get ALL users from the database EXCEPT the current user (you cannot message yourself)

// THE SQL EXPLAINED:
// SELECT user_id, username = Get only the user_id and username columns
// FROM users = From the users table
// WHERE user_id != :current_user_id = Where the user_id is NOT equal to the current user's id
// ORDER BY username ASC = Sort by username alphabetically (A to Z)

$allUsersSql = "SELECT user_id, username FROM users WHERE user_id != :current_user_id ORDER BY username ASC";

// Prepare the SQL statement (protect against SQL injection)
$allUsersStatement = $connection->prepare($allUsersSql);

// Bind the parameter (replace :current_user_id with the actual value)
$allUsersStatement->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);

// Execute the query (run it on the database)
$allUsersStatement->execute();

// Fetch all users into an array
$allUsers = $allUsersStatement->fetchAll(PDO::FETCH_ASSOC);
// ===========================
// END OF PHP LOGIC - NOW WE SHOW THE HTML
// ===========================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- This meta tag makes the page work well on mobile phones -->
    <!-- width=device-width means use the phone's actual screen width -->
    <!-- initial-scale=1.0 means do not zoom in or out by default -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>My Messages - PlantBnB</title>
</head>
<body>

    <!-- MAIN CONTAINER -->
    <!-- container = Bootstrap class that centers content and adds padding on sides -->
    <!-- mt-4 = margin-top: 4 units (adds space at the top) -->
    <div class="container mt-4">
        
        <!-- PAGE TITLE -->
        <!-- mb-4 = margin-bottom: 4 units (adds space below the heading) -->
        <h1 class="mb-4">My Messages</h1>

        <!-- ============================= -->
        <!-- SECTION 1: NEW MESSAGE FORM -->
        <!-- ============================= -->
        <!-- WHY? This section lets the user send a new message to any other user -->
        
        <!-- card = Bootstrap class that creates a box with border and shadow -->
        <!-- mb-4 = margin-bottom: 4 units (adds space below the card) -->
        <div class="card mb-4">
            
            <!-- CARD HEADER (the title bar at the top) -->
            <div class="card-header">
                <!-- mb-0 = margin-bottom: 0 (no extra space, keeps it compact) -->
                <h5 class="mb-0">Send New Message</h5>
            </div>
            
            <!-- CARD BODY (the main content area) -->
            <div class="card-body">
                
                <!-- FORM -->
                <!-- action="message-send.php" = When submitted, send data to message-send.php -->
                <!-- method="POST" = Send data securely (not visible in the URL) -->
                <form action="message-send.php" method="POST">
                    
                    <!-- INPUT GROUP 1: DROPDOWN TO SELECT RECIPIENT -->
                    <!-- mb-3 = margin-bottom: 3 units (space below this input for mobile touch-friendliness) -->
                    <div class="mb-3">
                        <!-- LABEL (text above the input) -->
                        <!-- form-label = Bootstrap class for form labels -->
                        <label for="recipient" class="form-label">Send message to:</label>
                        
                        <!-- DROPDOWN SELECT -->
                        <!-- name="recipient_id" = This is the name of the data when sent to message-send.php -->
                        <!-- id="recipient" = Connects this input to the label above -->
                        <!-- form-select = Bootstrap class that styles the dropdown nicely -->
                        <!-- required = User MUST select someone before submitting -->
                        <select name="recipient_id" id="recipient" class="form-select" required>
                            <!-- First option is a placeholder -->
                            <option value="">-- Choose a user --</option>
                            
                            <?php
                            // Loop through all users and create an <option> for each one
                            // This builds the dropdown list dynamically
                            foreach ($allUsers as $user) {
                                // Start the option tag
                                // htmlspecialchars() protects us from XSS (Cross-Site Scripting) attacks
                                // It converts dangerous characters like < > into safe HTML entities
                                echo '<option value="' . htmlspecialchars($user['user_id']) . '">';
                                
                                // Show the username (also protected with htmlspecialchars)
                                echo htmlspecialchars($user['username']);
                                
                                // Close the option tag
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- INPUT GROUP 2: TEXT AREA FOR MESSAGE -->
                    <!-- mb-3 = margin-bottom: 3 units (space below this input) -->
                    <div class="mb-3">
                        <!-- LABEL -->
                        <label for="message_text" class="form-label">Your message:</label>
                        
                        <!-- TEXT AREA (multi-line text input) -->
                        <!-- name="message_text" = This is the name of the data when sent to message-send.php -->
                        <!-- id="message_text" = Connects this input to the label above -->
                        <!-- form-control = Bootstrap class that styles the text area nicely -->
                        <!-- rows="4" = Make the text area 4 lines tall -->
                        <!-- required = User MUST type something before submitting -->
                        <textarea name="message_text" id="message_text" class="form-control" rows="4" required></textarea>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <!-- d-grid = Makes button full width (good for mobile) -->
                    <!-- gap-2 = If there were multiple buttons, this adds space between them -->
                    <div class="d-grid gap-2">
                        <!-- type="submit" = When clicked, submit the form -->
                        <!-- btn = Bootstrap button class -->
                        <!-- btn-primary = Bootstrap class for blue button (primary action) -->
                        <button type="submit" class="btn btn-success">Send Message</button>
                    </div>
                    
                </form>
            </div>
        </div>

        <!-- ============================= -->
        <!-- SECTION 2: EXISTING CONVERSATIONS -->
        <!-- ============================= -->
        <!-- WHY? This section shows all the people the user has already messaged with -->
        <!-- Each person is a clickable link that opens the full conversation -->
        
        <div class="card">
            
            <!-- CARD HEADER -->
            <div class="card-header">
                <h5 class="mb-0">Your Conversations</h5>
            </div>
            
            <!-- CARD BODY -->
            <div class="card-body">
                <?php
                // CHECK: Does the user have any conversations?
                // count($conversations) tells us how many conversations exist
                if (count($conversations) === 0) {
                    
                    // NO CONVERSATIONS FOUND
                    // Show a message telling the user they have no messages yet
                    // text-muted = Bootstrap class for gray text (less important text)
                    echo '<p class="text-muted">You have no messages yet. Send a message above to start a conversation.</p>';
                    
                } else {
                    
                    // CONVERSATIONS FOUND
                    // Show them as a list
                    // list-group = Bootstrap class that creates a nice vertical list
                    echo '<div class="list-group">';
                    
                    // Loop through each conversation (each person we've talked to)
                    foreach ($conversations as $conversation) {
                        
                        // Get the other user's ID
                        // This is the ID of the person we are talking to
                        $otherUserId = $conversation['user_id'];
                        
                        // Get the other user's username
                        // htmlspecialchars() protects against XSS attacks
                        $otherUsername = htmlspecialchars($conversation['username']);
                        
                        // CREATE A CLICKABLE LINK
                        // When clicked, go to message-conversation.php and pass the other user's ID
                        // list-group-item = Bootstrap class for items in a list-group
                        // list-group-item-action = Bootstrap class that makes the item hoverable/clickable
                        echo '<a href="message-conversation.php?user_id=' . htmlspecialchars($otherUserId) . '" class="list-group-item list-group-item-action">';
                        
                        // SHOW THE USERNAME
                        echo '<strong>' . $otherUsername . '</strong>';
                        
                        echo '</a>'; // Close link
                    }
                    
                    echo '</div>'; // Close list-group
                }
                ?>
            </div>
        </div>

    </div>
    <!-- End of container -->

</body>
</html>