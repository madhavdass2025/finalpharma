<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Manage Purchase Bills</h1>

<div class="card">
    <div class="card-header"><a href="add_purchase.php" class="btn btn-primary">Add New Purchase Bill</a></div>
    <div class="card-body">
        <!-- ... (table) ... -->
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>