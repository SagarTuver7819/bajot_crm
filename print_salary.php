<?php
require_once 'config.php';
require_once 'includes/auth.php';
check_login();

if (!isset($_GET['id'])) die("Invalid request.");

$id = (int)$_GET['id'];
$res = $conn->query("SELECT s.*, e.name as emp_name, e.mobile_number, e.role 
                     FROM salaries s 
                     JOIN employees e ON s.employee_id = e.id 
                     WHERE s.id = $id");

if (!$salary = $res->fetch_assoc()) die("Salary record not found.");

$s = get_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Receipt - <?php echo $salary['emp_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .receipt-card { background: #white; max-width: 800px; margin: 50px auto; padding: 40px; border: 1px solid #ddd; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #C9A14A; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .company-name { color: #C9A14A; font-weight: 700; font-size: 1.5rem; text-transform: uppercase; }
        .label { color: #888; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .value { font-weight: 600; color: #333; }
        .table-salary { margin-top: 30px; }
        .table-salary th { background: #f9f9f9; text-transform: uppercase; font-size: 0.75rem; color: #777; }
        .total-box { background: #C9A14A; color: black; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .footer-note { border-top: 1px solid #eee; padding-top: 20px; margin-top: 40px; color: #999; font-size: 0.8rem; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .receipt-card { margin: 0; box-shadow: none; border: none; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container text-center no-print mt-3">
        <button onclick="window.print()" class="btn btn-dark"><i class="fa fa-print"></i> Print Now</button>
        <a href="salaries.php" class="btn btn-secondary">Back to CRM</a>
    </div>

    <div class="receipt-card bg-white">
        <div class="header d-flex justify-content-between align-items-end">
            <div>
                <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
                    <img src="<?php echo $s['company_logo']; ?>" class="logo" alt="Logo"><br>
                <?php endif; ?>
                <span class="company-name"><?php echo $s['company_name'] ?? 'KAIZER INDUSTRIES'; ?></span>
            </div>
            <div class="text-end">
                <h3 class="fw-bold mb-0">SALARY SLIP</h3>
                <p class="text-muted small">Ref: #SLY-<?php echo str_pad($salary['id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-6">
                <div class="mb-3">
                    <div class="label">Employee Name</div>
                    <div class="value"><?php echo $salary['emp_name']; ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Department</div>
                    <div class="value"><?php echo $salary['role'] ?: 'General Staff'; ?></div>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="mb-3">
                    <div class="label">Payment for Month</div>
                    <div class="value"><?php echo $salary['month'] . ' ' . $salary['year']; ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Payment Date</div>
                    <div class="value"><?php echo date('d M, Y', strtotime($salary['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <table class="table border table-salary">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Days/Units</th>
                    <th class="text-end">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monthly Base Salary</td>
                    <td class="text-center">-</td>
                    <td class="text-end"><?php echo number_format($salary['monthly_salary'], 2); ?></td>
                </tr>
                <tr>
                    <td>Working Days Calculation</td>
                    <td class="text-center"><?php echo $salary['working_days']; ?> / (<?php echo $salary['total_days']; ?> - <?php echo $salary['week_offs']; ?>)</td>
                    <td class="text-end"><?php 
                        $rate = $salary['monthly_salary'] / ($salary['total_days'] - $salary['week_offs']);
                        $worked_amount = $rate * $salary['working_days'];
                        echo number_format($worked_amount, 2); 
                    ?></td>
                </tr>
                <?php 
                // Fetch advances for this month
                $month = $salary['month'];
                $year = $salary['year'];
                $emp_id = $salary['employee_id'];
                $adv_res = $conn->query("SELECT * FROM salary_advances 
                                         WHERE employee_id = $emp_id 
                                         AND YEAR(date) = $year 
                                         AND DATE_FORMAT(date, '%M') = '$month'
                                         ORDER BY date ASC");
                
                $total_adv = 0;
                if ($adv_res->num_rows > 0):
                    echo '<tr><td colspan="3" class="fw-bold small py-1">Salary Advances (Upad) Breakdown:</td></tr>';
                    while($adv = $adv_res->fetch_assoc()):
                        $total_adv += $adv['amount'];
                ?>
                <tr>
                    <td class="ps-4 small text-danger"><i class="fa fa-caret-right me-1"></i> Advance on <?php echo date('d M, Y', strtotime($adv['date'])); ?></td>
                    <td class="text-center">-</td>
                    <td class="text-end text-danger">- <?php echo number_format($adv['amount'], 2); ?></td>
                </tr>
                <?php 
                    endwhile; 
                endif;
                ?>
            </tbody>
        </table><?php if($total_adv > 0): ?>
        <div class="text-end text-danger mb-3 px-2">
            <span class="small fw-bold">Total Advances Deducted: ₹ <?php echo number_format($total_adv, 2); ?></span>
        </div>
        <?php endif; ?>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="total-box d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-uppercase small">Net Payable Amount</span>
                    <span class="h4 fw-bold mb-0">₹ <?php echo number_format($salary['net_salary'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="row mt-5 pt-5">
            <div class="col-6 text-center">
                <div style="border-top: 1px solid #333; width: 150px; margin: 0 auto;"></div>
                <p class="small mt-2">Employee Signature</p>
            </div>
            <div class="col-6 text-center">
                <div style="border-top: 1px solid #333; width: 150px; margin: 0 auto;"></div>
                <p class="small mt-2">Authorized Signatory</p>
            </div>
        </div>

        <div class="footer-note text-center">
            <p class="mb-0">This is a computer-generated salary slip and does not require a physical stamp for validation.</p>
            <p><?php echo $s['company_address'] ?? ''; ?></p>
        </div>
    </div>

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
