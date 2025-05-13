<?php
require __DIR__ . '/dbconnection.php';
include('admin/auth_check.php'); // For students

// Get the current student ID
$currentUserId = $_SESSION['user_id'];

// Handle different task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'complete':
            completeTask($connection, $currentUserId);
            break;
        case 'submit':
            submitTask($connection, $currentUserId);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'view':
            viewTask($connection, $currentUserId);
            break;
    }
}
// Function to submit a task
function submitTask($connection, $studentId) {
    try {
        $taskId = $_POST['task_id'];
        $submissionText = $_POST['submission_text'] ?? '';
        $attachments = $_FILES['attachments'] ?? [];

        // Insert the submission
        $stmt = $connection->prepare("INSERT INTO submissions (task_id, student_id, submission_text) VALUES (?, ?, ?)");
        $stmt->execute([$taskId, $studentId, $submissionText]);
        $submissionId = $connection->lastInsertId();

        // Handle file uploads
        if (!empty($attachments['name'])) {
            $uploadDir = 'uploads/';
            $attachmentStmt = $connection->prepare("INSERT INTO submission_attachments (submission_id, attachment_type, attachment_url) VALUES (?, ?, ?)");

            foreach ($attachments['name'] as $index => $name) {
                $tmpName = $attachments['tmp_name'][$index];
                $type = $attachments['type'][$index];
                $url = $uploadDir . basename($name);

                if (move_uploaded_file($tmpName, $url)) {
                    $attachmentType = pathinfo($url, PATHINFO_EXTENSION);
                    $attachmentStmt->execute([$submissionId, $attachmentType, $url]);
                }
            }
        }

        echo "success";
        exit;
    } catch (PDOException $e) {
        echo "Error submitting task: " . $e->getMessage();
        exit;
    }
}   
// Function to view task details
// Function to view task details
function viewTask($connection, $studentId) {
    try {
        $taskId = $_GET['task_id'];

        // Verify the task is assigned to this student using task_assignments table
        $stmt = $connection->prepare("SELECT t.*, 
                                    s.first_name as creator_first_name, s.last_name as creator_last_name
                                    FROM tasks t 
                                    INNER JOIN task_assignments ta ON t.task_id = ta.task_id
                                    LEFT JOIN students s ON t.created_by = s.student_id
                                    WHERE t.task_id = ? AND ta.student_id = ?");
        $stmt->execute([$taskId, $studentId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            echo "<div class='alert alert-danger'>Task not found or not assigned to you</div>";
            exit;
        }

        // Format the output
        echo "<div class='task-details'>";
        echo "<h3>" . htmlspecialchars($task['title']) . "</h3>";
        echo "<p class='task-description'>" . nl2br(htmlspecialchars($task['description'])) . "</p>";

        echo "<div class='task-meta'>";
        echo "<p><strong>Status:</strong> <span class='badge " . ($task['status'] == 'Completed' ? 'bg-success' : 'bg-warning') . "'>" . $task['status'] . "</span></p>";

        // Check if deadline is past
        $deadlineTimestamp = strtotime($task['deadline']);
        $isPastDeadline = $deadlineTimestamp < time() && $task['status'] == 'Pending';

        echo "<p><strong>Deadline:</strong> <span" . ($isPastDeadline ? " class='text-danger'" : "") . ">" . date('Y-m-d H:i', $deadlineTimestamp) . ($isPastDeadline ? " (OVERDUE)" : "") . "</span></p>";

        echo "<p><strong>Assigned by:</strong> " . htmlspecialchars($task['creator_first_name'] . ' ' . $task['creator_last_name']) . "</p>";
        echo "<p><strong>Created on:</strong> " . date('Y-m-d H:i', strtotime($task['created_at'])) . "</p>";

        if ($task['status'] == 'Completed' && !empty($task['completed_at'])) {
            echo "<p><strong>Completed on:</strong> " . date('Y-m-d H:i', strtotime($task['completed_at'])) . "</p>";

            // Calculate if completed before deadline
            $completedBeforeDeadline = strtotime($task['completed_at']) <= $deadlineTimestamp;
            echo "<p><strong>Completion status:</strong> " . 
                 ($completedBeforeDeadline ? 
                 "<span class='text-success'>Completed on time</span>" : 
                 "<span class='text-warning'>Completed after deadline</span>") . 
                 "</p>";
        }

        echo "</div>"; // End task-meta
        echo "</div>"; // End task-details

        // If task is pending, show a complete button
        if ($task['status'] == 'Pending') {
            echo "<div class='mt-3 d-flex justify-content-between'>";
            echo "<button class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
            echo "<button class='btn btn-success mark-complete-modal' data-id='" . $task['task_id'] . "'>Mark as Complete</button>";
            echo "</div>";

            // Add JavaScript to handle the completion from within the modal
            echo "<script>
                $('.mark-complete-modal').click(function() {
                    const taskId = $(this).data('id');
                    
                    $.ajax({
                        url: 'student_task_actions.php',
                        type: 'POST',
                        data: {
                            action: 'complete',
                            task_id: taskId
                        },
                        success: function(response) {
                            $('#taskDetailsModal').modal('hide');
                            alert('Task marked as completed!');
                            location.reload();
                        }
                    });
                });
            </script>";
        } else {
            echo "<div class='mt-3'>";
            echo "<button class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
            echo "</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error retrieving task details: " . $e->getMessage() . "</div>";
    }
}
?>