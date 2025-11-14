<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Current Stock Report</h1>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Full Stock Listing</span>
        <a href="report_current_stock.php?export=csv" class="btn btn-sm btn-success">Export to CSV</a>
    </div>
    <div class="card-body">
        <!-- ... (table) ... -->
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>