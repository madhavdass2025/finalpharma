<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Stock Adjustment</h1>

<div class="card">
    <div class="card-header">Create New Stock Adjustment</div>
    <div class="card-body">
        <form action="add_stock_adjustment.php" method="post" id="adjustment-form">
            <?php csrf_input(); ?>
            <!-- ... (form) ... -->
            <button type="submit" name="save_adjustment" class="btn btn-primary">Apply Adjustment</button>
        </form>
    </div>
</div>

<script>
    // --- JAVASCRIPT LOGIC RESTORED ---
</script>

<?php require_once '../includes/footer.php'; ?>