<?php
session_start();

$allowed_pages = ['login.php', 'logout.php'];

$current_page = basename($_SERVER['PHP_SELF']);

// Check if the user is logged in at all
if (!isset($_SESSION['user_id']) && !in_array($current_page, $allowed_pages)) {
    header("Location: login.php");
    exit();
}

// Check for admin-only pages (you can define these as needed)
$admin_only_pages = ['admin_dashboard.php'];

// If accessing an admin-only page but not an admin, redirect to user dashboard
if (isset($_SESSION['user_id']) && !isset($_SESSION['is_admin']) && in_array($current_page, $admin_only_pages)) {
    header("Location: user_dashboard.php");
    exit();
}
?>