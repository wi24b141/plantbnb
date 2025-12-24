<?php
require_once __DIR__ . '/../includes/header.php';

session_unset();
session_destroy();

header("Location: login.php");

exit();
