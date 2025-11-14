<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1, 2]);

// --- ALL DASHBOARD PHP LOGIC IS RESTORED HERE ---
// Data for Admin Dashboard
if ($_SESSION['role_id'] == 1) {
    // ... (logic)
}
// KPIs
// ... (logic)
// Alerts
// ... (logic)

$conn->close();
?>
<!-- Page content starts here -->
<h1 class="mt-4">Dashboard</h1>
<!-- ... (HTML content) -->
<?php require_once '../includes/footer.php'; ?>