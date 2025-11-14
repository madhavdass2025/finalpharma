<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1]);

$suppliers = [];
$sql_suppliers = "SELECT id, supplier_name FROM suppliers ORDER BY supplier_name";
if ($result = $conn->query($sql_suppliers)) {
    while ($row = $result->fetch_assoc()) $suppliers[] = $row;
}

$ledger_data = [];
$selected_supplier = '';
if (isset($_GET['supplier_id']) && !empty($_GET['supplier_id'])) {
    $supplier_id = intval($_GET['supplier_id']);
    $selected_supplier = $supplier_id;
    $sql_ledger = "
        SELECT
            'Purchase' as transaction_type, bill_date as date, bill_number as reference, total_amount as amount
        FROM purchase_bills
        WHERE supplier_id = ? AND status = 'Completed'
        UNION ALL
        SELECT
            'Return' as transaction_type, return_date as date, CONCAT('Return ID: ', id) as reference, -total_amount as amount
        FROM purchase_returns
        WHERE supplier_id = ?
        ORDER BY date ASC
    ";
    if ($stmt = $conn->prepare($sql_ledger)) {
        $stmt->bind_param("ii", $supplier_id, $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ledger_data[] = $row;
        }
    }
}
$conn->close();
?>

<h1 class="mt-4">Supplier Ledger (Accounts Payable)</h1>
<div class="card mb-4">
    <div class="card-body">
        <form action="report_supplier_ledger.php" method="get">
            <div class="row">
                <div class="col-md-5">
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- Select a Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo ($selected_supplier == $supplier['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">View Ledger</button></div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($selected_supplier)): ?>
<div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Transaction</th><th>Reference</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
                <?php $balance = 0; if (empty($ledger_data)): ?>
                    <tr><td colspan="6" class="text-center">No transactions found for this supplier.</td></tr>
                <?php else: foreach ($ledger_data as $row):
                        $debit = $row['amount'] > 0 ? $row['amount'] : 0;
                        $credit = $row['amount'] < 0 ? -$row['amount'] : 0;
                        $balance += $row['amount'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['reference']); ?></td>
                        <td class="text-end"><?php echo number_format($debit, 2); ?></td>
                        <td class="text-end"><?php echo number_format($credit, 2); ?></td>
                        <td class="text-end"><?php echo number_format($balance, 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="fw-bold"><tr><td colspan="5" class="text-end">Closing Balance (Payable):</td><td class="text-end"><?php echo number_format($balance, 2); ?></td></tr></tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>