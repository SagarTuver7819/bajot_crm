<?php
$page_title = 'Expense Dashboard';
require_once 'includes/header.php';

// Date Filter
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$dept_id = $_SESSION['dept_id'] ?? 0;

// Fetch Summary Data
$summary_sql = "SELECT 
                    SUM(amount) as total_expense,
                    COUNT(id) as total_count,
                    AVG(amount) as avg_expense
                FROM expenses 
                WHERE dept_id = $dept_id AND date BETWEEN '$from_date' AND '$to_date'";
$summary = $conn->query($summary_sql)->fetch_assoc();

// Fetch Top Category
$top_cat_sql = "SELECT ec.name, SUM(e.amount) as total 
                FROM expenses e 
                JOIN expense_categories ec ON e.category_id = ec.id 
                WHERE e.dept_id = $dept_id AND e.date BETWEEN '$from_date' AND '$to_date'
                GROUP BY e.category_id 
                ORDER BY total DESC LIMIT 1";
$top_cat = $conn->query($top_cat_sql)->fetch_assoc();

// Fetch Category-wise Data for Boxes & Charts
$categories_data = [];
$cat_res = $conn->query("SELECT ec.name, SUM(e.amount) as total 
                         FROM expenses e 
                         JOIN expense_categories ec ON e.category_id = ec.id 
                         WHERE e.dept_id = $dept_id AND e.date BETWEEN '$from_date' AND '$to_date'
                         GROUP BY ec.name 
                         ORDER BY total DESC");
while($row = $cat_res->fetch_assoc()) $categories_data[] = $row;

// Fetch Daily Trend for Chart
$trend_data = [];
$labels = [];
$values = [];
$trend_res = $conn->query("SELECT date, SUM(amount) as total 
                           FROM expenses 
                           WHERE dept_id = $dept_id AND date BETWEEN '$from_date' AND '$to_date'
                           GROUP BY date 
                           ORDER BY date ASC");
while($row = $trend_res->fetch_assoc()) {
    $labels[] = date('d M', strtotime($row['date']));
    $values[] = $row['total'];
}
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h4 class="mb-0 text-theme fw-bold"><i class="fa fa-chart-pie me-2 text-gold"></i>Expense Analytics Dashboard</h4>
        <p class="text-secondary-themed small mb-0">Detailed breakdown of company expenditures by category and date.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-flex justify-content-md-end gap-2 mb-2">
            <a href="export_report.php?type=expense&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&format=excel" class="btn btn-outline-gold btn-sm"><i class="fa fa-file-excel me-1"></i> Excel</a>
            <a href="export_report.php?type=expense&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&format=pdf" target="_blank" class="btn btn-outline-gold btn-sm"><i class="fa fa-file-pdf me-1"></i> PDF</a>
        </div>
        <form method="GET" class="d-flex justify-content-md-end gap-2 align-items-center">
            <div class="input-group input-group-sm w-auto">
                <span class="input-group-text bg-dark-card border-secondary text-secondary-themed"><i class="fa fa-calendar-alt"></i></span>
                <input type="date" name="from_date" class="form-control bg-dark-card border-secondary text-white" value="<?php echo $from_date; ?>">
                <span class="input-group-text bg-dark-card border-secondary text-secondary-themed">to</span>
                <input type="date" name="to_date" class="form-control bg-dark-card border-secondary text-white" value="<?php echo $to_date; ?>">
                <button type="submit" class="btn btn-gold btn-sm"><i class="fa fa-filter"></i> Filter</button>
            </div>
            <a href="expense_dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="fa fa-sync"></i></a>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-bajot border-0 shadow-premium h-100 overflow-hidden theme-gold-bg">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-warning-subtle p-2 rounded-2">
                        <i class="fa fa-hand-holding-dollar text-gold"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Total Period Expense</h6>
                <h3 class="fw-bold mb-0 text-white"><?php echo format_currency($summary['total_expense'] ?? 0); ?></h3>
                <div class="mt-2 extra-small text-white-50">
                    Range: <?php echo date('d M', strtotime($from_date)); ?> - <?php echo date('d M', strtotime($to_date)); ?>
                </div>
            </div>
            <div style="height: 4px; background: var(--gold-gradient);"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot border-0 shadow-premium h-100 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-primary-subtle p-2 rounded-2">
                        <i class="fa fa-star text-primary"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Top Category</h6>
                <h3 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $top_cat['name'] ?? 'None'; ?></h3>
                <div class="mt-2 extra-small text-secondary-themed">
                    Val: <?php echo format_currency($top_cat['total'] ?? 0); ?>
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #007aff, #004085);"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot border-0 shadow-premium h-100 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-success-subtle p-2 rounded-2">
                        <i class="fa fa-hashtag text-success"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Transaction Count</h6>
                <h3 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $summary['total_count'] ?? 0; ?></h3>
                <div class="mt-2 extra-small text-secondary-themed">
                    Avg: <?php echo format_currency($summary['avg_expense'] ?? 0); ?> / exp
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #4cd964, #28a745);"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot border-0 shadow-premium h-100 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-danger-subtle p-2 rounded-2">
                        <i class="fa fa-calendar-day text-danger"></i>
                    </div>
                </div>
                <h6 class="text-secondary-themed extra-small text-uppercase mb-1">Daily Avg Expense</h6>
                <h3 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>">
                    <?php 
                        $days = (strtotime($to_date) - strtotime($from_date)) / 86400 + 1;
                        echo format_currency(($summary['total_expense'] ?? 0) / $days); 
                    ?>
                </h3>
                <div class="mt-2 extra-small text-secondary-themed">
                    Computed over <?php echo round($days); ?> days
                </div>
            </div>
            <div style="height: 4px; background: linear-gradient(90deg, #ff3b30, #af1d1d);"></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-bajot h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Expense Spending Trend</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <canvas id="expenseTrendChart" height="150"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-bajot h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Categorical Share</h5>
            </div>
            <div class="card-body px-4 pb-4 d-flex align-items-center">
                <canvas id="categoryShareChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Category Boxes -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card card-bajot border-0 shadow-premium h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0 text-theme"><i class="fa fa-list-ul me-2"></i>Category-wise Total Expenditure</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($categories_data)): ?>
                    <p class="text-muted small">No data recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Share %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories_data as $exp): 
                                    $percent = ($exp['total'] / ($summary['total_expense'] ?: 1)) * 100;
                                ?>
                                <tr>
                                    <td>
                                        <i class="fa fa-circle text-gold me-2 small op-5"></i>
                                        <span class="fw-medium <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo htmlspecialchars($exp['name']); ?></span>
                                    </div>
                                    <td class="text-end fw-bold"><?php echo format_currency($exp['total']); ?></td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill bg-dark-card border border-secondary text-gold small">
                                            <?php echo number_format($percent, 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="row g-3">
            <?php foreach(array_slice($categories_data, 0, 6) as $exp): ?>
            <div class="col-6">
                <div class="card card-bajot border-0 shadow-sm h-100" style="border-left: 3px solid var(--gold) !important;">
                    <div class="card-body p-3">
                        <h6 class="extra-small text-secondary-themed text-uppercase mb-2 text-truncate"><?php echo htmlspecialchars($exp['name']); ?></h6>
                        <h6 class="fw-bold mb-0 <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo format_currency($exp['total']); ?></h6>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Trend Chart
        const trendCtx = document.getElementById('expenseTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Daily Expense (₹)',
                    data: <?php echo json_encode($values); ?>,
                    borderColor: '#C9A14A',
                    backgroundColor: 'rgba(201, 161, 74, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#e1e1e3' } },
                    x: { grid: { display: false }, ticks: { color: '#e1e1e3' } }
                }
            }
        });

        // Category Pie Chart
        const shareCtx = document.getElementById('categoryShareChart').getContext('2d');
        new Chart(shareCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($categories_data, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($categories_data, 'total')); ?>,
                    backgroundColor: [
                        '#C9A14A', '#007aff', '#ff3b30', '#4cd964', '#5856d6', '#ff9500', '#5ac8fa', '#ff2d55'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e1e1e3', boxWidth: 12, font: { size: 10 } }
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
