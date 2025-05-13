<?php
require 'admin/auth_check.php';
require 'dbconnection.php';

// Redirect admin users to their dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin_dashboard.php");
    exit();
}

// Get current user data
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get tasks assigned to this student
// Note: You'll need to adjust this query based on your actual database schema
$stmt = $connection->prepare("SELECT t.*, 
                         GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as assigned_names
                         FROM tasks t
                         LEFT JOIN task_assignments ta ON t.task_id = ta.task_id 
                         LEFT JOIN students s ON ta.student_id = s.student_id
                         WHERE ta.student_id = ?
                         GROUP BY t.task_id
                         ORDER BY t.deadline ASC
                         LIMIT 5");
$stmt->execute([$user_id]);
$recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending tasks count
$stmt = $connection->prepare("SELECT COUNT(*) FROM tasks t 
                         JOIN task_assignments ta ON t.task_id = ta.task_id
                         WHERE ta.student_id = ? AND t.status = 'Pending'");
$stmt->execute([$user_id]);
$pendingTasksCount = $stmt->fetchColumn();

// Get completed tasks count
$stmt = $connection->prepare("SELECT COUNT(*) FROM tasks t 
                         JOIN task_assignments ta ON t.task_id = ta.task_id
                         WHERE ta.student_id = ? AND t.status = 'Completed'");
$stmt->execute([$user_id]);
$completedTasksCount = $stmt->fetchColumn();

// Get overdue tasks
$stmt = $connection->prepare("SELECT COUNT(*) FROM tasks t 
                         JOIN task_assignments ta ON t.task_id = ta.task_id
                         WHERE ta.student_id = ? AND t.status = 'Pending' AND t.deadline < NOW()");
$stmt->execute([$user_id]);
$overdueTasksCount = $stmt->fetchColumn();

// Get recent announcements (placeholder - would need an announcements table)
// You may need to create this table or adjust based on your database schema
$announcements = []; // This would be populated from a database query
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            align-self: flex-end;
        }
        
        .stat-pending .stat-icon {
            color: #ffc107;
        }
        
        .stat-completed .stat-icon {
            color: #28a745;
        }
        
        .stat-overdue .stat-icon {
            color: #dc3545;
        }
        
        .dashboard-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .dashboard-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: #343a40;
            margin: 0;
        }
        
        .section-link {
            font-size: 0.9rem;
            color: #007bff;
            text-decoration: none;
        }
        
        .task-item {
            border-bottom: 1px solid #e9ecef;
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-details {
            flex: 1;
        }
        
        .task-title {
            font-weight: 500;
            color: #343a40;
            margin-bottom: 3px;
        }
        
        .task-deadline {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .task-deadline.overdue {
            color: #dc3545;
            font-weight: 500;
        }
        
        .task-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .task-status-pending {
            background-color: #fff0f0;
            color: #dc3545;
        }
        
        .task-status-completed {
            background-color: #f0fff0;
            color: #198754;
        }
        
        .welcome-banner {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text {
            flex: 1;
        }
        
        .welcome-text h2 {
            color: #343a40;
            margin-bottom: 10px;
        }
        
        .welcome-text p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .welcome-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .announcement-item {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .announcement-item:last-child {
            border-bottom: none;
        }
        
        .announcement-title {
            font-weight: 500;
            color: #343a40;
            margin-bottom: 5px;
        }
        
        .announcement-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .announcement-content {
            font-size: 0.9rem;
            color: #495057;
        }
        
        .no-data-message {
            color: #6c757d;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: #343a40;
            color: #fff;
            z-index: 1000;
            transition: all 0.3s ease;
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .sidebar-logo {
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar-link i {
            font-size: 1.2rem;
            width: 24px;
        }
        
        .sidebar-user-panel {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background-color: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .sidebar-user-info {
            flex: 1;
            overflow: hidden;
        }
        
        .sidebar-user-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-user-role {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .sidebar-logout-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-logout-btn:hover {
            color: #fff;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .hamburger-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background-color: #343a40;
            color: #fff;
            border: none;
            border-radius: 5px;
            width: 40px;
            height: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hamburger-line {
            width: 25px;
            height: 2px;
            background-color: #fff;
            transition: all 0.3s ease;
        }
        
        .main-content {
            margin-left: 0;
            padding: 70px 20px 20px;
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 250px;
        }
        
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .hamburger-toggle {
                display: none;
            }
        }
        
        @media (max-width: 991px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
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
            <span>Student System</span>
        </div>
        <nav class="sidebar-nav">
            <a href="user_dashboard.php" class="sidebar-link active">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="student_tasks.php" class="sidebar-link">
                <i class="bi bi-list-task"></i>
                <span>My Tasks</span>
            </a>
            <a href="user_profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="user_settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </nav>
        <div class="sidebar-user-panel">
            <img src="profiles/<?= $user['profile_image'] ?? 'default.jpg' ?>" class="sidebar-user-avatar" alt="User avatar">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                <div class="sidebar-user-role">Student</div>
            </div>
            <button class="sidebar-logout-btn" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, <?= htmlspecialchars($user['first_name']) ?>!</h2>
                <p>Here's an overview of your tasks and activities</p>
            </div>
            <img src="profiles/<?= $user['profile_image'] ?? 'default.jpg' ?>" class="welcome-image" alt="Profile">
        </div>
        
        <!-- Stats Cards -->
        <div class="dashboard-stats">
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-title">Pending Tasks</div>
                <div class="stat-value"><?= $pendingTasksCount ?></div>
                <div class="stat-link">
                    <a href="student_tasks.php?filter=pending" class="text-decoration-none">View all pending tasks</a>
                </div>
            </div>
            
            <div class="stat-card stat-completed">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-title">Completed Tasks</div>
                <div class="stat-value"><?= $completedTasksCount ?></div>
                <div class="stat-link">
                    <a href="student_tasks.php?filter=completed" class="text-decoration-none">View completed tasks</a>
                </div>
            </div>
            
            <div class="stat-card stat-overdue">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-title">Overdue Tasks</div>
                <div class="stat-value"><?= $overdueTasksCount ?></div>
                <div class="stat-link">
                    <a href="student_tasks.php?filter=overdue" class="text-decoration-none">View overdue tasks</a>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Sections -->
        <div class="dashboard-sections">
            <!-- Recent Tasks Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Tasks</h3>
                    <a href="student_tasks.php" class="section-link">View All</a>
                </div>
                
                <?php if (empty($recentTasks)): ?>
                <div class="no-data-message">
                    <p>You don't have any assigned tasks yet.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($recentTasks as $task): 
                        $isPending = $task['status'] === 'Pending';
                        $isOverdue = $isPending && strtotime($task['deadline']) < time();
                    ?>
                        <div class="task-item">
                            <div class="task-details">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-deadline <?= $isOverdue ? 'overdue' : '' ?>">
                                    <i class="bi bi-calendar-event"></i> 
                                    Due: <?= date('M d, Y', strtotime($task['deadline'])) ?>
                                    <?= $isOverdue ? ' (OVERDUE)' : '' ?>
                                </div>
                            </div>
                            <span class="task-status task-status-<?= strtolower($task['status']) ?>">
                                <?= $task['status'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Announcements Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Announcements</h3>
                </div>
                
                <div class="no-data-message">
                    <p>No announcements available at this time.</p>
                    <p>Check back later for updates from your instructors.</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h3 class="section-title">Quick Actions</h3>
            </div>
            <div class="row text-center">
                <div class="col-6 col-md-3 mb-3">
                    <a href="student_tasks.php" class="btn btn-light w-100 py-3">
                        <i class="bi bi-list-task fs-4 d-block mb-2"></i>
                        My Tasks
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="user_profile.php" class="btn btn-light w-100 py-3">
                        <i class="bi bi-person fs-4 d-block mb-2"></i>
                        View Profile
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="student_tasks.php?filter=completed" class="btn btn-light w-100 py-3">
                        <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                        Completed Tasks
                    </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <a href="user_settings.php" class="btn btn-light w-100 py-3">
                        <i class="bi bi-gear fs-4 d-block mb-2"></i>
                        Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarToggle').click(function() {
                $('.sidebar').toggleClass('active');
                $('.sidebar-overlay').toggleClass('active');
                $('.main-content').toggleClass('expanded');
            });

            $('.sidebar-overlay').click(function() {
                $('.sidebar').removeClass('active');
                $('.sidebar-overlay').removeClass('active');
                $('.main-content').removeClass('expanded');
            });

            // Logout
            $('#logoutBtn').click(function() {
                if (confirm("Are you sure you want to logout?")) {
                    window.location.href = 'admin/logout.php';
                }
            });
            
            // Task item click to view details
            $('.task-item').click(function() {
                // You could add code here to redirect to task details
                // Or show a modal with task details
                // window.location.href = 'task_details.php?id=' + $(this).data('id');
            });
        });
    </script>
</body>
</html>