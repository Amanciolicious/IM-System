<?php
require 'dbconnection.php';

header('Content-Type: application/json');

$response = [];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $userId = $_POST['user_id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $birthdate = $_POST['birthdate'];
        $course = $_POST['course'];

        $stmt = $connection->prepare("SELECT profile_image FROM students WHERE student_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $oldImage = $user['profile_image'];

        $query = "UPDATE students SET first_name=?, last_name=?, email=?, gender=?, user_address=?, birthdate=?, course=? WHERE student_id=?";
        $params = [$firstName, $lastName, $email, $gender, $address, $birthdate, $course, $userId];

        if (!empty($_FILES['profileImage']['name'])) {
            $uploadDir = "profiles/";
            $imageName = time() . "_" . basename($_FILES['profileImage']['name']);
            $uploadFile = $uploadDir . $imageName;

            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadFile)) {
                if (!empty($oldImage) && file_exists($oldImage)) {
                    unlink($oldImage);
                }
                $query = "UPDATE students SET first_name=?, last_name=?, email=?, gender=?, user_address=?, birthdate=?, course=?, profile_image=? WHERE student_id=?";
                $params = [$firstName, $lastName, $email, $gender, $address, $birthdate, $course, $uploadFile, $userId];
            }
        }

        $stmt = $connection->prepare($query);
        if ($stmt->execute($params)) {
            $response['status'] = "success";
        } else {
            throw new Exception("Failed to update user.");
        }
    }
} catch (Exception $e) {
    $response['status'] = "error";
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
