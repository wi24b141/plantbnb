<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/db.php';

// ===========================
// STEP 2: CHECK IF FORM WAS SUBMITTED
// ===========================
// This page should ONLY be accessed when someone submits a form
// $_SERVER is a special PHP array that contains information about the request
// $_SERVER['REQUEST_METHOD'] tells us HOW the page was accessed
// There are two main ways to access a page:
//   - GET: When you click a link or type a URL (normal browsing)
//   - POST: When you submit a form

// We check if the request method is NOT equal to POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Someone tried to access this page directly by typing the URL
    // We do not want that, so we redirect them back to messages page
    header("Location: /plantbnb/users/messages.php");
    exit(); // exit() stops the script immediately
}

// If we reach this line, it means the form WAS submitted via POST
// So we can continue processing the message

// ===========================
// STEP 3: GET THE RECIPIENT ID FROM FORM
// ===========================
// When a form is submitted with method="POST", all the form data
// is stored in a special array called $_POST
// The format is: $_POST['name_of_input_field']

// First, check if the recipient_id field exists
// isset() is a PHP function that returns true if a variable exists
if (isset($_POST['recipient_id']) === false) {
    // The recipient_id is missing from the form data
    // This should not happen in normal usage
    // Redirect back to messages page
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// Get the recipient_id from the form
// We use (int) to convert it to an integer
// This is a security measure to prevent SQL injection
$recipientId = (int)$_POST['recipient_id'];

// ===========================
// STEP 4: GET THE MESSAGE TEXT FROM FORM
// ===========================
// Now we need to get the actual message text that the user typed

// First, check if the message_text field exists
if (isset($_POST['message_text']) === false) {
    // The message_text is missing from the form data
    // Redirect back to messages page
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// Get the message text from the form
$messageText = $_POST['message_text'];

// Use trim() to remove extra spaces from beginning and end
// For example: "  Hello  " becomes "Hello"
$messageText = trim($messageText);

// ===========================
// STEP 5: VALIDATE THE MESSAGE
// ===========================
// Make sure the message is not empty
// If someone submits a blank message, we reject it

if ($messageText === '') {
    // The message is empty (just spaces or nothing)
    // Redirect back to messages page
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// ===========================
// STEP 6: GET THE SENDER ID
// ===========================
// We need to know WHO is sending the message
// The sender is the currently logged-in user
// Their user ID is stored in the session

// $_SESSION is a special array that stores data across multiple pages
// When the user logged in, we saved their user_id in the session
$senderId = $_SESSION['user_id'];

// ===========================
// STEP 7: SECURITY CHECK
// ===========================
// Make sure the user is not trying to send a message to themselves
// This would not make sense (why would you message yourself?)

if ($recipientId === $senderId) {
    // The recipient is the same as the sender
    // Redirect back to messages page
    header("Location: /plantbnb/users/messages.php");
    exit();
}

// ===========================
// STEP 8: PREPARE THE SQL QUERY
// ===========================
// Now we are ready to save the message to the database
// We need to insert a new row into the "messages" table

// This is our SQL INSERT query
// The :sender_id, :receiver_id, and :message_text are placeholders
// We will replace them with real values in the next step
// This is called a "prepared statement" and it protects from SQL injection
$insertSql = "
    INSERT INTO messages (sender_id, receiver_id, message_text, is_read, created_at)
    VALUES (:sender_id, :receiver_id, :message_text, 0, NOW())
";

// What each column means:
// - sender_id: The user ID of the person sending the message
// - receiver_id: The user ID of the person receiving the message
// - message_text: The actual message content
// - is_read: Whether the message has been read (0 = not read, 1 = read)
// - created_at: The timestamp when the message was sent (NOW() = current time)

// ===========================
// STEP 9: PREPARE THE STATEMENT
// ===========================
// $connection is the database connection object from db.php
// The prepare() method creates a prepared statement
// This protects us from SQL injection attacks
$insertStatement = $connection->prepare($insertSql);

// ===========================
// STEP 10: BIND THE PARAMETERS
// ===========================
// Now we replace the placeholders (:sender_id, etc.) with real values
// bindParam() connects a placeholder to a variable

// Bind the sender ID
// PDO::PARAM_INT means this value is an integer (whole number)
$insertStatement->bindParam(':sender_id', $senderId, PDO::PARAM_INT);

// Bind the receiver ID
$insertStatement->bindParam(':receiver_id', $recipientId, PDO::PARAM_INT);

// Bind the message text
// PDO::PARAM_STR means this value is a string (text)
$insertStatement->bindParam(':message_text', $messageText, PDO::PARAM_STR);

// ===========================
// STEP 11: EXECUTE THE QUERY
// ===========================
// The execute() method runs the SQL query
// This actually saves the message to the database
$insertStatement->execute();

// At this point, the message is successfully saved in the database

// ===========================
// STEP 12: REDIRECT TO CONVERSATION
// ===========================
// After sending the message, we redirect the user to the conversation page
// This way they can see their message and continue the conversation

// We use header() to send a redirect command to the browser
// We pass the recipient's user_id as a URL parameter
// For example: message-conversation.php?user_id=5
header("Location: /plantbnb/users/message-conversation.php?user_id=" . $recipientId);

// exit() stops the script here
// This is important after header() to prevent any more code from running
exit();

// ===========================
// NOTE: WHY IS THERE NO HTML?
// ===========================
// This file has no HTML because it is a "processing page"
// Its only job is to:
//   1. Receive form data
//   2. Validate the data
//   3. Save to database
//   4. Redirect to another page
// The user never actually "sees" this page
// They submit a form, and this page works behind the scenes
?>
