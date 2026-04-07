<?php
$page_title = 'Reports Module';
require_once 'includes/header.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'sales';

// Fetch Totals
if ($report_type == 'sales') {
    $res = $conn->query("SELECT o.*, p.name as customer_name FROM outwards o JOIN parties p ON o.party_id = p.id WHERE o.date BETWEEN '$from_date' AND '$to_date' AND o.dept_id = " . (int)$_SESSION['dept_id'] . " ORDER BY o.date DESC");
    $total_val = $conn->query("SELECT SUM(total_amount) FROM outwards WHERE (date BETWEEN '$from_date' AND '$to_date') AND dept_id = " . (int)$_SESSION['dept_id'])->fetch_row()[0] ?? 0;
} elseif ($report_type == 'purchase') {
    $res = $conn->query("SELECT i.*, p.name as supplier_name FROM inwards i JOIN parties p ON i.party_id = p.id WHERE i.date BETWEEN '$from_date' AND '$to_date' AND i.dept_id = " . (int)$_SESSION['dept_id'] . " ORDER BY i.date DESC");
    $total_val = $conn->query("SELECT SUM(total_amount) FROM inwards WHERE (date BETWEEN '$from_date' AND '$to_date') AND dept_id = " . (int)$_SESSION['dept_id'])->fetch_row()[0] ?? 0;
} else {
    $res = $conn->query("SELECT e.*, ec.name as cat_name FROM expenses e JOIN expense_categories ec ON e.category_id = ec.id WHERE e.date BETWEEN '$from_date' AND '$to_date' AND e.dept_id = " . (int)$_SESSION['dept_id'] . " ORDER BY e.date DESC");
    $total_val = $conn->query("SELECT SUM(amount) FROM expenses WHERE (date BETWEEN '$from_date' AND '$to_date') AND dept_id = " . (int)$_SESSION['dept_id'])->fetch_row()[0] ?? 0;
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Business Reports</h4>
        <div class="d-flex gap-2">
            <a href="export_report.php?type=<?php echo $report_type; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&format=excel" class="btn btn-outline-gold btn-sm"><i class="fa fa-file-excel me-1"></i> Export Excel</a>
            <a href="export_report.php?type=<?php echo $report_type; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&format=pdf" target="_blank" class="btn btn-outline-gold btn-sm"><i class="fa fa-file-pdf me-1"></i> Export PDF</a>
        </div>
    </div>
</div>

<div class="card card-bajot mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select bg-dark text-white border-secondary">
                    <option value="sales" <?php echo ($report_type == 'sales') ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="purchase" <?php echo ($report_type == 'purchase') ? 'selected' : ''; ?>>Purchase Report</option>
                    <option value="expense" <?php echo ($report_type == 'expense') ? 'selected' : ''; ?>>Expense Report</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-gold w-100">FILTER REPORT <i class="fa fa-filter ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

<?php if ($report_type == 'expense'): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <h6 class="text-secondary-themed fw-bold text-uppercase small"><i class="fa fa-layer-group me-2"></i>Category-wise Summary</h6>
    </div>
    <?php 
    $cat_summ = $conn->query("SELECT ec.name, SUM(e.amount) as total FROM expenses e JOIN expense_categories ec ON e.category_id = ec.id WHERE e.date BETWEEN '$from_date' AND '$to_date' AND e.dept_id = " . (int)$_SESSION['dept_id'] . " GROUP BY ec.id ORDER BY total DESC");
    while($cs = $cat_summ->fetch_assoc()):
    ?>
    <div class="col-md-3 col-6">
        <div class="card card-bajot border-0 shadow-sm p-3" style="border-left: 3px solid var(--gold) !important;">
            <p class="text-secondary-themed extra-small text-uppercase mb-1"><?php echo htmlspecialchars($cs['name']); ?></p>
            <h5 class="fw-bold mb-0"><?php echo format_currency($cs['total']); ?></h5>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-bajot p-4 text-center">
            <h6 class="text-muted mb-2">Grand Total (Filtered)</h6>
            <h2 class="fw-bold" style="color: var(--gold);"><?php echo format_currency($total_val); ?></h2>
        </div>
    </div>
</div>

<div class="card card-bajot">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom datatable w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th><?php echo ($report_type == 'expense' ? 'Category' : 'Bill No.'); ?></th>
                        <th><?php echo ($report_type == 'expense' ? 'Description' : 'Party Name'); ?></th>
                        <th>Amount (Total)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                        <td class="fw-bold"><?php echo ($report_type == 'expense' ? htmlspecialchars($row['cat_name']) : '#' . $row['bill_no']); ?></td>
                        <td><?php echo ($report_type == 'expense' ? htmlspecialchars($row['description']) : ($row['customer_name'] ?? $row['supplier_name'] ?? 'N/A')); ?></td>
                        <td class="fw-bold"><?php echo format_currency($report_type == 'expense' ? $row['amount'] : $row['total_amount']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($report_type != 'expense'): ?>
                                <a href="print_invoice.php?type=<?php echo $report_type; ?>&id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" target="_blank"><i class="fa fa-print"></i></a>
                                <?php endif; ?>
                                <a href="<?php 
                                    if($report_type == 'sales') echo 'outward_crud.php';
                                    elseif($report_type == 'purchase') echo 'inward_crud.php';
                                    else echo 'expenses.php'; 
                                ?>?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold"><i class="fa fa-edit"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
