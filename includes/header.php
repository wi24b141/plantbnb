<?php
// NOTE: Session management - checks if session is already active to prevent errors
// Ensures session_start() is only called once per request lifecycle
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determines authentication state from session variable
// This controls which navigation menu items are displayed to the user
$isLoggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

// NOTE: Persistent login implementation - "Remember Me" functionality
// If user is not logged in via session, check for a valid remember token cookie
if (!$isLoggedIn) {
    if (isset($_COOKIE['remember_token'])) {
        // Retrieve the remember token from the cookie
        $rememberToken = $_COOKIE['remember_token'];
        
        // Include database connection using PDO
        require_once __DIR__ . '/db.php';
        
        // NOTE: SQL Injection Protection - using PDO prepared statements with parameterized queries
        // The :token placeholder prevents malicious SQL code from being injected via cookie manipulation
        $query = "SELECT user_id, username FROM users WHERE remember_token = :token";
        
        // Prepare the statement to protect against SQL injection
        $statement = $connection->prepare($query);
        
        // Bind the remember token parameter as a string type
        $statement->bindParam(':token', $rememberToken, PDO::PARAM_STR);
        
        // Execute the prepared statement safely
        $statement->execute();
        
        // Fetch user data as associative array
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        // NOTE: Session restoration from persistent cookie
        // If valid token found, restore the user's session without requiring login
        if ($user) {
            // Restore session variables to maintain authentication state
            $_SESSION["loggedIn"] = true;
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];
            
            // Update login status flag for navbar rendering
            $isLoggedIn = true;

        } else {
            // Invalid or expired token - delete the cookie by setting expiration to past
            // NOTE: Cookie deletion for security - prevents reuse of invalid tokens
            setcookie("remember_token", "", time() - 3600, "/");
        }
    }
}

// Retrieve username from session for display, defaulting to empty string if not set
// The null coalescing operator (??) provides fallback for non-authenticated users
$username = $_SESSION['username'] ?? '';

// Extract current page filename to highlight active navigation link
// basename() strips the directory path, leaving only the filename
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- ========== STYLESHEETS ========== -->
<!-- Bootstrap 5.3.2 CDN for responsive layout and utility classes -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="" crossorigin="anonymous">
<!-- Custom application styles -->
<link rel="stylesheet" href="resources/css/style.css">

<!-- ========== CUSTOM NAVIGATION STYLES ========== -->
<!-- NOTE: Responsive navbar implementation using CSS flexbox and media queries -->
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

<!-- ========== NAVIGATION BAR ========== -->
<!-- NOTE: Accessibility consideration - uses semantic <nav> element for screen readers -->
<nav class="navbar-custom">
    <div class="navbar-container">
        <!-- Brand/logo with plant emoji for visual identity -->
        <a class="navbar-brand" href="/plantbnb/index.php">plantbnb🪴</a>
        <!-- Hidden checkbox for mobile menu toggle (no JavaScript required) -->
        <input type="checkbox" id="menu-toggle" class="menu-toggle" />
        <!-- Hamburger icon (&#9776; is the HTML entity for ☰) -->
        <label for="menu-toggle" class="menu-icon">&#9776;</label>
        <!-- Main navigation menu -->
        <ul class="navbar-menu">
            <!-- Public navigation links - visible to all users -->
            <!-- NOTE: Dynamic 'active' class highlights current page for better UX -->
            <li><a class="nav-link <?php if ($currentPage == 'index.php') echo ' active'; ?>" href="/plantbnb/index.php">Home</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'help.php') echo ' active'; ?>" href="/plantbnb/help.php">Help</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'listings.php') echo ' active'; ?>" href="/plantbnb/listings/listings.php">Browse Listings</a></li>
            <!-- Authenticated user navigation - listing management -->
            <!-- NOTE: Authorization control - menu items conditionally rendered based on login state -->
            <?php if ($isLoggedIn) { ?>
                <li><a class="nav-link <?php if ($currentPage == 'my-listings.php') echo ' active'; ?>" href="/plantbnb/listings/my-listings.php">My Listings</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'listing-creator.php') echo ' active'; ?>" href="/plantbnb/listings/listing-creator.php">Create Listing</a></li>
            <?php } else { ?>
            <?php } ?>
           
            <!-- Authenticated user navigation - user features -->
            <?php if ($isLoggedIn) { ?>
                <!-- User feature links - favorites, ratings, messaging -->
                <li><a class="nav-link <?php if ($currentPage == 'favoritelistings.php') echo ' active'; ?>" href="/plantbnb/listings/favoritelistings.php">Favorites</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'rate-user.php') echo ' active'; ?>" href="/plantbnb/users/rate-user.php">Rating</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'messages.php') echo ' active'; ?>" href="/plantbnb/users/messages.php">Messages</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'dashboard.php') echo ' active'; ?>" href="/plantbnb/users/dashboard.php">Dashboard</a></li>
                <!-- Admin dashboard link - should have additional role-based access control -->
                <li><a class="nav-link <?php if ($currentPage == 'admin-dashboard.php') echo ' active'; ?>" href="/plantbnb/admin/admin-dashboard.php">Admin-Dashboard</a></li>
                <!-- Username display with XSS protection -->
                <!-- NOTE: htmlspecialchars() prevents Cross-Site Scripting (XSS) attacks -->
                <!-- Converts special characters (<, >, &, etc.) to HTML entities -->
                <li>
                    <span class="nav-link text-success">
                        <strong>👤 <?php echo htmlspecialchars($username); ?></strong>
                    </span>
                </li>
                <!-- Logout link to terminate session -->
                <li><a class="nav-link <?php if ($currentPage == 'logout.php') echo ' active'; ?>" href="/plantbnb/users/logout.php">Logout</a></li>
           
                <?php } else { ?>
                <!-- Guest user navigation - authentication links -->
                <!-- Shown only when user is NOT logged in -->
                <li><a class="nav-link <?php if ($currentPage == 'login.php') echo ' active'; ?>" href="/plantbnb/users/login.php">Login</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'registration.php') echo ' active'; ?>" href="/plantbnb/users/registration.php">Register</a></li>
            <?php } ?>
        </ul><!-- End navbar-menu -->
    </div><!-- End navbar-container -->
</nav>
<!-- ========== END NAVIGATION BAR ========== -->