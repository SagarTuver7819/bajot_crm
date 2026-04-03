<?php
$page_title = 'Expense Management';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        $category_id = (int)$_POST['category_id'];
        $party_id = !empty($_POST['party_id']) ? (int)$_POST['party_id'] : NULL;
        $dept_id = (int)$_SESSION['dept_id'];
        $date = trim($_POST['date']);
        $amount = (float)$_POST['amount'];
        $description = trim($_POST['description']);
        $payment_mode = $_POST['payment_mode'];

        if ($mode === 'add') {
            $stmt = $conn->prepare("INSERT INTO expenses (category_id, party_id, dept_id, date, amount, description, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisdss", $category_id, $party_id, $dept_id, $date, $amount, $description, $payment_mode);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE expenses SET category_id=?, party_id=?, date=?, amount=?, description=?, payment_mode=? WHERE id=?");
            $stmt->bind_param("iisdssi", $category_id, $party_id, $date, $amount, $description, $payment_mode, $id);
        }

        if ($stmt->execute()) {
            redirect('expenses.php?success=1');
        } else {
            redirect('expenses.php?error=1');
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM expenses WHERE id=$id");
    redirect('expenses.php?deleted=1');
}

// Fetch data for form
$expense = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM expenses WHERE id=$id");
    $expense = $result->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Expenses</h4>
        <?php if ($mode === 'list'): ?>
            <a href="expenses.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add New Expense
            </a>
        <?php else: ?>
            <a href="expenses.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mode === 'list'): ?>
    <!-- Filter Bar (Optional but good) -->
    <div class="card card-bajot mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end g-2">
                <div class="col-md-3">
                    <label class="form-label small">Category</label>
                    <select name="filter_cat" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php 
                        $cats = $conn->query("SELECT * FROM expense_categories ORDER BY name ASC");
                        while($c = $cats->fetch_assoc()) {
                            $sel = (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $c['id']) ? 'selected' : '';
                            echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Party</label>
                    <select name="filter_party" class="form-select form-select-sm">
                        <option value="">All Parties</option>
                        <?php 
                        $parts = $conn->query("SELECT id, name FROM parties ORDER BY name ASC");
                        while($p = $parts->fetch_assoc()) {
                            $sel = (isset($_GET['filter_party']) && $_GET['filter_party'] == $p['id']) ? 'selected' : '';
                            echo "<option value='{$p['id']}' $sel>{$p['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-gold w-100"><i class="fa fa-filter me-1"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="expenses.php" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-bajot">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom datatable w-100">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Party</th>
                            <th>Amount (₹)</th>
                            <th>Mode</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $where = "WHERE e.dept_id = " . (int)$_SESSION['dept_id'];
                        if(!empty($_GET['filter_cat'])) $where .= " AND e.category_id = " . (int)$_GET['filter_cat'];
                        if(!empty($_GET['filter_party'])) $where .= " AND e.party_id = " . (int)$_GET['filter_party'];

                        $sql = "SELECT e.*, ec.name as cat_name, p.name as party_name 
                                FROM expenses e 
                                JOIN expense_categories ec ON e.category_id = ec.id 
                                LEFT JOIN parties p ON e.party_id = p.id 
                                $where 
                                ORDER BY e.date DESC, e.id DESC";
                        
                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['cat_name']); ?></span></td>
                            <td><?php echo $row['party_name'] ? htmlspecialchars($row['party_name']) : '<span class="text-muted italic">N/A</span>'; ?></td>
                            <td class="fw-bold text-danger"><?php echo format_currency($row['amount']); ?></td>
                            <td>
                                <span class="badge <?php echo ($row['payment_mode'] == 'Cash') ? 'bg-success' : 'bg-primary'; ?>">
                                    <?php echo $row['payment_mode']; ?>
                                </span>
                            </td>
                            <td class="small"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="expenses.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php if (is_admin()): ?>
                                    <a href="expenses.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
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
    <div class="card card-bajot max-width-800 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Expense Entry</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($expense): ?>
                    <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expense Category *</label>
                        <div class="input-group">
                            <select name="category_id" class="form-select border-secondary" required>
                                <option value="">Select Category</option>
                                <?php 
                                $cats = $conn->query("SELECT * FROM expense_categories ORDER BY name ASC");
                                while($c = $cats->fetch_assoc()) {
                                    $sel = ($expense && $expense['category_id'] == $c['id']) ? 'selected' : '';
                                    echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                                }
                                ?>
                            </select>
                            <a href="expense_category.php?mode=add" class="btn btn-outline-gold"><i class="fa fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Party (Optional)</label>
                        <select name="party_id" class="form-select border-secondary">
                            <option value="">No Party / Cash Expense</option>
                            <?php 
                            $parts = $conn->query("SELECT id, name FROM parties ORDER BY name ASC");
                            while($p = $parts->fetch_assoc()) {
                                $sel = ($expense && $expense['party_id'] == $p['id']) ? 'selected' : '';
                                echo "<option value='{$p['id']}' $sel>{$p['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" name="date" class="form-control" required value="<?php echo $expense ? $expense['date'] : date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount (₹) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo $expense ? $expense['amount'] : ''; ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" class="form-select border-secondary">
                            <option value="Cash" <?php echo ($expense && $expense['payment_mode'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank" <?php echo ($expense && $expense['payment_mode'] == 'Bank') ? 'selected' : ''; ?>>Bank</option>
                        </select>
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label">Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Enter expense details..."><?php echo $expense ? htmlspecialchars($expense['description']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-gold">
                        <?php echo ($mode === 'add') ? 'Save' : 'Update'; ?> Expense <i class="fa fa-check ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
