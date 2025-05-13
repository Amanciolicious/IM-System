<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "student_datab"; 

try {
    $connection = new PDO("mysql:host=$servername;dbname=$database;charset=utf8", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $th) {
    die(json_encode(['error' => "Database connection failed: " . $th->getMessage()]));
}
?>
