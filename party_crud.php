<?php
$page_title = 'Party Management';
require_once 'includes/header.php';

if (!has_permission('parties', 'view')) {
    die("Access denied. You don't have permission to view this module.");
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        // Enforce Add/Edit Permission
        if ($mode === 'add' && !has_permission('parties', 'add')) die("Unauthorized action.");
        if ($mode === 'edit' && !has_permission('parties', 'edit')) die("Unauthorized action.");

        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $mobile = trim($_POST['mobile']);
        $address = trim($_POST['address']);
        $ob_alum = (float)$_POST['ob_alum'];
        $ob_pwdr = (float)$_POST['ob_pwdr'];
        $ob_anod = (float)$_POST['ob_anod'];
        $opening_balance = $ob_alum + $ob_pwdr + $ob_anod;

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
                $stmt = $conn->prepare("INSERT INTO parties (name, type, mobile, address, opening_balance, ob_alum, ob_pwdr, ob_anod) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdddd", $name, $type, $mobile, $address, $opening_balance, $ob_alum, $ob_pwdr, $ob_anod);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE parties SET name=?, type=?, mobile=?, address=?, opening_balance=?, ob_alum=?, ob_pwdr=?, ob_anod=? WHERE id=?");
                $stmt->bind_param("ssssddddi", $name, $type, $mobile, $address, $opening_balance, $ob_alum, $ob_pwdr, $ob_anod, $id);
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
    if (!has_permission('parties', 'delete')) die("Unauthorized action.");
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
            <?php if (has_permission('parties', 'add')): ?>
            <a href="party_crud.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add New Party
            </a>
            <?php endif; ?>
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
                                    <?php if (has_permission('parties', 'edit')): ?>
                                    <a href="party_crud.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (has_permission('parties', 'delete')): ?>
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
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Opening Balance Aluminium (₹)</label>
                        <input type="number" step="0.01" name="ob_alum" class="form-control" value="<?php echo $party ? $party['ob_alum'] : '0.00'; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Opening Balance Powder Coating (₹)</label>
                        <input type="number" step="0.01" name="ob_pwdr" class="form-control" value="<?php echo $party ? $party['ob_pwdr'] : '0.00'; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Opening Balance Anodizing (₹)</label>
                        <input type="number" step="0.01" name="ob_anod" class="form-control" value="<?php echo $party ? $party['ob_anod'] : '0.00'; ?>">
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
