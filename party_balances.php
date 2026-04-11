<?php
$page_title = 'Party Balances (Outstanding)';
require_once 'includes/header.php';

// Fetch all parties with their calculated balances (Matching Ledger Logic)
$dept_id = (int)$_SESSION['dept_id'];
$sql = "SELECT 
            p.id, 
            p.name, 
            p.mobile,
            p.type as party_type,
            (
                COALESCE(p.opening_balance, 0) +
                COALESCE((SELECT SUM(total_amount) FROM outwards WHERE party_id = p.id), 0) +
                COALESCE((SELECT SUM(amount) FROM vouchers WHERE party_id = p.id AND type IN ('payment', 'expense')), 0) +
                COALESCE((SELECT SUM(amount) FROM kasars WHERE party_id = p.id AND type = 'received' AND dept_id = $dept_id), 0) -
                COALESCE((SELECT SUM(total_amount) FROM inwards WHERE party_id = p.id), 0) -
                COALESCE((SELECT SUM(amount) FROM vouchers WHERE party_id = p.id AND type = 'receipt'), 0) -
                COALESCE((SELECT SUM(amount) FROM kasars WHERE party_id = p.id AND type = 'allowed' AND dept_id = $dept_id), 0)
            ) as balance
        FROM parties p
        ORDER BY p.name ASC";

$result = $conn->query($sql);

$total_receivable = 0;
$total_payable = 0;
$parties_data = [];

while ($row = $result->fetch_assoc()) {
    $balance = (float)$row['balance'];
    if ($balance > 0.01) {
        $total_receivable += $balance;
    } elseif ($balance < -0.01) {
        $total_payable += abs($balance);
    }
    $parties_data[] = $row;
}
?>

<!-- Professional Print Header (Hidden on screen) -->
<div class="print-only-header d-none">
    <div class="text-center mb-4">
        <?php $s = get_settings(); ?>
        <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
            <img src="<?php echo $s['company_logo']; ?>" alt="Logo" style="max-height: 80px;" class="mb-2">
        <?php else: ?>
            <h2 class="mb-0 fw-bold"><?php echo $s['company_name'] ?? 'KAIZER CRM'; ?></h2>
        <?php endif; ?>
        <p class="mb-1"><?php echo $s['company_address'] ?? ''; ?></p>
        <?php if(!empty($s['company_gst'])): ?><p class="mb-1">GSTIN: <?php echo $s['company_gst']; ?></p><?php endif; ?>
        <hr class="my-3" style="border-top: 2px solid #000;">
        <h4 class="fw-bold">OUTSTANDING BALANCES REPORT</h4>
        <p class="small text-muted">Generated on: <?php echo date('d-m-Y h:i A'); ?> | Department: <?php echo $_SESSION['dept_name'] ?? 'All'; ?></p>
    </div>
</div>

<div class="row mb-4 no-print">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Party Balances (Outstanding)</h4>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fa fa-print me-1"></i> Print Report
            </button>
            <a href="party_crud.php" class="btn btn-gold">
                <i class="fa fa-users me-1"></i> Manage Parties
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards (Hidden on print if preferred, but usually good to keep) -->
<div class="row mb-4 g-3 no-print">
    <div class="col-md-4">
        <div class="card card-bajot border-start border-4 border-success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Receivable (Leva na)</p>
                        <h3 class="mb-0 text-success fw-bold"><?php echo format_currency($total_receivable); ?></h3>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success p-3 rounded">
                        <i class="fa fa-arrow-down-long fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-bajot border-start border-4 border-danger shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Payable (Apva na)</p>
                        <h3 class="mb-0 text-danger fw-bold"><?php echo format_currency($total_payable); ?></h3>
                    </div>
                    <div class="icon-box bg-danger bg-opacity-10 text-danger p-3 rounded">
                        <i class="fa fa-arrow-up-long fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php $net = $total_receivable - $total_payable; ?>
        <div class="card card-bajot border-start border-4 <?php echo $net >= 0 ? 'border-primary' : 'border-warning'; ?> shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Net Balance</p>
                        <h3 class="mb-0 <?php echo $net >= 0 ? 'text-primary' : 'text-warning'; ?> fw-bold"><?php echo format_currency(abs($net)); ?> <?php echo $net >= 0 ? '(Dr)' : '(Cr)'; ?></h3>
                    </div>
                    <div class="icon-box bg-<?php echo $net >= 0 ? 'primary' : 'warning'; ?> bg-opacity-10 text-<?php echo $net >= 0 ? 'primary' : 'warning'; ?> p-3 rounded">
                        <i class="fa fa-scale-balanced fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Table -->
