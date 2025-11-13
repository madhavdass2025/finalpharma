<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]);

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Sale processing logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale'])) {
    check_csrf();
    $customer_name = $_POST['customer_name'];
    $user_id = $_SESSION['id'];
    $invoice_date = date('Y-m-d');

    $net_amount = 0;
    foreach($_POST['quantity'] as $key => $qty) {
        if ($qty > 0) {
            $net_amount += $qty * $_POST['unit_price'][$key];
        }
    }

    $total_paid = 0;
    if (isset($_POST['payment_method'])) {
        foreach($_POST['amount_paid'] as $paid_amount) {
            if (!empty($paid_amount)) {
                $total_paid += (float)$paid_amount;
            }
        }
    }

    $balance_due = $net_amount - $total_paid;
    $status = ($balance_due <= 0) ? 'Completed' : 'Credit';

    $conn->begin_transaction();
    try {
        // 1. Create sales_invoices record
        $sql_invoice = "INSERT INTO sales_invoices (invoice_number, invoice_date, net_amount, gross_amount, total_tax, total_paid, balance_due, user_id, customer_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $invoice_number = "INV-" . time();

        $stmt_invoice = $conn->prepare($sql_invoice);
        $stmt_invoice->bind_param("ssddddisss", $invoice_number, $invoice_date, $net_amount, $net_amount, $total_tax, $total_paid, $balance_due, $user_id, $customer_name, $status);
        $stmt_invoice->execute();
        $sales_invoice_id = $stmt_invoice->insert_id;

        // 2. Create payments records for each partial payment
        if (isset($_POST['payment_method'])) {
            foreach ($_POST['payment_method'] as $key => $method) {
                $amount = (float)$_POST['amount_paid'][$key];
                if ($amount > 0) {
                    $sql_payment = "INSERT INTO payments (invoice_id, payment_method, amount_paid, payment_date) VALUES (?, ?, ?, ?)";
                    $stmt_payment = $conn->prepare($sql_payment);
                    $stmt_payment->bind_param("isds", $sales_invoice_id, $method, $amount, $invoice_date);
                    $stmt_payment->execute();
                }
            }
        }

        // 3. Loop through cart items and update stock
        foreach ($_POST['product_id'] as $key => $product_id) {
            $quantity = $_POST['quantity'][$key];
            if ($quantity <= 0) continue; // Skip items with no quantity

            $batch_id = $_POST['batch_id'][$key];
            $unit_price = $_POST['unit_price'][$key];

            $sql_item = "INSERT INTO invoice_items (sales_invoice_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiiid", $sales_invoice_id, $product_id, $batch_id, $quantity, $unit_price);
            $stmt_item->execute();

            $sql_stock = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("iii", $quantity, $batch_id, $quantity);
            $stmt_stock->execute();
            if ($stmt_stock->affected_rows == 0) throw new Exception("Insufficient stock.");

            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'OUT - Sale', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $sale_qty_negative = -$quantity;
            $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $sales_invoice_id, $sale_qty_negative);
            $stmt_ledger->execute();
        }

        $conn->commit();
        $success_message = "Sale processed! Invoice: " . $invoice_number;
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to process sale: " . $e->getMessage();
    }
}

// Fetch products for search
$products = [];
$sql_products = "SELECT p.id, p.product_name, p.mrp, SUM(sb.current_qty) as total_stock FROM products p JOIN stock_batches sb ON p.id = sb.product_id WHERE p.is_active = TRUE AND sb.current_qty > 0 AND sb.expiry_date > CURDATE() GROUP BY p.id ORDER BY p.product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Point of Sale (POS) - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #product-search-results { position: absolute; background: white; border: 1px solid #ccc; z-index: 1000; width: 100%; }
        #product-search-results .list-group-item:hover { background: #f0f0f0; cursor: pointer; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Point of Sale</h2>
    <?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
    <?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
    <form action="pos.php" method="post" id="pos-form">
        <?php csrf_input(); ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card"><div class="card-header">Billing Items</div>
                    <div class="card-body">
                        <div class="mb-3 position-relative"><input type="text" id="product-search" class="form-control" placeholder="Search for products..."><div id="product-search-results"></div></div>
                        <table class="table" id="billing-cart"><thead><tr><th>Product</th><th>Batch</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr></thead><tbody></tbody></table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-header">Payment</div>
                    <div class="card-body">
                        <div class="mb-3"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" value="Walk-in"></div>
                        <h4 class="mb-3">Bill Total: <span id="cart-total">0.00</span></h4>
                        <div id="payment-methods">
                            <div class="row payment-row mb-2">
                                <div class="col-6"><select name="payment_method[]" class="form-control"><option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option></select></div>
                                <div class="col-6"><input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid-input" placeholder="Amount"></div>
                            </div>
                        </div>
                        <button type="button" id="add-payment-btn" class="btn btn-sm btn-info mb-3">Add Payment Method</button>
                        <hr>
                        <h5 class="mb-3">Total Paid: <span id="total-paid">0.00</span></h5>
                        <h5 class="mb-3">Balance Due: <span id="balance-due">0.00</span></h5>
                        <button type="submit" name="process_sale" class="btn btn-success w-100">Process Sale</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
<script>
    const products = <?php echo json_encode($products); ?>;
    document.getElementById('product-search').addEventListener('keyup', function() {
        // ... search logic from previous version ...
    });
    // ... fetchBatches and addToCart logic from previous version ...

    document.getElementById('add-payment-btn').addEventListener('click', function() {
        const paymentMethodsContainer = document.getElementById('payment-methods');
        const newPaymentRow = document.createElement('div');
        newPaymentRow.className = 'row payment-row mb-2';
        newPaymentRow.innerHTML = `
            <div class="col-6"><select name="payment_method[]" class="form-control"><option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option></select></div>
            <div class="col-5"><input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid-input" placeholder="Amount"></div>
            <div class="col-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.payment-row').remove(); updatePaymentTotals();">X</button></div>
        `;
        paymentMethodsContainer.appendChild(newPaymentRow);
    });

    document.querySelector('.card-body').addEventListener('input', function(e) {
        if (e.target.classList.contains('amount-paid-input')) {
            updatePaymentTotals();
        }
    });

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const rowTotal = quantity * price;
            row.querySelector('.total-price').textContent = rowTotal.toFixed(2);
            total += rowTotal;
        });
        document.getElementById('cart-total').textContent = total.toFixed(2);
        updatePaymentTotals();
    }

    function updatePaymentTotals() {
        let totalPaid = 0;
        document.querySelectorAll('.amount-paid-input').forEach(input => {
            totalPaid += parseFloat(input.value) || 0;
        });
        document.getElementById('total-paid').textContent = totalPaid.toFixed(2);

        const billTotal = parseFloat(document.getElementById('cart-total').textContent) || 0;
        const balanceDue = billTotal - totalPaid;
        document.getElementById('balance-due').textContent = balanceDue.toFixed(2);
    }
</script>
</body>
</html>