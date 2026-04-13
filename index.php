<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

if (!has_permission('dashboard', 'view')) {
    // If they can't view dashboard, redirect to another module they CAN view
    // Or just show a simple access denied.
    echo "<div class='container p-5 text-center'><h3>Welcome to Kaizer CRM</h3><p>Select a module from the sidebar to begin.</p></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch Totals
$total_sales = $conn->query("SELECT SUM(total_amount) FROM outwards")->fetch_row()[0] ?? 0;
$total_purchase = $conn->query("SELECT SUM(total_amount) FROM inwards")->fetch_row()[0] ?? 0;
$dept_id = $_SESSION['dept_id'] ?? 0;
$total_expenses = $conn->query("SELECT SUM(amount) FROM expenses WHERE dept_id = $dept_id")->fetch_row()[0] ?? 0;
$profit = $total_sales - $total_purchase - $total_expenses;

// Calculate Total Receivables (Levana) and Total Payables (Apvana)
// Convention: Customer balance = Opening + Sales - Receipts - Kasar_allowed (positive = we receive)
//             Supplier balance = Opening + Purchases - Payments - Kasar_received (positive = we owe)

$total_receivables = 0;
$total_payables    = 0;

$parties_res = $conn->query("SELECT id, type, opening_balance FROM parties");
while ($party = $parties_res->fetch_assoc()) {
    $pid  = (int)$party['id'];
    $ob   = floatval($party['opening_balance']);
    $type = $party['type'];

    $sales     = floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) FROM outwards WHERE party_id=$pid")->fetch_row()[0]);
    $purchases = floatval($conn->query("SELECT COALESCE(SUM(total_amount),0) FROM inwards WHERE party_id=$pid")->fetch_row()[0]);
    $receipts  = floatval($conn->query("SELECT COALESCE(SUM(amount),0) FROM vouchers WHERE party_id=$pid AND type='receipt'")->fetch_row()[0]);
    $payments  = floatval($conn->query("SELECT COALESCE(SUM(amount),0) FROM vouchers WHERE party_id=$pid AND type='payment'")->fetch_row()[0]);
    $kasar_allowed   = floatval($conn->query("SELECT COALESCE(SUM(amount),0) FROM kasars WHERE party_id=$pid AND type='allowed'")->fetch_row()[0]);
    $kasar_received  = floatval($conn->query("SELECT COALESCE(SUM(amount),0) FROM kasars WHERE party_id=$pid AND type='received'")->fetch_row()[0]);

    if ($type === 'customer') {
        // Levana: Sales increase what customer owes us
        $bal = $ob + $sales - $receipts - $kasar_allowed;
        if ($bal > 0) $total_receivables += $bal;
    } elseif ($type === 'supplier') {
        // Apvana: Purchases increase what we owe supplier
        $bal = $ob + $purchases - $payments - $kasar_received;
        if ($bal > 0) $total_payables += $bal;
    } elseif ($type === 'both') {
        // Both: treat receivable and payable portions separately
        $recv_bal = $ob > 0 ? $ob : 0;
        $pay_bal  = $ob < 0 ? abs($ob) : 0;
        $recv_bal = $recv_bal + $sales - $receipts - $kasar_allowed;
        $pay_bal  = $pay_bal + $purchases - $payments - $kasar_received;
        if ($recv_bal > 0) $total_receivables += $recv_bal;
        if ($pay_bal  > 0) $total_payables    += $pay_bal;
    }
}

// Dept Breakdown
$sales_by_dept = [];
$res_s = $conn->query("SELECT dept_id, SUM(total_amount) as total FROM outwards GROUP BY dept_id");
while($row = $res_s->fetch_assoc()) $sales_by_dept[$row['dept_id']] = $row['total'];

$purchase_by_dept = [];
$res_p = $conn->query("SELECT dept_id, SUM(total_amount) as total FROM inwards GROUP BY dept_id");
while($row = $res_p->fetch_assoc()) $purchase_by_dept[$row['dept_id']] = $row['total'];

// Low Stock Alert
$low_stock = $conn->query("SELECT * FROM products WHERE current_stock < 10 LIMIT 5");

