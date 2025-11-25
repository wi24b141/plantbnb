<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\includes\header.php
// Only start the session if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
// $_SESSION['loggedIn'] is set to true in login.php after successful login
$isLoggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

// Get the username if the user is logged in
// We use the null coalescing operator (??) to provide a default empty string if username is not set
$username = $_SESSION['username'] ?? '';
?>

<!-- Navigation Bar -->
<!-- This navbar appears on every page to show login status -->
<!-- Sticky-top keeps the navbar visible at the top even when scrolling -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <!-- Container for responsive padding on all sides -->
    <div class="container-fluid">
        <!-- Brand/Logo on the left side of the navbar -->
        <!-- d-flex = use flexbox layout -->
        <!-- align-items-center = vertically align the logo -->
        <a class="navbar-brand d-flex align-items-center" href="listings.php">
          <span>plantbnb🪴</span>
        </a>

        <!-- Navbar Toggle Button (appears only on mobile screens) -->
        <!-- This button lets users collapse/expand the menu on small screens -->
        <!-- data-bs-toggle="collapse" = Bootstrap collapse functionality -->
        <!-- data-bs-target="#navbarNav" = targets the element to toggle (id="navbarNav" below) -->
        <button 
            class="navbar-toggler" 
            type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" 
            aria-controls="navbarNav" 
            aria-expanded="false" 
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu Items -->
        <!-- id="navbarNav" = referenced by the toggle button above -->
        <!-- collapse navbar-collapse = collapses on mobile, expands on desktop -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left side navigation links -->
            <!-- ms-auto = margin-start: auto pushes items to the right -->
            <ul class="navbar-nav ms-auto">
                <!-- Link to Listings page (always visible) -->
                <li class="nav-item">
                    <a class="nav-link" href="listings.php">Browse Listings</a>
                </li>

                <!-- Link to dashboard (always visible) -->
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>

                <!-- Link to listing-creator (always visible) -->
                <li class="nav-item">
                    <a class="nav-link" href="listing-creator.php">Create Listing</a>
                </li>

                <?php
                    // Check if the user is logged in
                    if ($isLoggedIn) {
                        // User is logged in, so show their username and logout link
                ?>
                    <!-- Display the logged-in username -->
                    <!-- nav-link disabled = looks like a link but is not clickable -->
                    <!-- text-success = green text color to highlight logged-in status -->
                    <li class="nav-item">
                        <span class="nav-link text-success">
                            <strong>👤 <?php echo htmlspecialchars($username); ?></strong>
                        </span>
                    </li>

                    <!-- Logout link -->
                    <!-- This link takes the user to logout.php which clears the session -->
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>

                <?php
                    } else {
                        // User is NOT logged in, so show Login and Register links
                ?>
                    <!-- Login link -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>

                    <!-- Register link -->
                    <li class="nav-item">
                        <a class="nav-link" href="registration.php">Register</a>
                    </li>

                <?php
                    }
                ?>
            </ul>
        </div>
    </div>
</nav>