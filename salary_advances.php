<?php
$page_title = 'Salary Advance (Upad)';
require_once 'includes/header.php';

if (!has_permission('salaries', 'view')) {
    die("Access denied.");
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_advance'])) {
    $employee_id = (int)$_POST['employee_id'];
    $date = $_POST['date'];
    $amount = (float)$_POST['amount'];
    $remarks = trim($_POST['remarks']);
    
    // 1. Get Employee Name
    $emp_res = $conn->query("SELECT name FROM employees WHERE id = $employee_id");
    $emp_name = ($emp_res->fetch_assoc())['name'] ?? 'Employee';

    // 2. Handle Expense Category (ADVANCE)
    $cat_res = $conn->query("SELECT id FROM expense_categories WHERE name LIKE '%ADVANCE%' LIMIT 1");
    if ($cat_res->num_rows > 0) {
        $cat_id = $cat_res->fetch_assoc()['id'];
    } else {
        $conn->query("INSERT INTO expense_categories (name) VALUES ('SALARY ADVANCE (UPAD)')");
        $cat_id = $conn->insert_id;
    }

    $dept_id = $_SESSION['dept_id'] ?? 1;
    $desc = "Advance (Upad) Paid to $emp_name. Note: $remarks";
    
    // 3. Create Expense Entry
    $exp_stmt = $conn->prepare("INSERT INTO expenses (category_id, dept_id, date, amount, description, payment_mode) VALUES (?, ?, ?, ?, ?, 'Cash')");
    $exp_stmt->bind_param("iisds", $cat_id, $dept_id, $date, $amount, $desc);
    $exp_stmt->execute();
    $expense_id = $conn->insert_id;

    // 4. Save Advance Record
    $stmt = $conn->prepare("INSERT INTO salary_advances (employee_id, date, amount, remarks, expense_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsi", $employee_id, $date, $amount, $remarks, $expense_id);
    
    if ($stmt->execute()) {
        redirect('salary_advances.php?success=1');
    } else {
        $msg = "Error: " . $conn->error;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $existing = $conn->query("SELECT expense_id FROM salary_advances WHERE id = $id")->fetch_assoc();
    if($existing && $existing['expense_id']) {
        $conn->query("DELETE FROM expenses WHERE id = " . $existing['expense_id']);
    }
    $conn->query("DELETE FROM salary_advances WHERE id = $id");
    redirect('salary_advances.php?msg=Deleted');
}

$advances = $conn->query("SELECT a.*, e.name as emp_name FROM salary_advances a JOIN employees e ON a.employee_id = e.id ORDER BY a.date DESC");
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Salary Advance (Upad)</h4>
        <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addAdvanceModal">
            <i class="fa fa-plus me-1"></i> Record New Advance
        </button>
    </div>
</div>

<div class="card card-bajot">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom datatable w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Amount</th>
                        <th>Remarks</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $advances->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d M, Y', strtotime($row['date'])); ?></td>
                        <td class="fw-bold"><?php echo $row['emp_name']; ?></td>
                        <td class="fw-bold text-danger">₹ <?php echo number_format($row['amount'], 2); ?></td>
                        <td class="small text-secondary-themed"><?php echo $row['remarks']; ?></td>
                        <td class="text-end">
                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <form method="POST">
                <div class="modal-header border-secondary text-white">
                    <h5 class="modal-title">Record Salary Advance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white">
                    <div class="mb-3">
                        <label class="form-label">Employee *</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee...</option>
                            <?php 
                            $emps = $conn->query("SELECT id, name FROM employees ORDER BY name ASC");
                            while($e = $emps->fetch_assoc()) echo "<option value='{$e['id']}'>{$e['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (₹) *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_advance" class="btn btn-gold">Save Advance Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
