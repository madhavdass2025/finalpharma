<?php
include_once '../../includes/db_connect.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['results' => []]);
    exit();
}

$searchTerm = $_GET['q'] ?? '';

$query = "SELECT id, product_name as text FROM products WHERE product_name LIKE ? ORDER BY product_name LIMIT 20";
$stmt = $mysqli->prepare($query);
$likeTerm = "%$searchTerm%";
$stmt->bind_param("s", $likeTerm);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['results' => $products]);
