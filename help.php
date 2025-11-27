<?php
// filepath: c:\xampp\htdocs\plantbnb\plantbnb\help.php

// ============================================
// HELP PAGE - PHP LOGIC (TOP)
// ============================================

// Start the session to check if user is logged in
// We need this to show the correct navigation links
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - PlantBnB</title>
    <?php require_once 'includes/head-includes.php'; ?>
</head>
<body>
    <!-- ============================================
         HELP PAGE - HTML VIEW (BOTTOM)
         ============================================ -->

    <!-- Include the site header/navigation -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Page Header -->
        <!-- col-12 = full width on mobile -->
        <!-- col-md-8 = 2/3 width on desktop for better reading -->
        <!-- offset-md-2 = centers the content on desktop -->
        <div class="row mb-4">
            <div class="col-12 col-md-8 offset-md-2">
                <h1 class="text-center">Help Page</h1>
                <p class="text-center text-muted">Quick answers to common questions</p>
            </div>
        </div>

        <!-- FAQ Cards -->
        <!-- Each FAQ is a separate card for mobile-friendly touch targets -->
        <!-- col-12 = full width on all screens -->
        <!-- col-md-8 = narrower on desktop for better reading -->
        <!-- offset-md-2 = centers the cards on desktop -->
        <div class="row">
            <div class="col-12 col-md-8 offset-md-2">
                
                <!-- FAQ 1: How to create a listing -->
                <!-- mb-3 = margin-bottom for touch-friendly spacing -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">How do I create a listing?</h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>Go to your Dashboard</li>
                            <li>Click "Create New Listing"</li>
                            <li>Fill in plant details and dates</li>
                            <li>Upload a photo (optional)</li>
                            <li>Click "Create Listing"</li>
                        </ol>
                    </div>
                </div>

                <!-- FAQ 2: How to get verified -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">How do I get verified?</h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>Go to your Dashboard</li>
                            <li>Click "Get Verified"</li>
                            <li>Upload a government-issued ID</li>
                            <li>Wait for admin approval (1-2 days)</li>
                        </ol>
                    </div>
                </div>

                <!-- FAQ 3: How to edit profile -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">How do I edit my profile?</h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>Go to your Dashboard</li>
                            <li>Click "Edit Profile"</li>
                            <li>Update your bio and photo</li>
                            <li>Click "Save Changes"</li>
                        </ol>
                    </div>
                </div>

                <!-- FAQ 4: Contact support -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Need more help?</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Contact us at: <strong>support@plantbnb.com</strong></p>
                    </div>
                </div>

                <!-- Back to Home Button -->
                <!-- d-grid = full width button on mobile -->
                <!-- gap-2 = adds spacing inside the button area -->
                <!-- mt-4 = margin-top for spacing from cards above -->
                <div class="d-grid gap-2 mt-4">
                    <a href="listings.php" class="btn btn-outline-secondary">
                        ← Back to Listings
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Include the site footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>