<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$product_id = $_GET['id'] ?? null;
if ($product_id) {
    // Check for related records (stock, sales, purchases) before deleting
    $related_tables = ['stock_batches', 'invoice_items', 'purchase_items'];
    $can_delete = true;
    foreach ($related_tables as $table) {
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM $table WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) {
            $can_delete = false;
            break;
        }
    }

    if (!$can_delete) {
        $_SESSION['error_message'] = "Cannot delete product: It is linked to existing transactions (stock, sales, or purchases).";
    } else {
        $delete_stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Product deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting product.";
        }
        $delete_stmt->close();
    }
}

header('Location: manage_products.php');
exit();
