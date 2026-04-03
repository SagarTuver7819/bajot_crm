<?php
$page_title = 'Employee Module';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_employee'])) {
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);
    $salary = (float)$_POST['salary'];
    
    $stmt = $conn->prepare("INSERT INTO employees (name, role, salary) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $role, $salary);
    
    if ($stmt->execute()) {
        redirect('employees.php?success=1');
    } else {
        redirect('employees.php?error=1');
    }
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Staff & HR</h4>
        <?php if ($mode === 'list'): ?>
            <a href="employees.php?mode=add" class="btn btn-gold"><i class="fa fa-user-plus me-1"></i> Add Employee</a>
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
                            <th>Name</th>
                            <th>Designation / Role</th>
                            <th>Salary (₹)</th>
                            <th>Joining Date</th>
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
                            <td><?php echo $row['role']; ?></td>
                            <td class="fw-bold"><?php echo format_currency($row['salary']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="employees.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn"><i class="fa fa-trash"></i></a>
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
    <div class="card card-bajot max-width-600 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-white">Add New Employee</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Employee Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="Full Name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Designation / Role</label>
                    <input type="text" name="role" class="form-control" placeholder="e.g. Sales Executive / Account">
                </div>
                <div class="mb-4">
                    <label class="form-label">Monthly Salary (₹)</label>
                    <input type="number" step="0.01" name="salary" class="form-control" required placeholder="0.00">
                </div>
                <div class="d-grid">
                    <button type="submit" name="save_employee" class="btn btn-gold">Save Employee <i class="fa fa-check ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
