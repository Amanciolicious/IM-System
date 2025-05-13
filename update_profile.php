<?php
require __DIR__ . '/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];

    // Handle image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $imageName = time() . '_' . $_FILES['profile_image']['name'];
        $imagePath = 'profiles/' . $imageName;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $imagePath);

        $stmt = $connection->prepare("UPDATE students SET first_name = ?, last_name = ?, profile_image = ? WHERE student_id = ?");
        $stmt->execute([$firstName, $lastName, $imageName, $studentId]);
    } else {
        $stmt = $connection->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE student_id = ?");
        $stmt->execute([$firstName, $lastName, $studentId]);
    }

    header("Location: profile.php");
    exit();
}
