<?php
$page_title = 'Product Management';
require_once 'includes/header.php';

if (!has_permission('products', 'view')) {
    die("Access denied. You don't have permission to view this module.");
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        // Enforce Add/Edit Permission
        if ($mode === 'add' && !has_permission('products', 'add')) die("Unauthorized action.");
        if ($mode === 'edit' && !has_permission('products', 'edit')) die("Unauthorized action.");

        $name = trim($_POST['name']);
        $unit = trim($_POST['unit']);
        $opening_pcs = (float)$_POST['opening_pcs'];
        $opening_kgs = (float)$_POST['opening_kgs'];
        $rate = (float)$_POST['rate'];
        // $current_stock will be derived or set initially to opening values

        // Duplication Check (using cleaned name for safety in direct query)
        $clean_name = clean($name);
        $dept_id = (int)$_SESSION['dept_id'];
        $check_q = ($mode === 'add') ? 
            "SELECT id FROM products WHERE name = '$clean_name' AND dept_id = $dept_id" : 
            "SELECT id FROM products WHERE name = '$clean_name' AND dept_id = $dept_id AND id != " . (int)$_POST['id'];
        
        $check_res = $conn->query($check_q);
        if ($check_res->num_rows > 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Duplicate Product!',
                        text: 'A product with the name \"".htmlspecialchars($name)."\" already exists!',
                        confirmButtonColor: '#C9A14A'
                    });
                });
            </script>";
        } else {
            if ($mode === 'add') {
                $dept_id = (int)$_SESSION['dept_id'];
                $stmt = $conn->prepare("INSERT INTO products (dept_id, name, unit, opening_pcs, opening_kgs, total_pcs, total_kgs, rate, current_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $current_stock = ($unit === 'Pcs') ? $opening_pcs : $opening_kgs;
                $stmt->bind_param("issdddddd", $dept_id, $name, $unit, $opening_pcs, $opening_kgs, $opening_pcs, $opening_kgs, $rate, $current_stock);
            } else {
                $id = (int)$_POST['id'];
                // Update product info. When user is editing opening stock, we also update total_pcs and total_kgs.
                // This ensures that the 'Current Stock' reflects the values they just entered.
                $stmt = $conn->prepare("UPDATE products SET name=?, unit=?, opening_pcs=?, opening_kgs=?, total_pcs=?, total_kgs=?, rate=?, current_stock=CASE WHEN unit = 'Pcs' THEN ? ELSE ? END WHERE id=?");
                $stmt->bind_param("ssdddddddi", $name, $unit, $opening_pcs, $opening_kgs, $opening_pcs, $opening_kgs, $rate, $opening_pcs, $opening_kgs, $id);
            }

            if ($stmt->execute()) {
                redirect('product_crud.php?success=1');
            } else {
                redirect('product_crud.php?error=1');
            }
        }
    }
}

if (isset($_GET['delete'])) {
    if (!has_permission('products', 'delete')) die("Unauthorized action.");
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    redirect('product_crud.php?deleted=1');
}

// Fetch data for form
$product = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM products WHERE id=$id");
    $product = $result->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Products & Inventory</h4>
        <?php if ($mode === 'list'): ?>
            <?php if (has_permission('products', 'add')): ?>
            <a href="product_crud.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add New Product
            </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="product_crud.php" class="btn btn-outline-secondary">
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
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Unit</th>
                            <th>Opening Stock (<?php echo ($_SESSION['dept_id'] == 1 ? 'Pcs/' : ''); ?>Kg)</th>
                            <th>Current Stock (<?php echo ($_SESSION['dept_id'] == 1 ? 'Pcs/' : ''); ?>Kg)</th>
                             <th>Rate (₹)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dept_id = (int)$_SESSION['dept_id'];
                        $res = $conn->query("SELECT * FROM products WHERE dept_id = $dept_id ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['unit']; ?></td>
                            <td><?php if($_SESSION['dept_id'] == 1) echo $row['opening_pcs'] . " / "; echo $row['opening_kgs']; ?></td>
                            <td class="fw-bold text-success">
                                <?php if($_SESSION['dept_id'] == 1) echo $row['total_pcs'] . " Pcs / "; echo $row['total_kgs'] . " Kg"; ?>
                            </td>
                            <td><?php echo format_currency($row['rate']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (has_permission('products', 'edit')): ?>
                                    <a href="product_crud.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (has_permission('products', 'delete')): ?>
                                    <a href="product_crud.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
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
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Product</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($product): ?>
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>" placeholder="Full Name">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Unit *</label>
                        <select name="unit" class="form-select border-secondary" required>
                            <?php if ($_SESSION['dept_id'] == 1): ?>
                            <option value="Pcs" <?php echo ($product && $product['unit'] == 'Pcs') ? 'selected' : ''; ?>>Pcs</option>
                            <?php endif; ?>
                            <option value="Kgs" <?php echo ($product && $product['unit'] == 'Kgs' || $_SESSION['dept_id'] != 1) ? 'selected' : ''; ?>>Kgs</option>
                        </select>
                    </div>
                    <?php if ($_SESSION['dept_id'] == 1): ?>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Opening Pcs *</label>
                        <input type="number" step="0.01" name="opening_pcs" class="form-control" value="<?php echo $product ? $product['opening_pcs'] : '0.00'; ?>">
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="opening_pcs" value="0">
                    <?php endif; ?>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Opening Kgs *</label>
                        <input type="number" step="0.01" name="opening_kgs" class="form-control" value="<?php echo $product ? $product['opening_kgs'] : '0.00'; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Default Rate (₹)</label>
                        <input type="number" step="0.01" name="rate" class="form-control" value="<?php echo $product ? $product['rate'] : '0.00'; ?>">
                    </div>
                    <?php if ($mode === 'edit'): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current Stock (Derived)</label>
                        <div class="form-control bg-light">
                            <?php if ($_SESSION['dept_id'] == 1) echo $product['total_pcs'] . " Pcs / "; echo $product['total_kgs'] . " Kg"; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-12 mb-4">
                        <div class="alert alert-info py-2">
                            <i class="fa fa-info-circle me-1"></i> Opening Stock sets the initial quantities. Transactions will update the <strong>Current Stock</strong>.
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-gold">
                        <?php echo ($mode === 'add') ? 'Save' : 'Update'; ?> Product <i class="fa fa-save ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
