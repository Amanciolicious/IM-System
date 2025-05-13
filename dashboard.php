<?php 
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php');

// Get current user data
$currentUserId = $_SESSION['admin'];
$stmt = $connection->prepare("SELECT first_name, last_name, profile_image FROM students WHERE student_id = ?");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all students
$students = $connection->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="hamburger-toggle" id="sidebarToggle">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="bi bi-heart-pulse"></i>
            <span>HealthAdmin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="sidebar-link active">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="dashboard.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
            <a href="admin_assign_tasks.php" class="sidebar-link">
                <i class="bi bi-list-task"></i>
                <span>Task</span>
            </a>
            <a href="profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </nav>
        <div class="sidebar-user-panel">
            <img src="profiles/<?= $currentUser['profile_image'] ?? 'default.jpg' ?>" class="sidebar-user-avatar">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                <div class="sidebar-user-role">Admin</div>
            </div>
            <button class="sidebar-logout-btn" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>

   
    <!-- Main Content -->
    <div class="main-content">
    <div class="page-title-d">Dashboard</div>
        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="bi bi-person-check stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-count" id="verifiedUsers">0</div>
                    <div class="stat-label">Verified Users</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-people stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-count" id="activeSessions">0</div>
                    <div class="stat-label">Active Sessions</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="bi bi-heart-pulse stat-icon"></i>
                <div class="stat-content">
                    <div class="stat-count" id="systemHealth">100%</div>
                    <div class="stat-label">System Health</div>
                </div>
            </div>
        </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animate.js"></script>
</body>
</html>