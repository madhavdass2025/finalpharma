<?php
header('Content-Type: application/json');
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Billing/Pharmacists

require_once '../config/database.php';

$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    $batches = [];
    // For returns, we want to see all batches, even with 0 stock, to allow for corrections.
    $sql = "
        SELECT id, batch_number, current_qty
        FROM stock_batches
        WHERE product_id = ?
        ORDER BY batch_number ASC
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $batches[] = $row;
        }
        $stmt->close();

        $response = ['status' => 'success', 'data' => $batches];
    } else {
        $response['message'] = 'Database query failed.';
    }
}

$conn->close();
echo json_encode($response);
exit;
?>