<?php
$page_title = 'Reports Module';
require_once 'includes/header.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'sales';

// Fetch Totals
if ($report_type == 'sales') {
    $res = $conn->query("SELECT o.*, p.name as customer_name FROM outwards o JOIN parties p ON o.party_id = p.id WHERE o.date BETWEEN '$from_date' AND '$to_date' ORDER BY o.date DESC");
    $total_val = $conn->query("SELECT SUM(total_amount) FROM outwards WHERE date BETWEEN '$from_date' AND '$to_date'")->fetch_row()[0] ?? 0;
} else {
    $res = $conn->query("SELECT i.*, p.name as supplier_name FROM inwards i JOIN parties p ON i.party_id = p.id WHERE i.date BETWEEN '$from_date' AND '$to_date' ORDER BY i.date DESC");
    $total_val = $conn->query("SELECT SUM(total_amount) FROM inwards WHERE date BETWEEN '$from_date' AND '$to_date'")->fetch_row()[0] ?? 0;
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Business Reports</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-gold btn-sm"><i class="fa fa-file-excel me-1"></i> Export Excel</button>
            <button class="btn btn-outline-gold btn-sm"><i class="fa fa-file-pdf me-1"></i> Export PDF</button>
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
                        <th>Bill No.</th>
                        <th>Party Name</th>
                        <th>Amount (Total)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                        <td class="fw-bold">#<?php echo $row['bill_no']; ?></td>
                        <td><?php echo $row['customer_name'] ?? $row['supplier_name'] ?? 'N/A'; ?></td>
                        <td class="fw-bold"><?php echo format_currency($row['total_amount']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="print_invoice.php?type=<?php echo $report_type; ?>&id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" target="_blank"><i class="fa fa-print"></i></a>
                                <a href="<?php echo ($report_type == 'sales' ? 'outward_crud.php' : 'inward_crud.php'); ?>?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold"><i class="fa fa-edit"></i></a>
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
