<?php 
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php');

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
    <title>Settings - HealthAdmin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css" class="rel">
    <style>
        body {
            margin-left: 10rem;
        }
        .settings-section {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }
        .form-switch .form-check-input {
            cursor: pointer;
        }
        .danger-zone {
            border: 1px solid #dc3545;
            background-color: #ffeaea;
        }
    </style>
</head>

<body class="bg-light">
    
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



<div class="container py-5">
    <h2 class="mb-4">Settings</h2>

    <!-- Account Info -->
    <div class="settings-section">
        <h4>Account Information</h4>
        <form action="update_account.php" method="POST">
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($currentUser['first_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($currentUser['last_name']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>
                <div class="col-12">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                </div>
            </div>
            <button class="btn btn-primary mt-3">Save Changes</button>
        </form>
    </div>

    <!-- Privacy Settings -->
    <div class="settings-section">
        <h4>Privacy Settings</h4>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="" id="profileVisible" checked>
            <label class="form-check-label" for="profileVisible">Make profile public</label>
        </div>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="" id="searchable">
            <label class="form-check-label" for="searchable">Allow search engines to index my profile</label>
        </div>
    </div>

    <!-- Notifications -->
    <div class="settings-section">
        <h4>Notifications</h4>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="emailNotify" checked>
            <label class="form-check-label" for="emailNotify">Email Notifications</label>
        </div>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="pushNotify">
            <label class="form-check-label" for="pushNotify">Push Notifications</label>
        </div>
    </div>

    <!-- Theme -->
    <div class="settings-section">
        <h4>Theme Preferences</h4>
        <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="darkMode">
            <label class="form-check-label" for="darkMode">Enable Dark Mode</label>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="settings-section danger-zone">
        <h4 class="text-danger">Danger Zone</h4>
        <p>Once you deactivate your account, there's no going back. Please be certain.</p>
        <button class="btn btn-outline-danger">Deactivate Account</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animate.js"></script>
</body>
</html>
