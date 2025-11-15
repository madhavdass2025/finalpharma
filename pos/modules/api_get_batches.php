<?php
include_once '../includes/db_connect.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$product_id = $_GET['product_id'] ?? 0;

if ($product_id) {
    $query = "SELECT id, batch_number, expiry_date, current_qty, mrp
              FROM stock_batches sb
              JOIN products p ON sb.product_id = p.id
              WHERE product_id = ? AND current_qty > 0
              ORDER BY expiry_date ASC";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $batches = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($batches);
} else {
    echo json_encode([]);
}
