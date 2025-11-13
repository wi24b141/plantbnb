<?php
// This command is always necessary when dealing with sessions
// starts or RESUMES the session
session_start();

$adminUser="admin";
$adminPassword="admin123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $adminUser="admin";
    $adminPassword='$2y$10$u/zz7qWZplIswzdhUeR59OcN7lgri0tjW0PsBsCwhlHIuu9H2tMsS';


    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === $adminUser && password_verify($password, $adminPassword))
        $_SESSION["loggedIn"]=true;
        // Redirect to other page e.g. admin.php
        header("Location: admin.php");
        exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head-includes.php'; ?>
<body>
    <header class="container text-center my-4">
        <h1 class="site-brand" id="site-title">
            <span class="brand-text">&#x1FAB4;plantbnb</span>
        </h1>
    </header>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                <h2 class="mb-3 text-center">Login</h2>

                <form action="login.php" method="post" class="card p-4 shadow-sm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Login</button>
                    </div>
                </form>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
