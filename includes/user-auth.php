<?php
if (!isset($isLoggedIn) || $isLoggedIn === false) {
    header("Location: login.php");
    exit();
}
