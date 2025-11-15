<div class="sidebar p-3 border-end">
    <h4 class="mb-3">Navigation</h4>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>

        <h5 class="mt-3">Sales & Billing</h5>
        <a href="pos.php" class="list-group-item list-group-item-action">Point of Sale (POS)</a>
        <a href="manage_sales.php" class="list-group-item list-group-item-action">Manage Invoices</a>
        <a href="add_sales_return.php" class="list-group-item list-group-item-action">Sales Return</a>

        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == 'admin'): // Admin-only links ?>
            <h5 class="mt-3">Purchase & Stock</h5>
            <a href="add_purchase.php" class="list-group-item list-group-item-action">Add Purchase Bill</a>
            <a href="manage_purchases.php" class="list-group-item list-group-item-action">Manage Purchases</a>
            <a href="add_purchase_return.php" class="list-group-item list-group-item-action">Purchase Return</a>
            <a href="add_stock_adjustment.php" class="list-group-item list-group-item-action">Stock Adjustment</a>

            <h5 class="mt-3">Management</h5>
            <a href="manage_products.php" class="list-group-item list-group-item-action">Manage Products</a>
            <a href="manage_customers.php" class="list-group-item list-group-item-action">Manage Customers</a>
            <a href="manage_suppliers.php" class="list-group-item list-group-item-action">Manage Suppliers</a>
            <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>

            <h5 class="mt-3">Accounts & Reports</h5>
            <a href="add_voucher.php" class="list-group-item list-group-item-action">Voucher Entry</a>
            <a href="report_collection.php" class="list-group-item list-group-item-action">Collection Report</a>
            <a href="report_sales_register.php" class="list-group-item list-group-item-action">Sales Register</a>
            <a href="report_purchase_register.php" class="list-group-item list-group-item-action">Purchase Register</a>
            <a href="report_supplier_ledger.php" class="list-group-item list-group-item-action">Supplier Ledger</a>
        <?php endif; ?>

        <h5 class="mt-3">Inventory Reports</h5>
        <a href="report_current_stock.php" class="list-group-item list-group-item-action">Current Stock</a>
        <a href="report_stock_ledger.php" class="list-group-item list-group-item-action">Stock Ledger</a>
        <a href="report_low_stock_expiry.php" class="list-group-item list-group-item-action">Low Stock / Expiry</a>
    </div>
</div>

<div class="content">