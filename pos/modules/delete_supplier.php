<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$supplier_id = $_GET['id'] ?? null;
if ($supplier_id) {
    // Check for related records in purchase_bills before deleting
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM purchase_bills WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['error_message'] = "Cannot delete supplier: They are linked to existing purchase bills.";
    } else {
        $delete_stmt = $mysqli->prepare("DELETE FROM suppliers WHERE id = ?");
        $delete_stmt->bind_param("i", $supplier_id);
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Supplier deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting supplier.";
        }
        $delete_stmt->close();
    }
}

header('Location: manage_suppliers.php');
exit();
