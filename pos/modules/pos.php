<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Billing/Pharmacists

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Sale processing logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale'])) {
    check_csrf();
    $customer_name = $_POST['customer_name'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['id'];
    $invoice_date = date('Y-m-d');

    $total_amount = 0;
    foreach($_POST['quantity'] as $key => $qty) {
        $total_amount += $qty * $_POST['unit_price'][$key];
    }

    $conn->begin_transaction();
    try {
        // 1. Create sales_invoices record
        $sql_invoice = "INSERT INTO sales_invoices (invoice_number, invoice_date, net_amount, gross_amount, total_tax, user_id, customer_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed')";
        $invoice_number = "INV-" . time(); // Simple invoice number
        $gross_amount = $total_amount; // Assuming no complex tax for now
        $total_tax = 0.00;

        $stmt_invoice = $conn->prepare($sql_invoice);
        $stmt_invoice->bind_param("ssdddis", $invoice_number, $invoice_date, $total_amount, $gross_amount, $total_tax, $user_id, $customer_name);
        $stmt_invoice->execute();
        $sales_invoice_id = $stmt_invoice->insert_id;
        $stmt_invoice->close();

        // 2. Create payments record
        $sql_payment = "INSERT INTO payments (invoice_id, payment_method, amount_paid, payment_date) VALUES (?, ?, ?, ?)";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("isds", $sales_invoice_id, $payment_method, $total_amount, $invoice_date);
        $stmt_payment->execute();
        $stmt_payment->close();

        // 3. Loop through cart items
        foreach ($_POST['product_id'] as $key => $product_id) {
            $batch_id = $_POST['batch_id'][$key];
            $quantity = $_POST['quantity'][$key];
            $unit_price = $_POST['unit_price'][$key];

            // Insert into invoice_items
            $sql_item = "INSERT INTO invoice_items (sales_invoice_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiiid", $sales_invoice_id, $product_id, $batch_id, $quantity, $unit_price);
            $stmt_item->execute();
            $stmt_item->close();

            // Decrement stock in stock_batches
            $sql_stock = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("iii", $quantity, $batch_id, $quantity);
            $stmt_stock->execute();
            if ($stmt_stock->affected_rows == 0) {
                throw new Exception("Insufficient stock for a product in the cart.");
            }
            $stmt_stock->close();

            // Create stock_ledger entry
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'OUT - Sale', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $sale_qty_negative = -$quantity;
            $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $sales_invoice_id, $sale_qty_negative);
            $stmt_ledger->execute();
            $stmt_ledger->close();
        }

        $conn->commit();
        $success_message = "Sale processed successfully! Invoice Number: " . $invoice_number;
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to process sale: " . $e->getMessage();
    }
}


// Fetch products for the search functionality (moved this part down)
$products = [];
$sql_products = "
    SELECT p.id, p.product_name, p.mrp, SUM(sb.current_qty) as total_stock
    FROM products p
    JOIN stock_batches sb ON p.id = sb.product_id
    WHERE p.is_active = TRUE AND sb.current_qty > 0 AND sb.expiry_date > CURDATE()
    GROUP BY p.id
    ORDER BY p.product_name
";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();
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
        #product-search-results .list-group-item { cursor: pointer; }
        #product-search-results .list-group-item:hover { background: #f0f0f0; }
        .cart-item-row input { max-width: 80px; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <h2>Point of Sale</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="pos.php" method="post" id="pos-form">
        <?php csrf_input(); ?>
        <div class="row">
            <!-- Left Side: Billing -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Billing Items</div>
                    <div class="card-body">
                        <div class="mb-3 position-relative">
                            <input type="text" id="product-search" class="form-control" placeholder="Search for products...">
                            <div id="product-search-results"></div>
                        </div>
                        <table class="table" id="billing-cart">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Payment -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Payment</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" value="Walk-in">
                        </div>
                        <div class="mb-3">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                        <h4 class="mb-3">Total: <span id="cart-total">0.00</span></h4>
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
        const query = this.value.toLowerCase();
        const resultsContainer = document.getElementById('product-search-results');
        resultsContainer.innerHTML = '';
        if (query.length < 2) return;

        const filteredProducts = products.filter(p => p.product_name.toLowerCase().includes(query));

        let resultsHtml = '<ul class="list-group">';
        filteredProducts.forEach(p => {
            resultsHtml += `<li class="list-group-item" onclick="fetchBatches(${p.id}, '${p.product_name}')">${p.product_name} (Stock: ${p.total_stock})</li>`;
        });
        resultsHtml += '</ul>';
        resultsContainer.innerHTML = resultsHtml;
    });

    async function fetchBatches(productId, productName) {
        document.getElementById('product-search').value = '';
        document.getElementById('product-search-results').innerHTML = '';

        try {
            const response = await fetch(`api_get_batches.php?product_id=${productId}`);
            const result = await response.json();

            if (result.status === 'success' && result.data.length > 0) {
                const batch = result.data[0]; // FEFO
                addToCart(productId, productName, batch);
            } else {
                alert(result.message || 'No stock found for this product.');
            }
        } catch (error) {
            console.error('Error fetching batches:', error);
            alert('Failed to fetch product batch details.');
        }
    }

    function addToCart(productId, productName, batch) {
        const cartBody = document.querySelector("#billing-cart tbody");

        if (document.querySelector(`input[name='batch_id[]'][value='${batch.id}']`)) {
            alert('This product batch is already in the cart.');
            return;
        }

        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.innerHTML = `
            <td>
                ${productName}
                <input type="hidden" name="product_id[]" value="${productId}">
                <input type="hidden" name="batch_id[]" value="${batch.id}">
            </td>
            <td>${batch.batch_number}</td>
            <td>${batch.expiry_date}</td>
            <td><input type="number" name="quantity[]" class="form-control quantity-input" value="1" min="1" max="${batch.current_qty}" required></td>
            <td><input type="number" step="0.01" name="unit_price[]" class="form-control price-input" value="${batch.mrp}" required></td>
            <td class="total-price">${batch.mrp}</td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotal();">X</button></td>
        `;
        cartBody.appendChild(row);
        updateTotal();

        row.querySelector('.quantity-input').addEventListener('input', updateTotal);
        row.querySelector('.price-input').addEventListener('input', updateTotal);
    }

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
    }
</script>

</body>
</html>