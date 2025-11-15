<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$customer_id = $_GET['id'] ?? null;
if ($customer_id) {
    // Check for related records in sales_invoices
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM sales_invoices WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['error_message'] = "Cannot delete customer: They are linked to existing sales invoices.";
    } else {
        $delete_stmt = $mysqli->prepare("DELETE FROM customers WHERE id = ?");
        $delete_stmt->bind_param("i", $customer_id);
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Customer deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting customer.";
        }
        $delete_stmt->close();
    }
}

header('Location: manage_customers.php');
exit();
