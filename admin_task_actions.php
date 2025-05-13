<?php
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php');

// Handle different task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            addTask($connection);
            break;
        case 'update':
            updateTask($connection);
            break;
        case 'complete':
            completeTask($connection);
            break;
        case 'delete':
            deleteTask($connection);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'view':
            viewTask($connection);
            break;
        case 'get':
            getTask($connection);
            break;
    }
}

// Function to add a new task
function addTask($connection) {
    try {
        // Begin transaction
        $connection->beginTransaction();
        
        $title = $_POST['title'];
        $description = $_POST['description'] ?? '';
        $deadline = $_POST['deadline'];
        
        // Use user_id if available, otherwise fallback to admin ID
        $createdBy = $_SESSION['admin'] ?? $_SESSION['user_id'] ?? null;
        
        if (!$createdBy) {
            throw new Exception("User not properly authenticated");
        }
        
        // Debug - check what's coming in
        error_log("Adding task with title: " . $title);
        error_log("Created by user ID: " . $createdBy);
        error_log("Assigned students POST data: " . print_r($_POST['assigned_to'] ?? [], true));
        
        // Get assigned students array
        $assignedStudents = isset($_POST['assigned_to']) && is_array($_POST['assigned_to']) ? $_POST['assigned_to'] : [];
        
        // Insert the task
        $stmt = $connection->prepare("INSERT INTO tasks (title, description, deadline, created_by, status, created_at) 
                                    VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $stmt->execute([$title, $description, $deadline, $createdBy]);
        
        // Get the task ID
        $taskId = $connection->lastInsertId();
        
        // Insert assignments if students were selected
        if (!empty($assignedStudents)) {
            $assignmentStmt = $connection->prepare("INSERT INTO task_assignments (task_id, student_id, assigned_at) VALUES (?, ?, NOW())");
            
            foreach ($assignedStudents as $studentId) {
                if (!empty($studentId)) {
                    $assignmentStmt->execute([$taskId, $studentId]);
                }
            }
        }
        
        // Commit the transaction
        $connection->commit();
        
        header("Location: admin_assign_tasks.php?success=Task added successfully");
        exit;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $connection->rollBack();
        error_log("Error adding task: " . $e->getMessage());
        header("Location: admin_assign_tasks.php?error=" . urlencode("Error adding task: " . $e->getMessage()));
        exit;
    }
}

// Function to update an existing task
function updateTask($connection) {
    try {
        $taskId = $_POST['task_id'];
        $title = $_POST['title'];
        $description = $_POST['description'] ?? '';
        $deadline = $_POST['deadline'];
        $assignedStudents = isset($_POST['assigned_to']) ? $_POST['assigned_to'] : [];
        $status = $_POST['status'];
        
        // Begin transaction
        $connection->beginTransaction();
        
        // Update the task
        $stmt = $connection->prepare("UPDATE tasks 
                                     SET title = ?, description = ?, deadline = ?, 
                                         status = ?, updated_at = NOW() 
                                     WHERE task_id = ?");
        $stmt->execute([$title, $description, $deadline, $status, $taskId]);
        
        // Remove all existing assignments for this task
        $deleteStmt = $connection->prepare("DELETE FROM task_assignments WHERE task_id = ?");
        $deleteStmt->execute([$taskId]);
        
        // Insert new assignments if students were selected
        if (!empty($assignedStudents)) {
            $assignmentStmt = $connection->prepare("INSERT INTO task_assignments (task_id, student_id, assigned_at) VALUES (?, ?, NOW())");
            
            foreach ($assignedStudents as $studentId) {
                $assignmentStmt->execute([$taskId, $studentId]);
            }
        }
        
        // Commit the transaction
        $connection->commit();
        
        header("Location: admin_assign_tasks.php?success=Task updated successfully");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $connection->rollBack();
        header("Location: admin_assign_tasks.php?error=" . urlencode("Error updating task: " . $e->getMessage()));
        exit;
    }
}

// Function to mark a task as complete
function completeTask($connection) {
    try {
        $taskId = $_POST['task_id'];
        
        // Begin transaction
        $connection->beginTransaction();
        
        // Update the task status
        $stmt = $connection->prepare("UPDATE tasks SET status = 'Completed', completed_at = NOW() WHERE task_id = ?");
        $stmt->execute([$taskId]);
        
        // Commit the transaction
        $connection->commit();
        
        echo "success";
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $connection->rollBack();
        echo "Error completing task: " . $e->getMessage();
        exit;
    }
}
// Function to delete a task
function deleteTask($connection) {
    try {
        $taskId = $_POST['task_id'];
        
        // Begin transaction
        $connection->beginTransaction();
        
        // Delete the assignment records first
        $deleteAssignmentsStmt = $connection->prepare("DELETE FROM task_assignments WHERE task_id = ?");
        $deleteAssignmentsStmt->execute([$taskId]);
        
        // Then delete the task
        $deleteTaskStmt = $connection->prepare("DELETE FROM tasks WHERE task_id = ?");
        $deleteTaskStmt->execute([$taskId]);
        
        // Commit the transaction
        $connection->commit();
        
        header("Location: admin_assign_tasks.php?success=Task deleted successfully");
        exit;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $connection->rollBack();
        header("Location: admin_assign_tasks.php?error=" . urlencode("Error deleting task: " . $e->getMessage()));
        exit;
    }
}

// Function to view task details
function viewTask($connection) {
    try {
        $taskId = $_GET['task_id'];
        
        // Get task details
        $stmt = $connection->prepare("SELECT t.*, 
                                    s2.first_name as creator_first_name, s2.last_name as creator_last_name
                                    FROM tasks t 
                                    LEFT JOIN students s2 ON t.created_by = s2.student_id
                                    WHERE t.task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            echo "<div class='alert alert-danger'>Task not found</div>";
            exit;
        }
        
        // Get assigned students
        $assignedStudentsStmt = $connection->prepare("SELECT s.student_id, s.first_name, s.last_name 
                                                    FROM task_assignments ta
                                                    JOIN students s ON ta.student_id = s.student_id
                                                    WHERE ta.task_id = ?");
        $assignedStudentsStmt->execute([$taskId]);
        $assignedStudents = $assignedStudentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the output
        echo "<div class='task-details'>";
        echo "<h3>" . htmlspecialchars($task['title']) . "</h3>";
        echo "<p class='task-description'>" . nl2br(htmlspecialchars($task['description'])) . "</p>";
        
        echo "<div class='task-meta'>";
        echo "<p><strong>Status:</strong> <span class='badge " . ($task['status'] == 'Completed' ? 'bg-success' : 'bg-warning') . "'>" . $task['status'] . "</span></p>";
        echo "<p><strong>Deadline:</strong> " . date('Y-m-d H:i', strtotime($task['deadline'])) . "</p>";
        
        if (!empty($assignedStudents)) {
            echo "<p><strong>Assigned to:</strong></p>";
            echo "<ul class='assigned-students-list'>";
            foreach ($assignedStudents as $student) {
                echo "<li>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>Assigned to:</strong> Unassigned</p>";
        }
        
        echo "<p><strong>Created by:</strong> " . htmlspecialchars($task['creator_first_name'] . ' ' . $task['creator_last_name']) . "</p>";
        echo "<p><strong>Created on:</strong> " . date('Y-m-d H:i', strtotime($task['created_at'])) . "</p>";
        
        if ($task['status'] == 'Completed' && !empty($task['completed_at'])) {
            echo "<p><strong>Completed on:</strong> " . date('Y-m-d H:i', strtotime($task['completed_at'])) . "</p>";
        }
        
        echo "</div>"; // End task-meta
        echo "</div>"; // End task-details
        
        echo "<div class='mt-3'>";
        echo "<button class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error retrieving task details: " . $e->getMessage() . "</div>";
    }
}

// Function to get task data for editing
function getTask($connection) {
    try {
        $taskId = $_GET['task_id'];
        
        // Get task details
        $stmt = $connection->prepare("SELECT * FROM tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            echo json_encode(['error' => 'Task not found']);
            exit;
        }
        
        // Get assigned students
        $assignedStudentsStmt = $connection->prepare("SELECT student_id FROM task_assignments WHERE task_id = ?");
        $assignedStudentsStmt->execute([$taskId]);
        $assignedStudents = $assignedStudentsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add assigned students to the task data
        $task['assigned_students'] = $assignedStudents;
        
        // Convert deadline to HTML datetime-local format
        $task['deadline'] = date('Y-m-d\TH:i', strtotime($task['deadline']));
        
        header('Content-Type: application/json');
        echo json_encode($task);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error retrieving task: ' . $e->getMessage()]);
    }
}