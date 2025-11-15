<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: pos.php');
    exit();
}

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('CSRF token validation failed.');
}

$customer_id = $_POST['customer_id'] ?? null;
$user_id = $_SESSION['user_id'];
$invoice_date = date('Y-m-d');
$product_ids = $_POST['product_id'] ?? [];
$batch_ids = $_POST['batch_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices = $_POST['price'] ?? [];
$payment_methods = $_POST['payment_method'] ?? [];
$amounts_paid = $_POST['amount_paid'] ?? [];

$mysqli->begin_transaction();

try {
    // 1. Create Invoice Number
    $invoice_number = 'INV-' . date('Ymd') . '-' . time(); // Simple invoice number

    // 2. Calculate Net Amount
    $net_amount = 0;
    for ($i = 0; $i < count($product_ids); $i++) {
        $net_amount += $quantities[$i] * $prices[$i];
    }

    // 3. Insert into sales_invoices
    $stmt = $mysqli->prepare("INSERT INTO sales_invoices (invoice_number, invoice_date, customer_id, user_id, net_amount, status) VALUES (?, ?, ?, ?, ?, 'completed')");
    $stmt->bind_param("ssiid", $invoice_number, $invoice_date, $customer_id, $user_id, $net_amount);
    $stmt->execute();
    $invoice_id = $stmt->insert_id;
    $stmt->close();

    // 4. Insert into invoice_items and update stock
    $item_stmt = $mysqli->prepare("INSERT INTO invoice_items (invoice_id, product_id, batch_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stock_stmt = $mysqli->prepare("UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?");

    for ($i = 0; $i < count($product_ids); $i++) {
        // Insert item
        $item_stmt->bind_param("iiidi", $invoice_id, $product_ids[$i], $batch_ids[$i], $quantities[$i], $prices[$i]);
        $item_stmt->execute();

        // Update stock
        $stock_stmt->bind_param("iii", $quantities[$i], $batch_ids[$i], $quantities[$i]);
        $stock_stmt->execute();
        if ($stock_stmt->affected_rows === 0) {
            throw new Exception("Insufficient stock for product ID: " . $product_ids[$i]);
        }
    }
    $item_stmt->close();
    $stock_stmt->close();

    // 5. Insert into payments
    $payment_stmt = $mysqli->prepare("INSERT INTO payments (invoice_id, payment_method, amount_paid) VALUES (?, ?, ?)");
    for ($i = 0; $i < count($payment_methods); $i++) {
        if (!empty($amounts_paid[$i])) {
            $payment_stmt->bind_param("isd", $invoice_id, $payment_methods[$i], $amounts_paid[$i]);
            $payment_stmt->execute();
        }
    }
    $payment_stmt->close();

    // If all good, commit
    $mysqli->commit();

    // Redirect to a success page or the invoice view
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit();

} catch (Exception $e) {
    // If anything fails, roll back
    $mysqli->rollback();
    // Log the error and show a user-friendly message
    error_log($e->getMessage());
    die("An error occurred while processing the sale. Please try again.");
}
