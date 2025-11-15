<?php
header('Content-Type: application/json');
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Accessible by Admin and Pharmacist

require_once '../config/database.php';

$response = ['status' => 'error', 'message' => 'Invalid Request'];

if (isset($_GET['reg_no']) && !empty(trim($_GET['reg_no']))) {
    $reg_no = trim($_GET['reg_no']);

    $sql = "SELECT id, name, phone, address FROM customers WHERE reg_no = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $reg_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $customer = $result->fetch_assoc();
            $response = ['status' => 'success', 'data' => $customer];
        } else {
            $response['message'] = 'Customer not found.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Database query failed.';
    }
}

$conn->close();
echo json_encode($response);
exit;
?>