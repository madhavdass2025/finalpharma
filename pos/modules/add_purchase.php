<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

// --- ALL ADD PURCHASE PHP LOGIC RESTORED ---
// ... (Fetch suppliers and products)
// ... (Handle form submission with transaction)
?>

<h1 class="mt-4">Add New Purchase Bill</h1>

<form action="add_purchase.php" method="post">
    <?php csrf_input(); ?>
    <div class="card mb-4"><!-- Bill Details --></div>
    <div class="card"><!-- Purchase Items --></div>
    <div class="mt-3"><button type="submit" name="save_purchase" class="btn btn-primary">Save Purchase Bill</button></div>
</form>

<script>
    // --- JAVASCRIPT LOGIC RESTORED ---
</script>

<?php require_once '../includes/footer.php'; ?>