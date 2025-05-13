<?php
session_start();
require __DIR__ . '/../dbconnection.php';  

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Check if user exists and is verified
        $stmt = $connection->prepare("SELECT student_id, email, user_password, role FROM students WHERE email = ? AND is_verified = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or account not verified']);
            exit();
        }

        // Verify password
        if (password_verify($password, $user['user_password'])) {
            // Store common user info
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['email'] = $user['email'];
            
            // Store role-specific info
            if ($user['role'] === 'admin') {
                $_SESSION['is_admin'] = true;
                $redirect = "../admin_dashboard.php";
            } else {
                $_SESSION['is_admin'] = false;
                $redirect = "../user_dashboard.php";
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Login successful',
                'redirect' => $redirect
            ]);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>