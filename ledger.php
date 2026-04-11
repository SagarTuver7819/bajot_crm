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
        $sale['debit'] = $sale['amount'];
        $sale['credit'] = 0;
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

    // 4. Fetch Kasars
    $dept_id = (int)$_SESSION['dept_id'];
    $kasars = $conn->query("SELECT id, date, amount, type as ktype, description, 'Kasar' as type FROM kasars WHERE dept_id=$dept_id AND party_id=$party_id AND date BETWEEN '$from_date' AND '$to_date'");
    if ($kasars) {
        while($k = $kasars->fetch_assoc()) {
            $k['bill_no'] = "KSR-" . $k['id'];
            if ($k['ktype'] == 'allowed') {
                $k['debit'] = 0;
                $k['credit'] = $k['amount'];
            } else {
                $k['debit'] = $k['amount'];
                $k['credit'] = 0;
            }
            $transactions[] = $k;
        }
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
            <?php if ($party_id): 
                // Base URL for link sharing
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $host = $_SERVER['HTTP_HOST'];
                $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $base_url = $protocol . "://" . $host . $path . "/";
                
                // Get mobile for sharing
                $pm = $conn->query("SELECT mobile, name FROM parties WHERE id=$party_id")->fetch_assoc();
                if ($pm['mobile']):
                    $wa_msg = urlencode("Hello " . $pm['name'] . ",\n\nYour ledger statement is ready.\nPeriod: " . date('d-m-Y', strtotime($from_date)) . " to " . date('d-m-Y', strtotime($to_date)) . "\n\nView here: " . $base_url . "ledger.php?party_id=" . $party_id . "&from_date=" . $from_date . "&to_date=" . $to_date);
            ?>
                <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $pm['mobile']); ?>?text=<?php echo $wa_msg; ?>" target="_blank" class="btn btn-outline-success btn-sm no-print">
                    <i class="fa-brands fa-whatsapp me-1"></i> Share via WhatsApp
                </a>
            <?php endif; endif; ?>
        </div>
    </div>
</div>

<div class="card card-bajot mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-2">
                <label class="form-label">Filter by Type</label>
                <select id="typeFilter" class="form-select border-secondary">
                    <option value="all">All Types</option>
                    <option value="customer">Customer Only</option>
                    <option value="supplier">Supplier Only</option>
                    <option value="both">Both Only</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Select Party</label>
                <select name="party_id" id="partySelect" class="form-select border-secondary" required>
                    <option value="">-- Choose Party --</option>
                    <?php 
                    $ps = $conn->query("SELECT id, name, type FROM parties ORDER BY name ASC");
                    while($p = $ps->fetch_assoc()) {
                        $sel = ($party_id == $p['id']) ? 'selected' : '';
                        echo "<option value='{$p['id']}' data-type='{$p['type']}' $sel>{$p['name']} (".ucfirst($p['type']).")</option>";
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
        <img src="<?php echo $settings['company_logo']; ?>" alt="Logo" style="height: 100px; margin-bottom: 10px;">
    <?php else: ?>
        <h2><?php echo strtoupper($settings['company_name'] ?? ''); ?></h2>
    <?php endif; ?>
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
                    <?php 
                    $running_balance = $opening_balance;
                    if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted small">No transactions found for the selected period.</td>
                    </tr>
                    <?php else: ?>
                    <?php 
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
                        <td class="text-end fw-bold">
                            <?php echo format_currency($running_balance); ?>
                            <?php if (abs($running_balance) > 0): ?>
                                <button type="button" class="btn btn-sm btn-info ms-2 no-print" onclick="openKasarModal(<?php echo $running_balance; ?>)">
                                    <i class="fa fa-plus-circle me-1"></i> Add to Kasar
                                </button>
                            <?php endif; ?>
                        </td>
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

<!-- Kasar Modal -->
<div class="modal fade" id="kasarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark-card border-secondary">
            <div class="modal-header">
                <h5 class="modal-title">Add to Kasar (Discount/Adjustment)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="kasarForm">
                <div class="modal-body">
                    <input type="hidden" name="party_id" value="<?php echo $party_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" id="kasarAmount" class="form-control" required>
                    </div>
                    <input type="hidden" name="type" id="kasarType">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Rounding off balance"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold">Save Kasar Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
        document.getElementById('typeFilter').addEventListener('change', function() {
            const filterValue = this.value;
            const options = document.querySelectorAll('#partySelect option');
            options.forEach(opt => {
                if (opt.value === "") return;
                const type = opt.getAttribute('data-type');
                
                let show = false;
                if (filterValue === 'all') show = true;
                else if (filterValue === 'customer' && (type === 'customer' || type === 'both')) show = true;
                else if (filterValue === 'supplier' && (type === 'supplier' || type === 'both')) show = true;
                else if (filterValue === 'both' && type === 'both') show = true;

                opt.style.display = show ? 'block' : 'none';
            });
            // Reset selection if hidden
            const selectedOpt = document.querySelector('#partySelect option:checked');
            if (selectedOpt && selectedOpt.style.display === 'none') {
                document.getElementById('partySelect').value = "";
            }
        });

    // Trigger filter on load to match selected party
    window.addEventListener('DOMContentLoaded', () => {
         const partySelect = document.getElementById('partySelect');
         const selectedOpt = partySelect.querySelector('option:checked');
         if (selectedOpt && selectedOpt.value !== "") {
            const type = selectedOpt.getAttribute('data-type');
            document.getElementById('typeFilter').value = type;
         }
    });

function openKasarModal(balance) {
    const amount = Math.abs(balance);
    const type = balance > 0 ? 'allowed' : 'received';
    
    document.getElementById('kasarAmount').value = amount;
    document.getElementById('kasarType').value = type;
    
    new bootstrap.Modal(document.getElementById('kasarModal')).show();
}

document.getElementById('kasarForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add_kasar');

    fetch('ajax_kasar.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error saving kasar');
        }
    });
};
</script>

<?php require_once 'includes/footer.php'; ?>
