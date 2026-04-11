<?php
$report_type = $_GET['type'] ?? 'all'; // all, receivable, payable
$page_title = ($report_type == 'receivable') ? 'Receivable Report' : (($report_type == 'payable') ? 'Payable Report' : 'Party Balances');

require_once 'includes/header.php';

// Fetch all parties with their calculated balances (Matching Ledger Logic)
$dept_id = (int)$_SESSION['dept_id'];
$sql = "SELECT 
            p.id, 
            p.name, 
            p.mobile,
            p.address,
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
    
    // Filter by type if requested
    if ($report_type == 'receivable' && $balance <= 0.01) continue;
    if ($report_type == 'payable' && $balance >= -0.01) continue;
    
    $parties_data[] = $row;
}
?>

<!-- Miracle Style Print Header (Hidden on screen) -->
<div class="print-only-header d-none">
    <div class="text-center mb-0">
        <?php $s = get_settings(); ?>
        <h2 class="mb-0 fw-bold accounting-font" style="letter-spacing: 1px;"><?php echo strtoupper($s['company_name'] ?? 'BAJOT EXTRUSION PVT. LTD.'); ?></h2>
        <p class="mb-0 extra-small"><?php echo strtoupper($s['company_address'] ?? ''); ?></p>
        <div class="d-flex justify-content-between mt-3 px-2 border-top border-bottom border-dark py-1">
            <div class="text-start">
                <h5 class="mb-0 fw-bold small">
                    <?php 
                        if ($report_type == 'receivable') echo 'A/c. Receivable Namewise Report';
                        elseif ($report_type == 'payable') echo 'A/c. Payable Namewise Report';
                        else echo 'Party Balances (Consolidated)';
                    ?>
                </h5>
                <p class="mb-0 extra-small">As On Date <?php echo date('d/m/Y'); ?></p>
            </div>
            <div class="text-end align-self-end">
                <p class="mb-0 extra-small">Page : 1</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 no-print">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 text-theme"><?php echo $page_title; ?></h4>
            <div class="btn-group mt-2">
                <a href="party_balances.php?type=all" class="btn btn-sm <?php echo $report_type == 'all' ? 'btn-gold' : 'btn-outline-gold'; ?>">Consolidated</a>
                <a href="party_balances.php?type=receivable" class="btn btn-sm <?php echo $report_type == 'receivable' ? 'btn-gold' : 'btn-outline-gold'; ?>">Receivable Only</a>
                <a href="party_balances.php?type=payable" class="btn btn-sm <?php echo $report_type == 'payable' ? 'btn-gold' : 'btn-outline-gold'; ?>">Payable Only</a>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fa fa-print me-1"></i> Print Miracle Report
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards (Standard View Only) -->
<?php if ($report_type == 'all'): ?>
<div class="row mb-4 g-3 no-print">
    <div class="col-md-4">
        <div class="card card-bajot border-start border-4 border-success shadow-sm">
            <div class="card-body">
                <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Receivable (Leva na)</p>
                <h3 class="mb-0 text-success fw-bold"><?php echo format_currency($total_receivable); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-bajot border-start border-4 border-danger shadow-sm">
            <div class="card-body">
                <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Payable (Apva na)</p>
                <h3 class="mb-0 text-danger fw-bold"><?php echo format_currency($total_payable); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php $net = $total_receivable - $total_payable; ?>
        <div class="card card-bajot border-start border-4 <?php echo $net >= 0 ? 'border-primary' : 'border-warning'; ?> shadow-sm">
            <div class="card-body">
                <p class="text-secondary-themed mb-1 small text-uppercase fw-bold">Net Balance</p>
                <h3 class="mb-0 <?php echo $net >= 0 ? 'text-primary' : 'text-warning'; ?> fw-bold"><?php echo format_currency(abs($net)); ?> <?php echo $net >= 0 ? '(Dr)' : '(Cr)'; ?></h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Details Table -->
