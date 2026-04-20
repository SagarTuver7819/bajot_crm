<?php
$page_title = 'Salary Management';
require_once 'includes/header.php';

if (!has_permission('salaries', 'view')) {
    die("Access denied.");
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_salary'])) {
    $mode_post = $_POST['mode'];
    $employee_id = (int)$_POST['employee_id'];
    $month = $_POST['month'];
    $year = (int)$_POST['year'];
    $monthly_salary = (float)$_POST['monthly_salary'];
    $working_days = (int)$_POST['working_days'];
    $total_days = (int)$_POST['total_days'];
    $week_offs = (int)$_POST['week_offs'];
    $net_salary = (float)$_POST['net_salary'];
    
    $total_advances = (float)$_POST['total_advances'];
    
    // 1. Get Employee Name
    $emp_res = $conn->query("SELECT name FROM employees WHERE id = $employee_id");
    $emp_name = ($emp_res->fetch_assoc())['name'] ?? 'Employee';

    // 2. Handle Expense Category
    $cat_res = $conn->query("SELECT id FROM expense_categories WHERE name LIKE '%SELARY%' OR name LIKE '%SALARY%' LIMIT 1");
    if ($cat_res->num_rows > 0) {
        $cat_id = $cat_res->fetch_assoc()['id'];
    } else {
        $conn->query("INSERT INTO expense_categories (name) VALUES ('SALARY EXPENSE')");
        $cat_id = $conn->insert_id;
    }

    $dept_id = $_SESSION['dept_id'] ?? 1;
    $date = date('Y-m-d');
    $desc = "Salary Paid to $emp_name for $month $year";

    if ($mode_post === 'add') {
        // Insert Expense first to get ID
        $exp_stmt = $conn->prepare("INSERT INTO expenses (category_id, dept_id, date, amount, description, payment_mode) VALUES (?, ?, ?, ?, ?, 'Cash')");
        $exp_stmt->bind_param("iisds", $cat_id, $dept_id, $date, $net_salary, $desc);
        $exp_stmt->execute();
        $expense_id = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO salaries (employee_id, month, year, monthly_salary, working_days, total_days, week_offs, net_salary, total_advances, expense_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isidiiiddi", $employee_id, $month, $year, $monthly_salary, $working_days, $total_days, $week_offs, $net_salary, $total_advances, $expense_id);
    } else {
        $salary_id = (int)$_POST['id'];
        $existing_salary = $conn->query("SELECT expense_id FROM salaries WHERE id = $salary_id")->fetch_assoc();
        $expense_id = $existing_salary['expense_id'];

        if ($expense_id) {
            // Update existing expense
            $exp_stmt = $conn->prepare("UPDATE expenses SET category_id=?, amount=?, description=? WHERE id=?");
            $exp_stmt->bind_param("idsi", $cat_id, $net_salary, $desc, $expense_id);
            $exp_stmt->execute();
        } else {
            // Create missing expense and link it
            $exp_stmt = $conn->prepare("INSERT INTO expenses (category_id, dept_id, date, amount, description, payment_mode) VALUES (?, ?, ?, ?, ?, 'Cash')");
            $exp_stmt->bind_param("iisds", $cat_id, $dept_id, $date, $net_salary, $desc);
            $exp_stmt->execute();
            $expense_id = $conn->insert_id;
            
            $conn->query("UPDATE salaries SET expense_id = $expense_id WHERE id = $salary_id");
        }

        $stmt = $conn->prepare("UPDATE salaries SET employee_id=?, month=?, year=?, monthly_salary=?, working_days=?, total_days=?, week_offs=?, net_salary=?, total_advances=? WHERE id=?");
        $stmt->bind_param("isidiiiddi", $employee_id, $month, $year, $monthly_salary, $working_days, $total_days, $week_offs, $net_salary, $total_advances, $salary_id);
    }
    
    if ($stmt->execute()) {
        $last_id = ($mode_post === 'add') ? $conn->insert_id : $salary_id;
        redirect("salaries.php?success=1&last_id=$last_id");
    } else {
        redirect('salaries.php?error=1');
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Also delete linked expense
    $existing = $conn->query("SELECT expense_id FROM salaries WHERE id = $id")->fetch_assoc();
    if ($existing && $existing['expense_id']) {
        $conn->query("DELETE FROM expenses WHERE id = " . $existing['expense_id']);
    }
    $conn->query("DELETE FROM salaries WHERE id = $id");
    redirect('salaries.php?msg=Record deleted');
}

$salary_data = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $salary_data = $conn->query("SELECT s.*, e.mobile_number, e.week_off as emp_week_off FROM salaries s JOIN employees e ON s.employee_id = e.id WHERE s.id = $id")->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <?php if (isset($_GET['success']) && isset($_GET['last_id'])): ?>
            <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                <span><i class="fa fa-check-circle me-2"></i> Salary calculated and expense recorded successfully!</span>
                <a href="print_salary.php?id=<?php echo $_GET['last_id']; ?>" class="btn btn-dark btn-sm" target="_blank"><i class="fa fa-print me-1"></i> Print Receipt</a>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-theme">Salary Calculation</h4>
        <?php if ($mode === 'list'): ?>
            <a href="salaries.php?mode=add" class="btn btn-gold"><i class="fa fa-calculator me-1"></i> New Calculation</a>
        <?php else: ?>
            <a href="salaries.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back to List</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mode === 'list'): ?>
    <div class="card card-bajot">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom datatable w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Month/Year</th>
                            <th>Base Salary</th>
                            <th>Days Worked</th>
                            <th>Net Paid</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT s.*, e.name as emp_name FROM salaries s JOIN employees e ON s.employee_id = e.id ORDER BY s.id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo $row['emp_name']; ?></td>
                            <td><?php echo $row['month'] . ' ' . $row['year']; ?></td>
                            <td><?php echo format_currency($row['monthly_salary']); ?></td>
                            <td><?php echo $row['working_days']; ?> / <?php echo $row['total_days']; ?></td>
                            <td class="fw-bold text-success"><?php echo format_currency($row['net_salary']); ?></td>
                            <td class="small"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="salaries.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit"><i class="fa fa-edit"></i></a>
                                    <a href="print_salary.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" target="_blank" title="Print Slip"><i class="fa fa-print"></i></a>
                                    <a href="salaries.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete"><i class="fa fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card card-bajot max-width-800 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-white"><?php echo $salary_data ? 'Edit' : 'Generate'; ?> Salary Receipt</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" id="salaryForm">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if($salary_data): ?>
                    <input type="hidden" name="id" value="<?php echo $salary_data['id']; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Employee *</label>
                        <select name="employee_id" id="employee_id" class="form-select border-secondary" required>
                            <option value="">Select Employee...</option>
                            <?php 
                            $emps = $conn->query("SELECT * FROM employees ORDER BY name ASC");
                            while($e = $emps->fetch_assoc()):
                                $sel = ($salary_data && $salary_data['employee_id'] == $e['id']) ? 'selected' : '';
                            ?>
                                <option value='<?php echo $e['id']; ?>' <?php echo $sel; ?>><?php echo $e['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Month</label>
                        <select name="month" id="month" class="form-select border-secondary">
                            <?php 
                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach($months as $m) {
                                $sel = ($salary_data ? ($salary_data['month'] == $m) : ($m == date('F'))) ? 'selected' : '';
                                echo "<option value='$m' $sel>$m</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" id="year" class="form-control" value="<?php echo $salary_data ? $salary_data['year'] : date('Y'); ?>">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monthly Salary (Base)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="monthly_salary" id="monthly_salary" class="form-control" readonly value="<?php echo $salary_data ? $salary_data['monthly_salary'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Week Off Day</label>
                        <input type="text" id="week_off_day" class="form-control" readonly disabled value="<?php echo $salary_data ? $salary_data['emp_week_off'] : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" id="emp_mobile" class="form-control" readonly disabled value="<?php echo $salary_data ? $salary_data['mobile_number'] : ''; ?>">
                    </div>
                </div>

                <hr class="border-secondary opacity-25 my-4">

                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Total Days</label>
                        <input type="number" name="total_days" id="total_days" class="form-control" value="<?php echo $salary_data ? $salary_data['total_days'] : '30'; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Week Offs</label>
                        <input type="number" name="week_offs" id="week_offs" class="form-control" value="<?php echo $salary_data ? $salary_data['week_offs'] : '4'; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Present Days *</label>
                        <input type="number" name="working_days" id="working_days" class="form-control" required placeholder="0" value="<?php echo $salary_data ? $salary_data['working_days'] : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Monthly Advances (Upad)</label>
                        <div class="input-group">
                            <span class="input-group-text text-danger">₹</span>
                            <input type="number" step="0.01" name="total_advances" id="total_advances" class="form-control text-danger fw-bold" readonly value="0.00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Net Payable Salary</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white">₹</span>
                            <input type="number" step="0.01" name="net_salary" id="net_salary" class="form-control fw-bold text-success" readonly value="<?php echo $salary_data ? $salary_data['net_salary'] : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 small border-0 bg-soft-info text-info">
                    <i class="fa fa-info-circle me-1"></i> 
                    <strong>Formula:</strong> [(Base Salary / (Total Days - Week Offs)) * Present Days] - Monthly Advances.
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="save_salary" class="btn btn-gold btn-lg">Save & Confirm Payment <i class="fa fa-check-circle ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        const salaryInp = $('#monthly_salary');
        const weekOffInp = $('#week_off_day');
        const mobileInp = $('#emp_mobile');
        
        const workingInp = $('#working_days');
        const totalInp = $('#total_days');
        const netInp = $('#net_salary');
        const advanceInp = $('#total_advances');

        // Fetch Employee Data using Select2 Compatible Event
        const fetchEssentialData = () => {
            const empId = $('#employee_id').val();
            const month = $('#month').val();
            const year = $('#year').val();
            
            if(!empId) return;
            
            // Fetch Basic Salary info
            $.getJSON('ajax_employee_salary.php', { employee_id: empId }, function(data) {
                if(data) {
                    $('#monthly_salary').val(data.salary);
                    $('#week_off_day').val(data.week_off);
                    $('#emp_mobile').val(data.mobile_number);
                    updateDaysInMonth();
                }
            });

            // Fetch Monthly Advances (Upad)
            $.getJSON('ajax_get_advances.php', { employee_id: empId, month: month, year: year }, function(data) {
                advanceInp.val(parseFloat(data.total || 0).toFixed(2));
                calculateSalary();
            });
        };

        $('#employee_id, #month, #year').on('change', fetchEssentialData);

        // Calculation Logic: (Base Salary / (Total Days - Week Offs)) * Present Days - Advances
        const calculateSalary = () => {
            const base = parseFloat($('#monthly_salary').val()) || 0;
            const working = parseFloat(workingInp.val()) || 0;
            const total = parseFloat(totalInp.val()) || 30;
            const weekOffs = parseFloat($('#week_offs').val()) || 0;
            const advances = parseFloat(advanceInp.val()) || 0;
            
            const effectiveDays = total - weekOffs;
            
            if(base > 0 && effectiveDays > 0) {
                const netWithoutAdvance = (base / effectiveDays) * working;
                const finalNet = netWithoutAdvance - advances;
                netInp.val(finalNet.toFixed(2));
            } else {
                netInp.val('0.00');
            }
        };

        workingInp.on('input', calculateSalary);
        totalInp.on('input', calculateSalary);
        $('#week_offs').on('input', calculateSalary);
        
        // Auto-calculate days in month and Week Off count
        const updateDaysInMonth = () => {
            const monthStr = $('#month').val();
            const year = parseInt($('#year').val());
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const monthIdx = monthNames.indexOf(monthStr);
            
            // Total Days
            const daysInMonth = new Date(year, monthIdx + 1, 0).getDate();
            $('#total_days').val(daysInMonth);
            
            // Week Offs in Month Calculation
            const empWeekOffDay = $('#week_off_day').val();
            if (empWeekOffDay && empWeekOffDay !== 'No Week Off') {
                let count = 0;
                let date = new Date(year, monthIdx, 1);
                while (date.getMonth() === monthIdx) {
                    const currentDayName = date.toLocaleDateString('en-US', { weekday: 'long' });
                    if (currentDayName === empWeekOffDay) {
                        count++;
                    }
                    date.setDate(date.getDate() + 1);
                }
                $('#week_offs').val(count);
            } else {
                $('#week_offs').val(0);
            }
            
            calculateSalary();
        };

        $('#month, #year').on('change input', updateDaysInMonth);
        
        // Run on load to fetch data if editing or re-loading
        fetchEssentialData();
        updateDaysInMonth();
    });
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
