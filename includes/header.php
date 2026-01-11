<?php
// ============================================================
// STEP 1: Start the session
// ============================================================
 
// Check if a session is already started
// session_status() tells the current state of sessions
// PHP_SESSION_NONE means no session exists yet
if (session_status() === PHP_SESSION_NONE) {
    // Start a new session
    // This allows to use $_SESSION to store data across pages
    session_start();
}

// ============================================================
// STEP 2: Check if user is logged in via SESSION
// ============================================================

// $_SESSION['loggedIn'] is set to true in login.php after successful login
// We check BOTH that it exists nd that it equals true
$isLoggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

// ============================================================
// STEP 3: If not logged in, check for "Remember Me" cookie
// ============================================================

// If the user is NOT logged in via session:
if (!$isLoggedIn) {
    // Check if a "remember_token" cookie exists
    // Cookies are stored in the $_COOKIE array
    if (isset($_COOKIE['remember_token'])) {
        // The cookie exists: This means the user chose "Remember Me" before
        // Get the token value from the cookie
        $rememberToken = $_COOKIE['remember_token'];
        
        // Load the database connection
        // look up the user by their token:
        require_once __DIR__ . '/db.php';
        
        // Look up the user in the database by their remember token
        // SELECT the user_id and username for the user with this token
        $query = "SELECT user_id, username FROM users WHERE remember_token = :token";
        
        // Prepare the query
        $statement = $connection->prepare($query);
        
        // Bind the token parameter
        $statement->bindParam(':token', $rememberToken, PDO::PARAM_STR);
        
        // Execute the query
        $statement->execute();
        
        // Fetch the user data
        // If a user with this token exists, $user will be an array
        // If not, $user will be false
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Check if we found a user
        if ($user) {
            // The token is valid and matches a user
            
            // Log the user in by setting session variables
            // This is the same as what we do in login.php
            $_SESSION["loggedIn"] = true;
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];
            
            // Update the $isLoggedIn variable
            $isLoggedIn = true;

        } else {
            // The token is invalid or expired
            // Delete the cookie so the browser stops sending it
            // We set the expiration time to the past (time() - 3600 = 1 hour ago)
            // This tells the browser to delete the cookie
            setcookie("remember_token", "", time() - 3600, "/");
        }
    }
}

// ============================================================
// STEP 4: Get username and current page
// ============================================================

// Get the username from the session
// If not logged in, $username will be an empty string
$username = $_SESSION['username'] ?? '';

// Get the current page filename
// basename() extracts  the filename from the full path
// Example: "/plantbnb/users/login.php" becomes "login.php"
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="" crossorigin="anonymous">
<link rel="stylesheet" href="resources/css/style.css">

<!-- Navigation Bar -->
<style>
.navbar-custom {
    background: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.navbar-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 1rem;
}
.navbar-brand {
    font-size: 1.3rem;
    text-decoration: none;
    color: #333;
}
.menu-toggle {
    display: none;
}
.menu-icon {
    display: none;
    font-size: 2rem;
    cursor: pointer;
}
.navbar-menu {
    display: flex;
    gap: 1rem;
    list-style: none;
}
.navbar-menu .nav-link {
    text-decoration: none;
    color: #333;
    padding: 0.5rem 0.8rem;
    border-radius: 4px;
}
.navbar-menu .nav-link:hover {
    background: #e2e6ea;
}
.navbar-menu .nav-link.active {
    background: #b8e0c6;
    color: #155724;
    font-weight: bold;
}

@media (max-width: 991px) {
    .menu-icon {
        display: block;
    }
    .navbar-menu {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #f8f9fa;
        position: absolute;
        top: 56px;
        left: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .menu-toggle:checked + .menu-icon + .navbar-menu {
        display: flex;
    }
}
</style>

<nav class="navbar-custom">
    <div class="navbar-container">
        <a class="navbar-brand" href="/plantbnb/index.php">plantbnb🪴</a>
        <!-- Toggle checkbox and icon -->
        <input type="checkbox" id="menu-toggle" class="menu-toggle" />
        <label for="menu-toggle" class="menu-icon">&#9776;</label>
         <!-- Navigation links -->
        <ul class="navbar-menu">
            <li><a class="nav-link <?php if ($currentPage == 'index.php') echo ' active'; ?>" href="/plantbnb/index.php">Home</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'help.php') echo ' active'; ?>" href="/plantbnb/help.php">Help</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'listings.php') echo ' active'; ?>" href="/plantbnb/listings/listings.php">Browse Listings</a></li>
            <?php if ($isLoggedIn) { ?>
            <li><a class="nav-link <?php if ($currentPage == 'my-listings.php') echo ' active'; ?>" href="/plantbnb/listings/my-listings.php">My Listings</a></li>
            <?php } ?>
            <li><a class="nav-link <?php if ($currentPage == 'listing-creator.php') echo ' active'; ?>" href="/plantbnb/listings/listing-creator.php">Create Listing</a></li>
           
            <?php if ($isLoggedIn) { ?>
                 <!-- Links visible ONLY when the user is logged in -->
                <li><a class="nav-link <?php if ($currentPage == 'favoritelistings.php') echo ' active'; ?>" href="/plantbnb/listings/favoritelistings.php">Favorites</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'rate-user.php') echo ' active'; ?>" href="/plantbnb/users/rate-user.php">Rating</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'messages.php') echo ' active'; ?>" href="/plantbnb/users/messages.php">Messages</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'dashboard.php') echo ' active'; ?>" href="/plantbnb/users/dashboard.php">Dashboard</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'admin-dashboard.php') echo ' active'; ?>" href="/plantbnb/admin/admin-dashboard.php">Admin-Dashboard</a></li>
                <li>
                    <span class="nav-link text-success">
                        <strong>👤 <?php echo htmlspecialchars($username); ?></strong>
                    </span>
                </li>
                <li><a class="nav-link <?php if ($currentPage == 'logout.php') echo ' active'; ?>" href="/plantbnb/users/logout.php">Logout</a></li>
           
                <?php } else { ?>
                    <!-- Links visible ONLY when the user is NOT logged in -->
                <li><a class="nav-link <?php if ($currentPage == 'login.php') echo ' active'; ?>" href="/plantbnb/users/login.php">Login</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'registration.php') echo ' active'; ?>" href="/plantbnb/users/registration.php">Register</a></li>
            <?php } ?>
        </ul>
    </div>
</nav>