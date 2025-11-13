<?php
header('Content-Type: application/json');
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Billing/Pharmacists

require_once '../config/database.php';

$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    $batches = [];
    $sql = "
        SELECT id, batch_number, expiry_date, current_qty, (SELECT mrp FROM products WHERE id = ?) as mrp
        FROM stock_batches
        WHERE product_id = ? AND current_qty > 0 AND expiry_date > CURDATE()
        ORDER BY expiry_date ASC
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $product_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        $stmt->close();

        if (!empty($batches)) {
            $response = ['status' => 'success', 'data' => $batches];
        } else {
            $response = ['status' => 'error', 'message' => 'No available stock or batches for this product.'];
        }
    } else {
        $response['message'] = 'Database query failed.';
    }
}

$conn->close();
echo json_encode($response);
exit;
?>