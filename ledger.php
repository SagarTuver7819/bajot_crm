<?php
$page_title = 'Party Ledger';
require_once 'includes/header.php';
$settings = get_settings();

$party_id = isset($_GET['party_id']) ? (int)$_GET['party_id'] : 0;
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

$transactions = [];
$opening_balance = 0;

if ($party_id) {
    $party = $conn->query("SELECT * FROM parties WHERE id=$party_id")->fetch_assoc();
    $opening_balance = $party['opening_balance'] ?? 0;

    // 1. Fetch Sales (Outwards)
    $sales = $conn->query("SELECT id, date, bill_no, total_amount as amount, 'Sales' as type, '' as description FROM outwards WHERE party_id=$party_id AND date BETWEEN '$from_date' AND '$to_date'");
    while($sale = $sales->fetch_assoc()) {
        $sale['debit'] = ($party['type'] == 'customer') ? $sale['amount'] : 0;
        $sale['credit'] = ($party['type'] == 'supplier') ? $sale['amount'] : 0;
        $transactions[] = $sale;
    }

    // 2. Fetch Purchases (Inwards)
    $purchases = $conn->query("SELECT id, date, bill_no, total_amount as amount, 'Purchase' as type, '' as description FROM inwards WHERE party_id=$party_id AND date BETWEEN '$from_date' AND '$to_date'");
    while($p = $purchases->fetch_assoc()) {
        $p['debit'] = 0; 
        $p['credit'] = $p['amount'];
        $transactions[] = $p;
    }

    // 3. Fetch Vouchers
    $vouchers = $conn->query("SELECT id, date, type as vtype, amount, description, 'Voucher' as type FROM vouchers WHERE party_id=$party_id AND date BETWEEN '$from_date' AND '$to_date'");
    while($v = $vouchers->fetch_assoc()) {
        $v['bill_no'] = "VCH-" . $v['id'];
        if ($v['vtype'] == 'receipt') {
            $v['debit'] = 0;
            $v['credit'] = $v['amount'];
        } else if ($v['vtype'] == 'payment') {
            $v['debit'] = $v['amount'];
            $v['credit'] = 0;
        } else {
            $v['debit'] = $v['amount'];
            $v['credit'] = 0;
        }
        $transactions[] = $v;
    }

    // Sort by date
    usort($transactions, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Party Ledger Report</h4>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-gold btn-sm"><i class="fa fa-print me-1"></i> Print Ledger</button>
        </div>
    </div>
</div>

<div class="card card-bajot mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label">Select Party (Customer/Supplier)</label>
                <select name="party_id" class="form-select border-secondary" required>
                    <option value="">-- Choose Party --</option>
                    <?php 
                    $ps = $conn->query("SELECT id, name, type FROM parties ORDER BY name ASC");
                    while($p = $ps->fetch_assoc()) {
                        $sel = ($party_id == $p['id']) ? 'selected' : '';
                        echo "<option value='{$p['id']}' $sel>{$p['name']} (".ucfirst($p['type']).")</option>";
                    }
                    ?>
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
            <div class="col-md-2">
                <button type="submit" class="btn btn-gold w-100">VIEW LEDGER <i class="fa fa-search ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

<?php if ($party_id): ?>
<style>
@media print {
    .sidebar, .navbar-top, .btn-gold, form, .btn-outline-gold { display: none !important; }
    .card-bajot:not(.ledger-card) { display: none !important; }
    .ledger-card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .card-header { display: none !important; }
    .card-body { padding: 0 !important; }
    body { background: white !important; color: black !important; font-size: 12px; }
    .print-only { display: block !important; }
    .table-ledger { border: 1px solid #333 !important; width: 100%; border-collapse: collapse; }
    .table-ledger th, .table-ledger td { border: 1px solid #333 !important; padding: 5px; }
    .ledger-header { text-align: center; margin-bottom: 20px; }
}
.print-only { display: none; }
.bg-dr { background: rgba(255, 59, 48, 0.05); }
.bg-cr { background: rgba(76, 217, 100, 0.05); }
</style>

<div class="print-only ledger-header">
    <?php if (!empty($settings['company_logo']) && file_exists($settings['company_logo'])): ?>
        <img src="<?php echo $settings['company_logo']; ?>" alt="Logo" style="height: 60px; margin-bottom: 10px;">
    <?php endif; ?>
    <h2><?php echo strtoupper($settings['company_name'] ?? ''); ?></h2>
    <p><?php echo $settings['company_address'] ?? ''; ?></p>
    <hr>
    <h4>PARTY LEDGER: <?php echo strtoupper($party['name']); ?></h4>
    <p>Statement Period: <?php echo date('d-m-Y', strtotime($from_date)); ?> To <?php echo date('d-m-Y', strtotime($to_date)); ?></p>
</div>

<div class="card card-bajot ledger-card">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between">
        <h5 class="fw-bold mb-0 text-theme">Ledger for: <?php echo $party['name']; ?></h5>
        <div class="text-theme">Opening Balance: <strong><?php echo format_currency($opening_balance); ?></strong></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-ledger w-100">
                <thead class="table-dark">
                    <tr>
                        <th width="12%">Date</th>
                        <th>Particulars / Transaction Type</th>
                        <th width="15%">Reference</th>
                        <th width="15%" class="text-end">Debit (Dr)</th>
                        <th width="15%" class="text-end">Credit (Cr)</th>
                        <th width="15%" class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light text-dark">
                        <td colspan="5" class="text-end fw-bold">Opening Balance</td>
                        <td class="text-end fw-bold"><?php echo format_currency($opening_balance); ?></td>
                    </tr>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted small">No transactions found for the selected period.</td>
                    </tr>
                    <?php else: ?>
                    <?php 
                    $running_balance = $opening_balance;
                    foreach ($transactions as $tr): 
                        $running_balance += ($tr['debit'] - $tr['credit']);
                    ?>
                    <tr class="<?php echo ($tr['debit'] > 0) ? 'bg-dr' : 'bg-cr'; ?>">
                        <td><?php echo date('d-m-Y', strtotime($tr['date'])); ?></td>
                        <td>
                            <strong><?php echo $tr['type']; ?></strong>
                            <div class="extra-small small text-muted"><?php echo htmlspecialchars($tr['description'] ?? ''); ?></div>
                        </td>
                        <td><?php echo $tr['bill_no']; ?></td>
                        <td class="text-end text-danger"><?php echo $tr['debit'] > 0 ? format_currency($tr['debit']) : '-'; ?></td>
                        <td class="text-end text-success"><?php echo $tr['credit'] > 0 ? format_currency($tr['credit']) : '-'; ?></td>
                        <td class="text-end fw-bold"><?php echo format_currency($running_balance); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Closing Balance</td>
                        <td class="text-end fw-bold"><?php echo format_currency($running_balance); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="text-center p-5 text-muted">
        <i class="fa fa-book fa-3x mb-3"></i>
        <p>Please select a party to view their transaction ledger.</p>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
