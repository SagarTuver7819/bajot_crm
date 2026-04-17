<?php
$page_title = 'Bank Accounts';
require_once 'includes/header.php';

if (!has_permission('banks', 'view')) {
    die("Access denied. You don't have permission to view this module.");
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        if ($mode === 'add' && !has_permission('banks', 'add')) die("Unauthorized action.");
        if ($mode === 'edit' && !has_permission('banks', 'edit')) die("Unauthorized action.");

        $name = trim($_POST['name']);
        $branch = trim($_POST['branch']);
        $account_no = trim($_POST['account_no']);
        $opening_balance = (float)$_POST['opening_balance'];

        if ($mode === 'add') {
            $stmt = $conn->prepare("INSERT INTO banks (name, branch, account_no, opening_balance) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $name, $branch, $account_no, $opening_balance);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE banks SET name=?, branch=?, account_no=?, opening_balance=? WHERE id=?");
            $stmt->bind_param("sssdi", $name, $branch, $account_no, $opening_balance, $id);
        }

        if ($stmt->execute()) {
            redirect('banks.php?success=1');
        } else {
            redirect('banks.php?error=1');
        }
    }
}

if (isset($_GET['delete'])) {
    if (!has_permission('banks', 'delete')) die("Unauthorized action.");
    $id = (int)$_GET['delete'];
    
    // Check if bank is used in vouchers
    $check = $conn->query("SELECT id FROM vouchers WHERE bank_id = $id LIMIT 1");
    if ($check->num_rows > 0) {
        echo "<script>alert('Cannot delete bank account. It has associated transactions.'); window.location='banks.php';</script>";
        exit;
    }

    $conn->query("DELETE FROM banks WHERE id=$id");
    redirect('banks.php?deleted=1');
}

// Fetch data for form
$bank = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM banks WHERE id=$id");
    $bank = $result->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Bank Accounts</h4>
        <?php if ($mode === 'list'): ?>
            <?php if (has_permission('banks', 'add')): ?>
            <a href="banks.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add Bank Account
            </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="banks.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
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
                            <th>Bank Name</th>
                            <th>Branch</th>
                            <th>Account No</th>
                            <th>Opening Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM banks ORDER BY name ASC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo htmlspecialchars($row['account_no']); ?></td>
                            <td class="fw-bold"><?php echo format_currency($row['opening_balance']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (has_permission('banks', 'edit')): ?>
                                    <a href="banks.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (has_permission('banks', 'delete')): ?>
                                    <a href="banks.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
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
    <div class="card card-bajot max-width-600 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Bank Account</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($bank): ?>
                    <input type="hidden" name="id" value="<?php echo $bank['id']; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Bank Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo $bank ? htmlspecialchars($bank['name']) : ''; ?>" placeholder="e.g. SBI, HDFC">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Branch</label>
                    <input type="text" name="branch" class="form-control" value="<?php echo $bank ? htmlspecialchars($bank['branch']) : ''; ?>" placeholder="Branch Name">
                </div>

                <div class="mb-3">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_no" class="form-control" value="<?php echo $bank ? htmlspecialchars($bank['account_no']) : ''; ?>" placeholder="Account No">
                </div>

                <div class="mb-4">
                    <label class="form-label">Opening Balance (₹)</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?php echo $bank ? $bank['opening_balance'] : '0.00'; ?>">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-gold">
                        <?php echo ($mode === 'add') ? 'Save' : 'Update'; ?> Bank Account <i class="fa fa-save ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
