<?php
include_once '../../includes/db_connect.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$query = "SELECT id, customer_name as name FROM customers ORDER BY customer_name";
$result = $mysqli->query($query);
$customers = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($customers);
