<?php 
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php'); // For students

// Get current user data
$currentUserId = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, last_name, profile_image FROM students WHERE student_id = ?");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get tasks assigned to this student via task_assignments table
$stmt = $connection->prepare("SELECT t.*, 
                           s.first_name, s.last_name 
                           FROM tasks t
                           INNER JOIN task_assignments ta ON t.task_id = ta.task_id
                           LEFT JOIN students s ON t.created_by = s.student_id
                           WHERE ta.student_id = ?
                           ORDER BY t.deadline ASC");
$stmt->execute([$currentUserId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - HealthAdmin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .task-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .task-card h3 {
            margin-top: 0;
            color: #333;
        }
        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .task-status-pending {
            background-color: #fff0f0;
            color: #dc3545;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .task-status-completed {
            background-color: #f0fff0;
            color: #198754;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .task-deadline {
            color: #666;
            font-size: 0.9rem;
        }
        .task-deadline.overdue {
            color: #dc3545;
            font-weight: 600;
        }
        .task-description {
            margin: 10px 0;
            color: #555;
        }
        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .task-actions button {
            margin-left: 5px;
        }
        .filter-buttons {
            margin-bottom: 20px;
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
            <a href="user_dashboard.php" class="sidebar-link">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="student_tasks.php" class="sidebar-link active">
                <i class="bi bi-list-task"></i>
                <span>My Tasks</span>
            </a>
            <a href="student_profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="student_settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </nav>
        <div class="sidebar-user-panel">
            <img src="profiles/<?= $currentUser['profile_image'] ?? 'default.jpg' ?>" class="sidebar-user-avatar">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                <div class="sidebar-user-role">Student</div>
            </div>
            <button class="sidebar-logout-btn" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title-d">My Tasks</div>
        
        <!-- Filter buttons -->
        <div class="filter-buttons">
            <button class="btn btn-outline-primary active" data-filter="all">All Tasks</button>
            <button class="btn btn-outline-warning" data-filter="pending">Pending Tasks</button>
            <button class="btn btn-outline-success" data-filter="completed">Completed Tasks</button>
        </div>
        
        <?php if (empty($tasks)): ?>
            <div class="alert alert-info">
                You don't have any assigned tasks yet.
            </div>
        <?php else: ?>
            <div class="task-container">
                <?php foreach ($tasks as $task):
                    $isPending = $task['status'] === 'Pending';
                    $isOverdue = $isPending && strtotime($task['deadline']) < time();
                ?>
                    <div class="task-card" data-status="<?= strtolower($task['status']) ?>">
                        <div class="task-card-header">
                            <h3><?= htmlspecialchars($task['title']) ?></h3>
                            <span class="task-status-<?= strtolower($task['status']) ?>"><?= $task['status'] ?></span>
                        </div>
                        
                        <div class="task-deadline <?= $isOverdue ? 'overdue' : '' ?>">
                            <i class="bi bi-calendar-event"></i> 
                            Due: <?= date('M d, Y \a\t h:i A', strtotime($task['deadline'])) ?>
                            <?= $isOverdue ? ' (OVERDUE)' : '' ?>
                        </div>
                        
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                        </div>
                        
                        <div class="task-footer">
                            <div class="task-creator">
                                <small>Assigned by: <?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></small>
                            </div>
                            <div class="task-actions">
                                <button class="btn btn-sm btn-primary view-task-details" data-id="<?= $task['task_id'] ?>">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <?php if ($isPending): ?>
                                    <button class="btn btn-sm btn-success mark-complete" data-id="<?= $task['task_id'] ?>">
                                        <i class="bi bi-check-lg"></i> Mark Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="taskDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Task Modal -->
    <div class="modal fade" id="submitTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="submitTaskForm" enctype="multipart/form-data">
                        <input type="hidden" name="task_id" id="submitTaskId">
                        <div class="mb-3">
                            <label for="submissionText" class="form-label">Submission Text</label>
                            <textarea class="form-control" id="submissionText" name="submission_text" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="animate.js"></script>
    <script>
        $(document).ready(function() {
            // Filter tasks
            $('.filter-buttons .btn').click(function() {
                $('.filter-buttons .btn').removeClass('active');
                $(this).addClass('active');
                
                const filter = $(this).data('filter');
                if (filter === 'all') {
                    $('.task-card').show();
                } else {
                    $('.task-card').hide();
                    $(`.task-card[data-status="${filter}"]`).show();
                }
            });

            // View task details
            $('.view-task-details').click(function() {
                const taskId = $(this).data('id');
                
                $.ajax({
                    url: 'student_task_actions.php',
                    type: 'GET',
                    data: {
                        action: 'view',
                        task_id: taskId
                    },
                    success: function(response) {
                        $('#taskDetailsContent').html(response);
                        $('#taskDetailsModal').modal('show');
                    }
                });
            });

            // Mark task as complete
            $('.mark-complete').click(function() {
                const taskId = $(this).data('id');
                
                if (confirm("Mark this task as completed?")) {
                    $.ajax({
                        url: 'student_task_actions.php',
                        type: 'POST',
                        data: {
                            action: 'complete',
                            task_id: taskId
                        },
                        success: function(response) {
                            alert("Task marked as completed!");
                            location.reload();
                        }
                    });
                }
            });

            // Handle form submission
            $('#submitTaskForm').submit(function(event) {
                event.preventDefault();
                
                const taskId = $('#submitTaskId').val();
                const submissionText = $('#submissionText').val();
                const attachments = $('#attachments')[0].files;
                
                const formData = new FormData();
                formData.append('action', 'submit');
                formData.append('task_id', taskId);
                formData.append('submission_text', submissionText);
                
                for (let i = 0; i < attachments.length; i++) {
                    formData.append('attachments[]', attachments[i]);
                }
                
                $.ajax({
                    url: 'student_task_actions.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert("Task submitted successfully!");
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert("Error submitting task: " + error);
                    }
                });
            });

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
                    window.location.href = 'logout.php';
                }
            });
        });
    </script>
</body>
</html>
