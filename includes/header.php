<?php
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    // session.cookie_secure must be set to 1 in production with HTTPS
    ini_set('session.cookie_secure', 1); // Requires HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', 0); // Session cookie expires when browser closes
    ini_set('session.gc_maxlifetime', 3600); // 1 hour session lifetime
    
    session_start();
    
    // Session timeout check (1 hour of inactivity)
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } else if (time() - $_SESSION['CREATED'] > 1800) {
        // Regenerate session every 30 minutes
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }
}

$isLoggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

if (!$isLoggedIn) {
    if (isset($_COOKIE['remember_token'])) {
        $rememberToken = $_COOKIE['remember_token'];

        require_once __DIR__ . '/db.php';
        
        $query = "SELECT user_id, username FROM users WHERE remember_token = :token";
        $statement = $connection->prepare($query);
        $statement->bindParam(':token', $rememberToken, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Regenerate session ID on authentication to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION["loggedIn"] = true;
            $_SESSION["user_id"] = $user['user_id'];
            $_SESSION["username"] = $user['username'];
            $_SESSION['CREATED'] = time();

            $isLoggedIn = true;

        } else {
            setcookie("remember_token", "", time() - 3600, "/");
        }
    }
}
$username = $_SESSION['username'] ?? '';
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
    max-width: 100%;
}
.navbar-brand {
    font-size: 1.3rem;
    text-decoration: none;
    color: #333;
    flex-shrink: 0;
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
    gap: 0.5rem;
    list-style: none;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.navbar-menu .nav-link {
    text-decoration: none;
    color: #333;
    padding: 0.5rem 0.6rem;
    border-radius: 4px;
    white-space: nowrap;
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
        padding: 0.5rem 0;
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
                 <!-- Links visible ONLY when the user is logged in -->
                <li><a class="nav-link <?php if ($currentPage == 'favoritelistings.php') echo ' active'; ?>" href="/plantbnb/listings/favoritelistings.php">Favorites</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'rate-user.php') echo ' active'; ?>" href="/plantbnb/users/rate-user.php">Rating</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'messages.php') echo ' active'; ?>" href="/plantbnb/users/messages.php">Messages</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'dashboard.php') echo ' active'; ?>" href="/plantbnb/users/dashboard.php">Dashboard</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'admin-dashboard.php') echo ' active'; ?>" href="/plantbnb/admin/admin-dashboard.php">Admin-Login</a></li>
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