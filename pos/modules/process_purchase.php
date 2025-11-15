<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: manage_purchases.php');
    exit();
}

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('CSRF token validation failed.');
}

$supplier_id = $_POST['supplier_id'];
$invoice_number = $_POST['invoice_number'];
$invoice_date = $_POST['invoice_date'];
$discount = $_POST['discount'] ?? 0;
$net_amount = $_POST['net_amount'] ?? 0;
$product_ids = $_POST['product_id'] ?? [];
$batch_numbers = $_POST['batch_number'] ?? [];
$expiry_dates = $_POST['expiry_date'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$purchase_prices = $_POST['purchase_price'] ?? [];
$tax_rates = $_POST['tax_rate'] ?? [];

$mysqli->begin_transaction();

try {
    // 1. Calculate Total Amount
    $total_amount = 0;
    for ($i = 0; $i < count($product_ids); $i++) {
        $total_amount += $quantities[$i] * $purchase_prices[$i] * (1 + $tax_rates[$i] / 100);
    }

    // 2. Insert into purchase_bills
    $stmt = $mysqli->prepare("INSERT INTO purchase_bills (supplier_id, invoice_number, invoice_date, total_amount, discount, net_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
    $stmt->bind_param("issdds", $supplier_id, $invoice_number, $invoice_date, $total_amount, $discount, $net_amount);
    $stmt->execute();
    $purchase_id = $stmt->insert_id;
    $stmt->close();

    // 3. Insert into purchase_items and update/create stock_batches
    $item_stmt = $mysqli->prepare("INSERT INTO purchase_items (purchase_id, product_id, batch_number, expiry_date, quantity, purchase_price, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stock_select_stmt = $mysqli->prepare("SELECT id FROM stock_batches WHERE product_id = ? AND batch_number = ?");
    $stock_update_stmt = $mysqli->prepare("UPDATE stock_batches SET current_qty = current_qty + ?, expiry_date = ?, purchase_price = ? WHERE id = ?");
    $stock_insert_stmt = $mysqli->prepare("INSERT INTO stock_batches (product_id, batch_number, mfg_date, expiry_date, current_qty, purchase_price, tax_rate) VALUES (?, ?, CURDATE(), ?, ?, ?, ?)");

    for ($i = 0; $i < count($product_ids); $i++) {
        // Insert purchase item record
        $item_stmt->bind_param("iissidd", $purchase_id, $product_ids[$i], $batch_numbers[$i], $expiry_dates[$i], $quantities[$i], $purchase_prices[$i], $tax_rates[$i]);
        $item_stmt->execute();

        // Check if batch exists
        $stock_select_stmt->bind_param("is", $product_ids[$i], $batch_numbers[$i]);
        $stock_select_stmt->execute();
        $result = $stock_select_stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Batch exists, update it
            $batch_id = $row['id'];
            $stock_update_stmt->bind_param("isdi", $quantities[$i], $expiry_dates[$i], $purchase_prices[$i], $batch_id);
            $stock_update_stmt->execute();
        } else {
            // New batch, insert it
            $stock_insert_stmt->bind_param("isiddd", $product_ids[$i], $batch_numbers[$i], $expiry_dates[$i], $quantities[$i], $purchase_prices[$i], $tax_rates[$i]);
            $stock_insert_stmt->execute();
        }
    }
    $item_stmt->close();
    $stock_select_stmt->close();
    $stock_update_stmt->close();
    $stock_insert_stmt->close();

    // All good, commit the transaction
    $mysqli->commit();

    // Redirect to success page or purchase view
    header("Location: view_purchase.php?id=" . $purchase_id);
    exit();

} catch (Exception $e) {
    // Something went wrong, rollback
    $mysqli->rollback();
    // Log error and show friendly message
    error_log($e->getMessage());
    die("An error occurred while processing the purchase. Please try again.");
}
