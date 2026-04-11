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

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Party Balances (Outstanding)</h4>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fa fa-print me-1"></i> Print
            </button>
            <a href="party_crud.php" class="btn btn-gold">
                <i class="fa fa-users me-1"></i> Manage Parties
            </a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card card-bajot border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1">Total Receivable (Leva na)</p>
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
        <div class="card card-bajot border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1">Total Payable (Apva na)</p>
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
        <div class="card card-bajot border-start border-4 <?php echo $net >= 0 ? 'border-primary' : 'border-warning'; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-secondary-themed mb-1">Net Balance</p>
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
<div class="card card-bajot">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom table-hover" id="balancesTable">
                <thead>
                    <tr>
                        <th>Party Name</th>
                        <th>Type</th>
                        <th>Mobile</th>
                        <th class="text-end">Receivable (Debit)</th>
                        <th class="text-end">Payable (Credit)</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($parties_data as $party): 
                        $bal = (float)$party['balance'];
                        if (abs($bal) < 0.01) continue; // Skip zero balance
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($party['name']); ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <?php echo ucfirst($party['party_type']); ?>
                            </span>
                        </td>
                        <td><?php echo $party['mobile'] ?: '-'; ?></td>
                        <td class="text-end">
                            <?php if($bal > 0): ?>
                                <span class="text-success fw-bold"><?php echo format_currency($bal); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($bal < 0): ?>
                                <span class="text-danger fw-bold"><?php echo format_currency(abs($bal)); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="ledger.php?party_id=<?php echo $party['id']; ?>" class="btn btn-sm btn-outline-gold" title="View Ledger">
                                <i class="fa fa-book"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="border-top-2">
                    <tr class="fw-bold bg-dark text-white">
                        <td colspan="3" class="text-end py-3">GRAND TOTAL (ALL PARTIES):</td>
                        <td class="text-end text-success py-3" style="font-size: 1.1rem;"><?php echo format_currency($total_receivable); ?></td>
                        <td class="text-end text-danger py-3" style="font-size: 1.1rem;"><?php echo format_currency($total_payable); ?></td>
                        <td></td>
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
        .sidebar, .navbar-top, .btn-gold, .btn-outline-secondary, .action-col, .dataTables_filter, .dataTables_length, .dataTables_paginate {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table-custom {
            color: black !important;
            width: 100% !important;
        }
        tfoot tr {
            background-color: #f8f9fa !important;
            color: black !important;
        }
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
