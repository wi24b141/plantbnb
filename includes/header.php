<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $_SESSION['loggedIn'] is set to true in login.php after successful login
$isLoggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

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
        <ul class="navbar-menu">
            <li><a class="nav-link <?php if ($currentPage == 'index.php') echo ' active'; ?>" href="/plantbnb/index.php">Home</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'help.php') echo ' active'; ?>" href="/plantbnb/help.php">Help</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'listings.php') echo ' active'; ?>" href="/plantbnb/listings/listings.php">Browse Listings</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'listing-creator.php') echo ' active'; ?>" href="/plantbnb/listings/listing-creator.php">Create Listing</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'favoritelistings.php') echo ' active'; ?>" href="/plantbnb/listings/favoritelistings.php">Favorites</a></li>
            <li><a class="nav-link <?php if ($currentPage == 'dashboard.php') echo ' active'; ?>" href="/plantbnb/users/dashboard.php">Dashboard</a></li>
            <?php if ($isLoggedIn) { ?>
                <li>
                    <span class="nav-link text-success">
                        <strong>👤 <?php echo htmlspecialchars($username); ?></strong>
                    </span>
                </li>
                <li><a class="nav-link <?php if ($currentPage == 'logout.php') echo ' active'; ?>" href="/plantbnb/users/logout.php">Logout</a></li>
            <?php } else { ?>
                <li><a class="nav-link <?php if ($currentPage == 'login.php') echo ' active'; ?>" href="/plantbnb/users/login.php">Login</a></li>
                <li><a class="nav-link <?php if ($currentPage == 'registration.php') echo ' active'; ?>" href="/plantbnb/users/registration.php">Register</a></li>
            <?php } ?>
        </ul>
    </div>
</nav>