<div class="card card-bajot border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom table-hover accounting-table" id="balancesTable">
                <thead>
                    <?php if ($report_type == 'all'): ?>
                    <tr>
                        <th style="width: 40%;">Party Name</th>
                        <th>Type</th>
                        <th class="no-print">Mobile</th>
                        <th class="text-end">Debit (Dr)</th>
                        <th class="text-end">Credit (Cr)</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                    <?php else: ?>
                    <tr class="accounting-header-row">
                        <th style="width: 70%;">Party Name</th>
                        <th style="width: 30%;" class="text-end">Pending Amount</th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php 
                    $p_count = 0;
                    $shown_total = 0;
                    foreach($parties_data as $party): 
                        $bal = (float)$party['balance'];
                        if (abs($bal) < 0.01) continue; 
                        $p_count++;
                        $shown_total += abs($bal);
                    ?>
                    <tr>
                        <td class="accounting-party-name">
                            <div class="fw-bold"><?php echo htmlspecialchars($party['name']); ?></div>
                        </td>
                        <?php if ($report_type == 'all'): ?>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <?php echo ucfirst($party['party_type']); ?>
                            </span>
                        </td>
                        <td class="no-print"><?php echo $party['mobile'] ?: '-'; ?></td>
                        <td class="text-end">
                            <?php if($bal > 0.01): ?>
                                <span class="fw-bold text-success_dr"><?php echo number_format($bal, 2); ?></span>
                            <?php else: ?>
                                <span class="text-muted opacity-25">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($bal < -0.01): ?>
                                <span class="fw-bold text-danger_cr"><?php echo number_format(abs($bal), 2); ?></span>
                            <?php else: ?>
                                <span class="text-muted opacity-25">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <a href="ledger.php?party_id=<?php echo $party['id']; ?>" class="btn btn-sm btn-outline-gold">
                                <i class="fa fa-book"></i>
                            </a>
                        </td>
                        <?php else: ?>
                        <td class="text-end accounting-amount"><?php echo number_format(abs($bal), 2); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-top-2 border-dark">
                    <?php if ($report_type == 'all'): ?>
                    <tr class="fw-bold bg-dark text-white table-total-row">
                        <td class="text-end py-2">TOTAL (<?php echo $p_count; ?> Parties):</td>
                        <td></td>
                        <td class="no-print"></td>
                        <td class="text-end py-2" style="font-size: 1rem;"><?php echo number_format($total_receivable, 2); ?></td>
                        <td class="text-end py-2" style="font-size: 1rem;"><?php echo number_format($total_payable, 2); ?></td>
                        <td class="no-print"></td>
                    </tr>
                    <?php else: ?>
                    <tr class="fw-bold accounting-total-row">
                        <td class="text-end py-1">Total</td>
                        <td class="text-end py-1"><?php echo number_format($shown_total, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
    .accounting-party-name { color: #8b0000 !important; } /* Maroonish color like Miracle */
    .accounting-font { font-family: "Courier New", Courier, monospace; }
    
    @media screen {
        .text-success_dr { color: #198754; }
        .text-danger_cr { color: #dc3545; }
    }

    @media print {
        @page { size: portrait; margin: 1cm; }
        .no-print, .sidebar, .navbar-top, .btn-group, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; padding: 0 !important; }
        .card { background: transparent !important; border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; }
        
        .print-only-header { display: block !important; }
        
        .accounting-table {
            width: 100% !important;
            border-collapse: collapse !important;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            font-size: 11px !important;
        }
        
        .accounting-table th, .accounting-table td {
            padding: 2px 5px !important;
            border: none !important;
            color: #8b0000 !important; /* Force maroon in print */
        }
        
        .accounting-header-row th {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            text-transform: capitalize !important;
            font-weight: bold !important;
            color: #000 !important;
        }
        
        .accounting-total-row td {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            font-weight: bold !important;
            color: #000 !important;
        }
        
        .accounting-amount { color: #000 !important; font-weight: bold; }
        
        tfoot tr.table-total-row { background: transparent !important; color: #000 !important; border-top: 2px solid #000 !important; }
        tfoot tr.table-total-row td { color: #000 !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        const reportType = '<?php echo $report_type; ?>';
        $('#balancesTable').DataTable({
            pageLength: 100,
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
