<?php
$page_title = 'Stock Management';
require_once 'includes/header.php';

// Handle Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $p_id = (int)$_POST['product_id'];
    $qty_pcs = (float)$_POST['qty_pcs'];
    $qty_kgs = (float)$_POST['qty_kgs'];
    $type = $_POST['adj_type']; // 'add' or 'subtract'
    
    if ($type === 'subtract') {
        $qty_pcs = -$qty_pcs;
        $qty_kgs = -$qty_kgs;
    }
    
    $conn->query("UPDATE products SET total_pcs = total_pcs + $qty_pcs, total_kgs = total_kgs + $qty_kgs, current_stock = CASE WHEN unit = 'Pcs' THEN total_pcs ELSE total_kgs END WHERE id = $p_id");
    echo "<script>Swal.fire('Success', 'Stock adjusted successfully!', 'success');</script>";
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-white">Current Stock & Ledger</h4>
        <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
            <i class="fa fa-adjust me-1"></i> Manual Adjustment
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-bajot p-3">
            <h6 class="text-muted small">Total Items</h6>
            <h4 class="fw-bold"><?php echo $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0]; ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot p-3 border-danger">
            <h6 class="text-muted small">Low Stock Alert</h6>
            <h4 class="fw-bold text-danger"><?php echo $conn->query("SELECT COUNT(*) FROM products WHERE current_stock < 10")->fetch_row()[0]; ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot p-3 border-theme">
            <h6 class="text-muted small">Total Current Pcs</h6>
            <h4 class="fw-bold text-theme"><?php 
                $total_pcs_sum = $conn->query("SELECT SUM(total_pcs) FROM products")->fetch_row()[0];
                echo number_format($total_pcs_sum ?? 0, 2); 
            ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bajot p-3 border-theme">
            <h6 class="text-muted small">Total Current Kgs</h6>
            <h4 class="fw-bold text-theme"><?php 
                $total_kgs_sum = $conn->query("SELECT SUM(total_kgs) FROM products")->fetch_row()[0];
                echo number_format($total_kgs_sum ?? 0, 2); 
            ?></h4>
        </div>
    </div>
</div>

<div class="card card-bajot">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom datatable w-100">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th>Opening Stock</th>
                        <th>Current Pcs</th>
                        <th>Current Kgs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res = $conn->query("SELECT * FROM products ORDER BY current_stock ASC");
                    $footer_total_pcs = 0;
                    $footer_total_kgs = 0;
                    while ($row = $res->fetch_assoc()):
                        $status = ($row['current_stock'] < 10) ? '<span class="badge bg-danger">Low Stock</span>' : '<span class="badge bg-success">In Stock</span>';
                        $footer_total_pcs += $row['total_pcs'];
                        $footer_total_kgs += $row['total_kgs'];
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo $row['name']; ?></td>
                        <td><?php echo $row['unit']; ?></td>
                        <td class="small text-muted"><?php echo $row['opening_pcs']; ?> Pcs / <?php echo $row['opening_kgs']; ?> Kg</td>
                        <td class="fw-bold <?php echo ($row['total_pcs'] < 10) ? 'text-danger' : 'text-theme'; ?>"><?php echo $row['total_pcs']; ?></td>
                        <td class="fw-bold <?php echo ($row['total_kgs'] < 10) ? 'text-danger' : 'text-theme'; ?>"><?php echo $row['total_kgs']; ?></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="border-top border-secondary">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total:</td>
                        <td class="text-theme"><?php echo number_format($footer_total_pcs, 2); ?></td>
                        <td class="text-theme"><?php echo number_format($footer_total_kgs, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Manual Stock Adjustment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Product</label>
                        <select name="product_id" class="form-select bg-dark text-white border-secondary" required>
                            <?php 
                            $prods = $conn->query("SELECT id, name FROM products");
                            while($p = $prods->fetch_assoc()) echo "<option value='{$p['id']}'>{$p['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adj_type" class="form-select bg-dark text-white border-secondary">
                            <option value="add">Add Stock (+)</option>
                            <option value="subtract">Subtract Stock (-)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Qty (Pcs)</label>
                            <input type="number" step="0.01" name="qty_pcs" class="form-control bg-dark text-white border-secondary" required value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Qty (Kgs)</label>
                            <input type="number" step="0.01" name="qty_kgs" class="form-control bg-dark text-white border-secondary" required value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="adjust_stock" class="btn btn-gold">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