<div class="card card-bajot border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom table-hover" id="balancesTable">
                <thead>
                    <tr>
                        <th style="width: 50%;">Party Name</th>
                        <th class="no-print">Type</th>
                        <th class="no-print">Mobile</th>
                        <th class="text-end" style="width: 25%;">Debit (Dr)</th>
                        <th class="text-end" style="width: 25%;">Credit (Cr)</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $p_count = 0;
                    foreach($parties_data as $party): 
                        $bal = (float)$party['balance'];
                        if (abs($bal) < 0.01) continue; // Skip zero balance
                        $p_count++;
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold party-name-cell"><?php echo htmlspecialchars($party['name']); ?></div>
                        </td>
                        <td class="no-print">
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <?php echo ucfirst($party['party_type']); ?>
                            </span>
                        </td>
                        <td class="no-print"><?php echo $party['mobile'] ?: '-'; ?></td>
                        <td class="text-end">
                            <?php if($bal > 0.01): ?>
                                <span class="<?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?> fw-bold dr-amount"><?php echo number_format($bal, 2); ?></span>
                            <?php else: ?>
                                <span class="text-muted opacity-25">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($bal < -0.01): ?>
                                <span class="<?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?> fw-bold cr-amount"><?php echo number_format(abs($bal), 2); ?></span>
                            <?php else: ?>
                                <span class="text-muted opacity-25">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <a href="ledger.php?party_id=<?php echo $party['id']; ?>" class="btn btn-sm btn-outline-gold" title="View Ledger">
                                <i class="fa fa-book"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-top-2">
                    <tr class="fw-bold bg-dark text-white table-total-row">
                        <td class="text-end py-2">GRAND TOTAL (<?php echo $p_count; ?> Parties):</td>
                        <td class="no-print"></td>
                        <td class="no-print"></td>
                        <td class="text-end py-2 dr-total" style="font-size: 1rem;"><?php echo number_format($total_receivable, 2); ?></td>
                        <td class="text-end py-2 cr-total" style="font-size: 1rem;"><?php echo number_format($total_payable, 2); ?></td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
    .border-gold { border: 1px solid var(--gold) !important; }
    .bg-light-subtle { background-color: rgba(255,255,255,0.02); }
    
    @media print {
        @page { size: portrait; margin: 1cm; }
        .no-print, .sidebar, .navbar-top, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
            display: none !important;
        }
        .print-only-header {
            display: block !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        .card-body { padding: 0 !important; }
        .table-custom {
            color: #000 !important;
            width: 100% !important;
            border: 1px solid #000 !important;
            border-collapse: collapse !important;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #000 !important;
            padding: 4px 8px !important;
            color: #000 !important;
            font-size: 11px !important;
        }
        .table-custom thead th {
            background-color: #f2f2f2 !important;
            color: #000 !important;
            border-bottom: 2px solid #000 !important;
            text-transform: uppercase;
        }
        .dr-amount, .dr-total { color: #000 !important; }
        .cr-amount, .cr-total { color: #000 !important; }
        tfoot tr {
            background-color: #eee !important;
            color: #000 !important;
            border-top: 2px solid #000 !important;
        }
        tfoot td { color: #000 !important; border-top: 2px solid #000 !important; }
        .party-name-cell { font-size: 12px !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('#balancesTable').DataTable({
            pageLength: 50,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search party..."
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
