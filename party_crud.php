<?php
$page_title = 'Party Management';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $mobile = trim($_POST['mobile']);
        $address = trim($_POST['address']);
        $opening_balance = (float)$_POST['opening_balance'];

        // Duplication Check (using cleaned name for safety in direct query)
        $clean_name = clean($name);
        $check_q = ($mode === 'add') ? 
            "SELECT id FROM parties WHERE name = '$clean_name'" : 
            "SELECT id FROM parties WHERE name = '$clean_name' AND id != " . (int)$_POST['id'];
        
        $check_res = $conn->query($check_q);
        if ($check_res->num_rows > 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Duplicate Party!',
                        text: 'A party with the name \"".htmlspecialchars($name)."\" already exists!',
                        confirmButtonColor: '#C9A14A'
                    });
                });
            </script>";
        } else {
            if ($mode === 'add') {
                $stmt = $conn->prepare("INSERT INTO parties (name, type, mobile, address, opening_balance) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssd", $name, $type, $mobile, $address, $opening_balance);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE parties SET name=?, type=?, mobile=?, address=?, opening_balance=? WHERE id=?");
                $stmt->bind_param("ssssdi", $name, $type, $mobile, $address, $opening_balance, $id);
            }

            if ($stmt->execute()) {
                redirect('party_crud.php?success=1');
            } else {
                redirect('party_crud.php?error=1');
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM parties WHERE id=$id");
    redirect('party_crud.php?deleted=1');
}

// Fetch data for form
$party = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM parties WHERE id=$id");
    $party = $result->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Customers & Suppliers</h4>
        <?php if ($mode === 'list'): ?>
            <a href="party_crud.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add New Party
            </a>
        <?php else: ?>
            <a href="party_crud.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>
<?php echo $msg; ?>


<?php if ($mode === 'list'): ?>
    <div class="card card-bajot">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom datatable w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Mobile</th>
                            <th>Address</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM parties ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <span class="badge <?php 
                                    if ($row['type'] == 'customer') echo 'bg-primary';
                                    elseif ($row['type'] == 'supplier') echo 'bg-info';
                                    else echo 'bg-warning text-dark';
                                ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td><?php echo $row['mobile']; ?></td>
                            <td class="small"><?php echo htmlspecialchars($row['address']); ?></td>
                            <td class="fw-bold"><?php echo format_currency($row['opening_balance']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="party_crud.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php if (is_admin()): ?>
                                    <a href="party_crud.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
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
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Party</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($party): ?>
                    <input type="hidden" name="id" value="<?php echo $party['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Party Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $party ? htmlspecialchars($party['name']) : ''; ?>" placeholder="Full Name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select border-secondary" required>
                            <option value="customer" <?php echo ($party && $party['type'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                            <option value="supplier" <?php echo ($party && $party['type'] == 'supplier') ? 'selected' : ''; ?>>Supplier</option>
                            <option value="both" <?php echo ($party && $party['type'] == 'both') ? 'selected' : ''; ?>>Both (Cust & Supp)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" value="<?php echo $party ? $party['mobile'] : ''; ?>" placeholder="10 Digit Mobile">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Complete Address"><?php echo $party ? htmlspecialchars($party['address']) : ''; ?></textarea>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Opening Balance (₹)</label>
                        <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?php echo $party ? $party['opening_balance'] : '0.00'; ?>">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-gold">
                        <?php echo ($mode === 'add') ? 'Save' : 'Update'; ?> Party <i class="fa fa-save ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
