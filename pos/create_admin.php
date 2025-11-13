<?php
require_once 'pos/config/database.php';
$name = 'Admin';
$email = 'admin@test.com';
$password = 'password';
$role_id = 1;

$sql = "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("sssi", $name, $email, $password, $role_id);
    $stmt->execute();
    $stmt->close();
    echo "Admin user created.";
}
$conn->close();
?>