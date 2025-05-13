<?php
require 'dbconnection.php';

header('Content-Type: text/html');

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    try {
        $stmt = $connection->prepare("SELECT student_id, role FROM students WHERE verification_code = ?");
        $stmt->execute([$code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "Invalid verification code.";
            exit();
        }

        $updateStmt = $connection->prepare("UPDATE students SET is_verified = 1, verification_code = NULL WHERE verification_code = ?");
        $updateStmt->execute([$code]);

        if ($updateStmt->rowCount() > 0) {
            echo "Verification successful! Redirecting to login...";
            header("refresh:2;url=admin/login.php");
            exit();
        } else {
            echo "Account already verified or code invalid.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "No verification code provided.";
}
?>