// Recent Activities (Combined Inward/Outward)
$recent_activities = $conn->query("
    (SELECT 'Purchase' as type, date, total_amount, party_id, dept_id FROM inwards)
    UNION
    (SELECT 'Sales' as type, date, total_amount, party_id, dept_id FROM outwards)
    ORDER BY date DESC LIMIT 5
");

// Expense Category Breakdown
$dept_id = $_SESSION['dept_id'] ?? 0;
$expense_categories_data = [];
$res_exp = $conn->query("SELECT ec.name, SUM(e.amount) as total 
                         FROM expenses e 
                         JOIN expense_categories ec ON e.category_id = ec.id 
                         WHERE e.dept_id = $dept_id 
                         GROUP BY ec.name");
while($row = $res_exp->fetch_assoc()) $expense_categories_data[] = $row;
?>

<div class="dashboard-header mb-4" data-aos="fade-down">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3 class="fw-bold text-theme mb-1">Corporate Dashboard</h3>
            <p class="text-secondary-themed small mb-0">Welcome back, <span class="text-gold"><?php echo $_SESSION['name'] ?? 'User'; ?></span>. Here is what's happening today.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex align-items-center bg-dark-card p-2 px-3 rounded-pill border border-secondary shadow-sm">
                <i class="fa fa-calendar-alt text-gold me-2"></i>
                <span class="small text-secondary-themed fw-bold"><?php echo date('D, d M Y'); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3" data-aos="zoom-in" data-aos-delay="100">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden h-100">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="bg-success-subtle p-3 rounded-3">
                        <i class="fa fa-chart-line text-success fa-lg"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Invoiced Sales</h6>
                <h2 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($total_sales); ?></h2>
                <div class="mt-3 text-success extra-small fw-bold">
                    <i class="fa fa-caret-up me-1"></i> Current Month
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #4cd964, #28a745);"></div>
        </div>
    </div>
    <div class="col-md-3" data-aos="zoom-in" data-aos-delay="200">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden h-100">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="bg-primary-subtle p-3 rounded-3">
                        <i class="fa fa-shopping-cart text-primary fa-lg"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Procurement Costs</h6>
                <h2 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($total_purchase); ?></h2>
                <div class="mt-3 text-primary extra-small fw-bold">
                    <i class="fa fa-caret-right me-1"></i> Stable Trend
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #007aff, #004085);"></div>
        </div>
    </div>
    <div class="col-md-3" data-aos="zoom-in" data-aos-delay="250">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden h-100">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="bg-danger-subtle p-3 rounded-3">
                        <i class="fa fa-receipt text-danger fa-lg"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Operational Expenses</h6>
                <h2 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($total_expenses); ?></h2>
                <div class="mt-3 text-danger extra-small fw-bold">
                    <i class="fa fa-minus-circle me-1"></i> Dept Wise
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #ff3b30, #af1d1d);"></div>
        </div>
    </div>
    <div class="col-md-3" data-aos="zoom-in" data-aos-delay="300">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden h-100 theme-gold-bg">
            <div class="card-body p-4 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="bg-warning-subtle p-3 rounded-3">
                        <i class="fa fa-wallet text-gold fa-lg"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Company Net Balance</h6>
                <h2 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($profit); ?></h2>
                <div class="mt-3 text-gold extra-small fw-bold">
                    <i class="fa fa-shield-alt me-1"></i> Account Health: Secure
                </div>
            </div>
            <div style="height: 4px; background: var(--gold-gradient);"></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6" data-aos="fade-right">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-success-subtle p-3 rounded-circle me-4">
                    <i class="fa fa-hand-holding-dollar text-success fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-success extra-small text-uppercase mb-1 fw-bold">TOTAL RECEIVABLES (Levana)</h6>
                    <h1 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($total_receivables); ?></h1>
                    <p class="text-secondary-themed small mb-0 mt-1"><i class="fa fa-arrow-up text-success me-1"></i> From All Customers</p>
                </div>
            </div>
            <div style="height: 4px; background: #28a745;"></div>
        </div>
    </div>
    <div class="col-md-6" data-aos="fade-left">
        <div class="card card-bajot border-0 shadow-premium overflow-hidden">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-danger-subtle p-3 rounded-circle me-4">
                    <i class="fa fa-money-bill-transfer text-danger fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-danger extra-small text-uppercase mb-1 fw-bold">TOTAL PAYABLES (Apvana)</h6>
                    <h1 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($total_payables); ?></h1>
                    <p class="text-secondary-themed small mb-0 mt-1"><i class="fa fa-arrow-down text-danger me-1"></i> To All Suppliers</p>
                </div>
            </div>
            <div style="height: 4px; background: #dc3545;"></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="400">
    <div class="col-12 mb-2">
        <h6 class="text-secondary-themed fw-bold text-uppercase small"><i class="fa fa-layer-group me-2"></i>Department Performance Breakdown</h6>
    </div>
    <?php foreach($departments as $id => $name): 
        $s = $sales_by_dept[$id] ?? 0;
        $p = $purchase_by_dept[$id] ?? 0;
    ?>
    <div class="col-md-4">
        <div class="card card-bajot border-0 shadow-sm" style="border-left: 4px solid <?php echo ($id==1?'var(--gold)':($id==2?'#007aff':'#4cd964')); ?> !important;">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3 small text-uppercase" style="color: <?php echo ($id==1?'var(--gold)':($id==2?'#007aff':'#4cd964')); ?>;"><?php echo $name; ?></h6>
                <div class="d-flex justify-content-between mb-1">
                    <span class="extra-small text-secondary-themed">Sales:</span>
                    <span class="small fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($s); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="extra-small text-secondary-themed">Purchase:</span>
                    <span class="small fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($p); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-bajot mb-4">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-4 px-4">
                <h5 class="fw-bold mb-0">Sales vs Purchase</h5>
                <div class="badge rounded-pill" style="background-color: rgba(201, 161, 74, 0.1); color: var(--gold);">Monthly Stats</div>
            </div>
            <div class="card-body px-4 pb-4">
                <canvas id="salesPurchaseChart" height="150"></canvas>
            </div>
        </div>

        <div class="card card-bajot">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Party</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($activity = $recent_activities->fetch_assoc()): 
                                $party_name = $conn->query("SELECT name FROM parties WHERE id = " . $activity['party_id'])->fetch_row()[0] ?? 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <span class="badge rounded-pill <?php echo ($activity['type'] == 'Sales') ? 'bg-success' : 'bg-primary'; ?>">
                                        <?php echo $activity['type']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M, Y', strtotime($activity['date'])); ?></td>
                                <td>
                                    <div>
                                        <p class="mb-0 fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $party_name; ?></p>
                                        <small class="extra-small text-gold"><i class="fa fa-building-user me-1"></i><?php echo $departments[$activity['dept_id']] ?? 'N/A'; ?></small>
                                    </div>
                                </td>
                                <td class="fw-bold"><?php echo format_currency($activity['total_amount']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-bajot mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0 text-danger">Low Stock Alerts</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush bg-transparent">
                    <?php while ($prod = $low_stock->fetch_assoc()): ?>
                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center text-theme px-0">
                        <div>
                            <p class="mb-0 fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $prod['name']; ?></p>
                            <small class="text-secondary-themed">Unit: <?php echo $prod['unit']; ?></small>
                        </div>
                        <span class="badge bg-danger rounded-pill"><?php echo $prod['current_stock']; ?> Left</span>
                    </li>
                    <?php endwhile; if ($low_stock->num_rows == 0): ?>
                    <li class="list-group-item bg-transparent text-muted small px-0">No low stock items.</li>
                    <?php endif; ?>
                </ul>
                <a href="inventory.php" class="btn btn-sm btn-outline-gold mt-3 w-100">View All Inventory</a>
            </div>
        </div>

        <div class="card border-0 shadow-premium overflow-hidden bg-dark-card" data-aos="fade-left" data-aos-delay="500">
            <div class="card-body p-4 text-center">
                <div class="quick-action-icon mb-3">
                    <i class="fa fa-bolt text-gold fa-3x op-3 animated bounceInfinite"></i>
                </div>
                <h5 class="fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?> mb-4">Management Hub</h5>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="outward_crud.php?mode=add" class="d-flex flex-column align-items-center p-3 rounded-3 bg-dark-card border border-secondary text-decoration-none hover-gold transition">
                            <i class="fa fa-file-invoice text-gold mb-2 h4"></i>
                            <span class="extra-small text-secondary-themed font-weight-bold">NEW SALE</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="inward_crud.php?mode=add" class="d-flex flex-column align-items-center p-3 rounded-3 bg-dark-card border border-secondary text-decoration-none hover-gold transition">
                            <i class="fa fa-truck-loading text-gold mb-2 h4"></i>
                            <span class="extra-small text-secondary-themed font-weight-bold">NEW STOCK</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="party_crud.php" class="d-flex flex-column align-items-center p-3 rounded-3 bg-dark-card border border-secondary text-decoration-none hover-gold transition">
                            <i class="fa fa-users text-gold mb-2 h4"></i>
                            <span class="extra-small text-secondary-themed font-weight-bold">CLIENTS</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="reports.php" class="d-flex flex-column align-items-center p-3 rounded-3 bg-dark-card border border-secondary text-decoration-none hover-gold transition">
                            <i class="fa fa-chart-pie text-gold mb-2 h4"></i>
                            <span class="extra-small text-secondary-themed font-weight-bold">ANALYTICS</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="600">
    <div class="col-lg-6">
        <div class="card card-bajot border-0 shadow-premium h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-theme"><i class="fa fa-calculator me-2"></i>Expense Report (Category-wise)</h5>
                <a href="expense_dashboard.php" class="btn btn-sm btn-outline-gold">Full Analytics <i class="fa fa-arrow-right ms-1"></i></a>
            </div>
            <div class="card-body p-4">
                <?php if (empty($expense_categories_data)): ?>
                    <p class="text-muted mb-0 small">No expenses recorded for this department.</p>
                <?php else: ?>
                    <div class="row">
                        <?php 
                        $half = ceil(count($expense_categories_data) / 2);
                        $chunks = array_chunk($expense_categories_data, $half > 0 ? $half : 1);
                        foreach($chunks as $chunk): 
                        ?>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush bg-transparent">
                                <?php foreach($chunk as $exp): ?>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-secondary py-2 px-0 mb-1" style="border-bottom-style: dashed !important;">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-caret-right text-gold me-2 extra-small"></i>
                                        <span class="small <?php echo ($theme === 'dark' ? 'text-white-50' : 'text-secondary'); ?>"><?php echo htmlspecialchars($exp['name']); ?></span>
                                    </div>
                                    <span class="fw-bold small <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($exp['total']); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                        <span class="text-secondary-themed small fw-bold">TOTAL OPERATIONAL EXPENSE</span>
                        <span class="h5 fw-bold text-danger mb-0"><?php echo format_currency($total_expenses); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card card-bajot border-0 shadow-premium h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0 text-theme"><i class="fa fa-bullseye me-2"></i>Expense Budget Insight</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($expense_categories_data)): ?>
                    <?php 
                    $limit = 5;
                    $count = 0;
                    foreach($expense_categories_data as $exp): 
                        if ($count++ >= $limit) break;
                        $percent = ($exp['total'] / ($total_expenses ?: 1)) * 100;
                        $color = ($percent > 40) ? 'bg-danger' : (($percent > 20) ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="extra-small text-secondary-themed"><?php echo htmlspecialchars($exp['name']); ?></span>
                            <span class="extra-small fw-bold text-gold"><?php echo number_format($percent, 1); ?>%</span>
                        </div>
                        <div class="progress bg-dark-card border border-secondary" style="height: 6px;">
                            <div class="progress-bar <?php echo $color; ?> rounded-pill" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">Add some expenses to see insights.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('salesPurchaseChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales (₹)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#C9A14A',
                    backgroundColor: 'rgba(201, 161, 74, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Purchase (₹)',
                    data: [10000, 15000, 12000, 18000, 17000, 20000],
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#e1e1e3' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#e1e1e3' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#e1e1e3' }
                    }
                }
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
