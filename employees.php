<?php
$page_title = 'Employee Master';
require_once 'includes/header.php';

if (!has_permission('employees', 'view')) {
    die("Access denied. You don't have permission to view this module.");
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_employee'])) {
    $mode_post = $_POST['mode'];
    if ($mode_post === 'add' && !has_permission('employees', 'add')) die("Unauthorized action.");
    if ($mode_post === 'edit' && !has_permission('employees', 'edit')) die("Unauthorized action.");

    $name = trim($_POST['name']);
    $mobile_number = trim($_POST['mobile_number']);
    $role = trim($_POST['role']);
    $salary = (float)$_POST['salary'];
    $week_off = trim($_POST['week_off']);
    
    if ($mode_post === 'add') {
        $stmt = $conn->prepare("INSERT INTO employees (name, mobile_number, role, salary, week_off) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdds", $name, $mobile_number, $role, $salary, $week_off);
    } else {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE employees SET name=?, mobile_number=?, role=?, salary=?, week_off=? WHERE id=?");
        $stmt->bind_param("ssddsi", $name, $mobile_number, $role, $salary, $week_off, $id);
    }
    
    if ($stmt->execute()) {
        redirect('employees.php?success=1');
    } else {
        redirect('employees.php?error=1');
    }
}

if (isset($_GET['delete'])) {
    if (!has_permission('employees', 'delete')) die("Unauthorized action.");
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM employees WHERE id = $id");
    redirect('employees.php?msg=Employee deleted');
}

$employee = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $employee = $conn->query("SELECT * FROM employees WHERE id = $id")->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Employee Master</h4>
        <?php if ($mode === 'list'): ?>
            <?php if (has_permission('employees', 'add')): ?>
            <a href="employees.php?mode=add" class="btn btn-gold"><i class="fa fa-user-plus me-1"></i> Add Employee</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="employees.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back to List</a>
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
                            <th>Employee Name</th>
                            <th>Mobile Number</th>
                            <th>Monthly Salary (₹)</th>
                            <th>Week Off</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM employees ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo $row['name']; ?></td>
                            <td><?php echo $row['mobile_number']; ?></td>
                            <td class="fw-bold"><?php echo format_currency($row['salary']); ?></td>
                            <td><span class="badge bg-soft-info text-info"><?php echo $row['week_off']; ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (has_permission('employees', 'edit')): ?>
                                    <a href="employees.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit"><i class="fa fa-edit"></i></a>
                                    <?php endif; ?>
                                    <?php if (has_permission('employees', 'delete')): ?>
                                    <a href="employees.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete"><i class="fa fa-trash"></i></a>
                                    <?php endif; ?>
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
    <div class="card card-bajot max-width-700 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-white"><?php echo $mode === 'add' ? 'Add New' : 'Edit'; ?> Employee</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($employee): ?>
                    <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Employee Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Full Name" value="<?php echo $employee ? htmlspecialchars($employee['name']) : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile Number *</label>
                        <input type="text" name="mobile_number" class="form-control" required placeholder="10 Digit Number" value="<?php echo $employee ? htmlspecialchars($employee['mobile_number']) : ''; ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Designation / Role</label>
                    <input type="text" name="role" class="form-control" placeholder="e.g. Sales Executive / Account" value="<?php echo $employee ? htmlspecialchars($employee['role']) : ''; ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Monthly Salary (₹) *</label>
                        <input type="number" step="0.01" name="salary" class="form-control" required placeholder="0.00" value="<?php echo $employee ? $employee['salary'] : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Week off Day Selection *</label>
                        <select name="week_off" class="form-select" required>
                            <option value="">Select Day</option>
                            <?php 
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'No Week Off'];
                            foreach ($days as $day) {
                                $selected = ($employee && $employee['week_off'] == $day) ? 'selected' : '';
                                echo "<option value=\"$day\" $selected>$day</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" name="save_employee" class="btn btn-gold"><?php echo $mode === 'add' ? 'Save' : 'Update'; ?> Employee <i class="fa fa-check ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
