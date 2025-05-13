<?php 
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php');

// Get current user data
$currentUserId = $_SESSION['admin'];
$stmt = $connection->prepare("SELECT first_name, last_name, profile_image FROM students WHERE student_id = ?");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
           margin-left: 10rem; 
        }
        .profile-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #0d6efd;
        }
        .section-title {
            margin-top: 2rem;
            font-weight: 600;
        }
        .task-item {
            list-style: none;
            padding: 5px 0;
        }
        .task-item i {
            margin-right: 10px;
        }
    </style>
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

<div class="container">
    <div class="profile-card text-center">
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="student_id" value="<?= $currentUserId ?>">
            <div class="mb-3">
                <img src="profiles/<?= $currentUser['profile_image'] ?? 'default.jpg' ?>" class="profile-img mb-2" alt="Profile Image">
                <input class="form-control mt-2" type="file" name="profile_image">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <div class="profile-card">
        <h5 class="section-title">Achievements</h5>
        <ul>
            <li>🏆 Completed Ethics Certification</li>
            <li>📚 Top Performer in Medical Ethics</li>
            <li>👨‍🏫 Participated in 5 Workshops</li>
        </ul>
    </div>

    <div class="profile-card">
        <h5 class="section-title">Tasks To Do</h5>
        <ul>
            <li class="task-item"><i class="bi bi-square"></i> Submit quarterly report</li>
            <li class="task-item"><i class="bi bi-square"></i> Update user training list</li>
            <li class="task-item"><i class="bi bi-square"></i> Schedule mentoring session</li>
        </ul>
    </div>
</div>
<script src="animate.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
