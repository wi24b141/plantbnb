<?php 
    require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>plantbnb🪴- Welcome</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2 text-center">
                <h1 class="display-4 mb-4">Welcome to plantbnb🪴!</h1>
                <p class="lead text-muted">
                    Find trusted plant sitters in your area or offer your green thumb services to fellow plant lovers.
                </p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12 col-md-8 offset-md-2">
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <!-- Browse Listings Button -->
                    <a href="/plantbnb/listings/listings.php" class="btn btn-success btn-lg">
                        Browse Listings
                    </a>
                    
                    <!-- Create Listing Button -->
                    <a href="/plantbnb/listings/listing-creator.php" class="btn btn-outline-success btn-lg">
                        Create a Listing
                    </a>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
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
                
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title">1. Sign Up or Login</h5>
                        <p class="card-text mb-0">
                            Create a free account to access all features or login if you already have one.
                        </p>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title">2. Browse or Create Listings</h5>
                        <p class="card-text mb-0">
                            Search for plant sitters in your area, or create a listing to offer your services.
                        </p>
                    </div>
                </div>

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
</body>
</html>