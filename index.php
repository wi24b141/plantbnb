<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\index.php

// ============================================
// HOMEPAGE - PHP LOGIC (TOP)
// ============================================

// Start the session to check if user is logged in
// We need this to show the correct navigation links in the header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>plantbnb🪴- Welcome</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         HOMEPAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-5">
        <!-- Hero Section -->
        <!-- col-12 = full width on mobile (vertical phone screen) -->
        <!-- col-md-8 = 2/3 width on desktop (centered with offset) -->
        <!-- offset-md-2 = centers the content on desktop by adding left margin -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2 text-center">
                <h1 class="display-4 mb-4">Welcome to plantbnb🪴!</h1>
                <p class="lead text-muted">
                    Find trusted plant sitters in your area or offer your green thumb services to fellow plant lovers.
                </p>
            </div>
        </div>

        <!-- Call-to-Action Buttons -->
        <!-- These buttons are full-width on mobile, side-by-side on desktop -->
        <!-- d-grid = makes buttons full width on mobile (touch-friendly) -->
        <!-- gap-2 = adds spacing between stacked buttons on mobile -->
        <!-- d-md-flex = switches to flexbox side-by-side layout on desktop -->
        <!-- justify-content-md-center = centers the buttons horizontally on desktop -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <!-- Browse Listings Button -->
                    <!-- btn-lg = larger button for easy touch on mobile -->
                    <a href="listings.php" class="btn btn-success btn-lg">
                        Browse Listings
                    </a>
                    
                    <!-- Create Listing Button -->
                    <!-- btn-outline-success = green outline (secondary action) -->
                    <a href="listing-creator.php" class="btn btn-outline-success btn-lg">
                        Create a Listing
                    </a>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <!-- row-cols-1 = 1 column on mobile (stacked vertically) -->
        <!-- row-cols-md-3 = 3 columns on desktop (side-by-side) -->
        <!-- g-4 = gap of 1.5rem between columns for touch-friendly spacing -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <!-- Feature 1: Find Sitters -->
            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body">
                        <h2 class="card-title">🔍 Find Sitters</h2>
                        <p class="card-text">
                            Browse verified plant sitters in your area. Read reviews and find the perfect match for your plants.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature 2: Offer Services -->
            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body">
                        <h2 class="card-title">🌿 Offer Services</h2>
                        <p class="card-text">
                            Have a green thumb? Create listings to offer your plant-sitting services to your community.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Feature 3: Build Trust -->
            <div class="col">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body">
                        <h2 class="card-title">✅ Build Trust</h2>
                        <p class="card-text">
                            Get verified, earn reviews, and build a reputation as a trusted plant care expert in your area.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                <h2 class="text-center mb-4">How It Works</h2>
                
                <!-- Step 1 -->
                <!-- mb-3 = margin-bottom for spacing between cards (touch-friendly) -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title">1. Sign Up or Login</h5>
                        <p class="card-text mb-0">
                            Create a free account to access all features or login if you already have one.
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title">2. Browse or Create Listings</h5>
                        <p class="card-text mb-0">
                            Search for plant sitters in your area, or create a listing to offer your services.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title">3. Connect and Care</h5>
                        <p class="card-text mb-0">
                            Apply for listings, review care sheets, and keep those plants thriving!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the site footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>