<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $_SESSION['loggedIn'] is set to true in login.php after successful login
$isLoggedIn = $_SESSION['loggedIn'] === true;

$username = $_SESSION['username'] ?? '';
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <!-- Container for responsive padding on all sides -->
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <span>plantbnb🪴</span>
        </a>

        <!-- Navbar Menu Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link" href="help.php">Help</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="listings.php">Browse Listings</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="listing-creator.php">Create Listing</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="favoritelistings.php">Favorites</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>


                <?php
                    if ($isLoggedIn) {
                        // User is logged in, show username and Logout link
                ?>
                    <li class="nav-item">
                        <span class="nav-link text-success">
                            <strong>👤 <?php echo htmlspecialchars($username); ?></strong>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>

                <?php
                    } else {
                        // User is NOT logged in, so show Login and Register links
                ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>

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