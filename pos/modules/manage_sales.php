<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1, 2]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Manage Sales Invoices</h1>

<div class="card">
    <div class="card-header"><a href="pos.php" class="btn btn-primary">Create New Invoice (POS)</a></div>
    <div class="card-body">
        <!-- ... (table) ... -->
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>