<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/user-auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Listing - Choose Type</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12 col-md-8 offset-md-2 text-center">
                <h1 class="mb-3">Create a Listing</h1>
                <p class="text-muted">Choose whether you want to offer plant care or need plant care.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h4>Offer</h4>
                        <p class="text-muted">I can provide plant care services.</p>
                        <div class="mt-auto d-grid">
                            <a href="/plantbnb/listings/listing-creator_offer.php" class="btn btn-success">Create Offer</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h4>Need</h4>
                        <p class="text-muted">I need someone to take care of my plants.</p>
                        <div class="mt-auto d-grid">
                            <a href="/plantbnb/listings/listing-creator_need.php" class="btn btn-success">Create Need</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>