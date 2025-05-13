<?php 
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php');

// Get current user data
$currentUserId = $_SESSION['admin'];
$stmt = $connection->prepare("SELECT first_name, last_name, profile_image FROM students WHERE student_id = ?");
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all tasks with assigned students
$tasks = $connection->query("SELECT t.*, 
                          GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as assigned_names
                          FROM tasks t
                          LEFT JOIN task_assignments ta ON t.task_id = ta.task_id
                          LEFT JOIN students s ON ta.student_id = s.student_id
                          GROUP BY t.task_id
                          ORDER BY t.deadline ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all students for assignment dropdown
$students = $connection->query("SELECT student_id, first_name, last_name FROM students")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tasks - HealthAdmin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Add Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin-left: 10rem;
        }
        .page-title-d {
            margin-left: 0;
        }
        .task-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .task-table th, .task-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .task-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        .task-actions {
            display: flex;
            gap: 5px;
        }
        .task-status-pending {
            color: #dc3545;
            font-weight: 600;
        }
        .task-status-completed {
            color: #198754;
            font-weight: 600;
        }
        .add-task-btn {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            margin-bottom: 15px;
        }
        .task-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .filter-btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #0d6efd;
            background-color: transparent;
            color: #0d6efd;
            cursor: pointer;
        }
        .filter-btn.active {
            background-color: #0d6efd;
            color: white;
        }
        .assigned-students-list {
            margin-top: 5px;
            padding-left: 20px;
        }
        /* Custom styling for Choices.js */
        .choices {
            margin-bottom: 0;
        }
        .choices__inner {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            min-height: 38px;
        }
        .choices__input {
            background-color: transparent;
        }
        .modal-open .choices__list--dropdown {
            z-index: 1056;
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
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-house"></i>
                <span>Home</span>
            </a>
            <a href="dashboard.php" class="sidebar-link">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_assign_tasks.php" class="sidebar-link active">
                <i class="bi bi-list-task"></i>
                <span>Assign Tasks</span>
            </a>
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
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
        <div class="page-title-d">Assign Tasks</div>
        
        <!-- Display success/error messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="task-manager-container">
            <div class="task-filters">
                <button class="filter-btn active" data-filter="all">All Tasks</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
            </div>
            
            <button class="add-task-btn" id="addTaskBtn">
                <i class="bi bi-plus"></i> Add Task
            </button>
            
            <table class="task-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Deadline</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr data-status="<?= strtolower($task['status']) ?>">
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td><?= htmlspecialchars(substr($task['description'], 0, 50)) . (strlen($task['description']) > 50 ? '...' : '') ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($task['deadline'])) ?></td>
                            <td><?= $task['assigned_names'] ?: 'Unassigned' ?></td>
                            <td class="task-status-<?= strtolower($task['status']) ?>">
                                <?= htmlspecialchars($task['status']) ?>
                            </td>
                            <td class="task-actions">
                                <button class="btn btn-primary btn-sm view-task" data-id="<?= $task['task_id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm edit-task" data-id="<?= $task['task_id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($task['status'] != 'Completed'): ?>
                                <button class="btn btn-success btn-sm complete-task" data-id="<?= $task['task_id'] ?>">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm delete-task" data-id="<?= $task['task_id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTaskForm" action="admin_task_actions.php" method="post">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="taskTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="taskDeadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" id="taskDeadline" name="deadline" required>
                        </div>
                        <div class="mb-3">
                            <label for="assignedTo" class="form-label">Assign To</label>
                            <select class="form-select choices-select" id="assignedTo" name="assigned_to[]" multiple>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id'] ?>">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Task Modal -->
    <div class="modal fade" id="viewTaskModal" tabindex="-1" aria-hidden="true">
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

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTaskForm" action="admin_task_actions.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="task_id" id="editTaskId">
                        <div class="mb-3">
                            <label for="editTaskTitle" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskDeadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" id="editTaskDeadline" name="deadline" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAssignedTo" class="form-label">Assign To</label>
                            <select class="form-select choices-select" id="editAssignedTo" name="assigned_to[]" multiple>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id'] ?>">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskStatus" class="form-label">Status</label>
                            <select class="form-select" id="editTaskStatus" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Task Confirmation Modal -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this task? This action cannot be undone.</p>
                    <form id="deleteTaskForm" action="admin_task_actions.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="task_id" id="deleteTaskId">
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add Choices.js script -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="animate.js"></script>
    <script>
      $(document).ready(function() {
        // Function to initialize Choices.js on select elements
        function initializeChoices(element) {
            return new Choices(element, {
                removeItemButton: true,
                searchEnabled: true,
                searchPlaceholderValue: 'Search for a student...',
                placeholder: true,
                placeholderValue: 'Select students...',
                itemSelectText: 'Press to select',
                noResultsText: 'No students found',
                noChoicesText: 'No more students available'
            });
        }
        
        // Initialize Choices on existing select elements
        document.querySelectorAll('.choices-select').forEach(function(element) {
            initializeChoices(element);
        });

        // Task Filters (All, Pending, Completed)
        $('.filter-btn').click(function() {
            // Remove active class from all buttons
            $('.filter-btn').removeClass('active');
            // Add active class to clicked button
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            
            if (filter === 'all') {
                // Show all tasks
                $('.task-table tbody tr').show();
            } else {
                // Hide all tasks first
                $('.task-table tbody tr').hide();
                // Show only tasks with matching status
                $(`.task-table tbody tr[data-status="${filter}"]`).show();
            }
        });
        
        // Show add task modal
        $('#addTaskBtn').click(function() {
            $('#addTaskModal').modal('show');
        });
        
        // View task details
        $('.view-task').click(function() {
            const taskId = $(this).data('id');
            
            // Load task details via AJAX
            $.ajax({
                url: 'admin_task_actions.php',
                type: 'GET',
                data: {
                    action: 'view',
                    task_id: taskId
                },
                success: function(response) {
                    $('#taskDetailsContent').html(response);
                    $('#viewTaskModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error loading task details:", error);
                    alert("Could not load task details. Please try again.");
                }
            });
        });
        
        // Storage for Choices.js instances
        let editChoicesInstance = null;
        
        // Edit task
        $('.edit-task').click(function() {
            const taskId = $(this).data('id');
            
            $.ajax({
                url: 'admin_task_actions.php',
                type: 'GET',
                data: {
                    action: 'get',
                    task_id: taskId
                },
                dataType: 'json',
                success: function(task) {
                    $('#editTaskId').val(task.task_id);
                    $('#editTaskTitle').val(task.title);
                    $('#editTaskDescription').val(task.description);
                    $('#editTaskDeadline').val(task.deadline);
                    $('#editTaskStatus').val(task.status);
                    
                    // Destroy existing Choices instance if it exists
                    if (editChoicesInstance) {
                        editChoicesInstance.destroy();
                    }
                    
                    // Initialize new Choices.js instance
                    editChoicesInstance = initializeChoices(document.getElementById('editAssignedTo'));
                    
                    // Set the values for assigned students
                    if (task.assigned_students && task.assigned_students.length > 0) {
                        editChoicesInstance.setChoiceByValue(task.assigned_students);
                    }
                    
                    $('#editTaskModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error loading task data:", error);
                    alert("Could not load task data. Please try again.");
                }
            });
        });
        
        // Handle modal hidden event to prevent conflicts
        $('#editTaskModal').on('hidden.bs.modal', function () {
            if (editChoicesInstance) {
                editChoicesInstance.destroy();
                editChoicesInstance = null;
            }
        });
        
        // Complete task
        $('.complete-task').click(function() {
            if (confirm('Mark this task as completed?')) {
                const taskId = $(this).data('id');
                
                $.ajax({
                    url: 'admin_task_actions.php',
                    type: 'POST',
                    data: {
                        action: 'complete',
                        task_id: taskId
                    },
                    success: function(response) {
                        if (response === 'success') {
                            // Update the UI to reflect completion
                            const row = $(`button.complete-task[data-id="${taskId}"]`).closest('tr');
                            row.find('.task-status-pending')
                               .removeClass('task-status-pending')
                               .addClass('task-status-completed')
                               .text('Completed');
                            
                            // Remove the complete button
                            $(`button.complete-task[data-id="${taskId}"]`).remove();
                            
                            // Update the data-status attribute for filtering
                            row.attr('data-status', 'completed');
                            
                            // Show success message
                            alert('Task marked as completed successfully!');
                        } else {
                            alert('Error completing task: ' + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error completing task:", error);
                        alert("Failed to complete task. Please try again.");
                    }
                });
            }
        });
        
        // Delete task
        $('.delete-task').click(function() {
            const taskId = $(this).data('id');
            $('#deleteTaskId').val(taskId);
            $('#deleteTaskModal').modal('show');
        });
        
        // Sidebar toggle functionality
        $('#sidebarToggle').click(function() {
            $('.sidebar').toggleClass('collapsed');
            $('.main-content').toggleClass('expanded');
            $('.sidebar-overlay').toggleClass('active');
        });
        
        // Close sidebar when clicking overlay (mobile)
        $('.sidebar-overlay').click(function() {
            $('.sidebar').removeClass('collapsed');
            $('.sidebar-overlay').removeClass('active');
        });
        
        // Logout button functionality
        $('#logoutBtn').click(function() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });
    });
    </script>
</body>
</html>