<?php
$page_title = 'Inward (Purchase)';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle Purchase Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inward'])) {
    $party_id = (int)$_POST['party_id'];
    $date = trim($_POST['date']);
    $bill_no = trim($_POST['bill_no']);
    
    $product_ids = $_POST['product_id'];
    $units = $_POST['unit'];
    $qty_pcs_array = $_POST['qty_pcs'];
    $qty_kgs_array = $_POST['qty_kgs'];
    $rates = $_POST['rate'];
    
    $sub_total = 0;
    
    // Begin Transaction
    $conn->begin_transaction();
    
    try {
        $edit_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($edit_id > 0) {
            // Recover Old Stock & Delete Old Items (Subtract because Inward increased stock)
            $old_items = $conn->query("SELECT * FROM inward_items WHERE inward_id=$edit_id");
            while($iit = $old_items->fetch_assoc()) {
                $conn->query("UPDATE products SET total_pcs = total_pcs - " . $iit['qty_pcs'] . ", total_kgs = total_kgs - " . $iit['qty_kgs'] . ", current_stock = CASE WHEN unit = 'Pcs' THEN total_pcs ELSE total_kgs END WHERE id = " . $iit['product_id']);
            }
            $conn->query("DELETE FROM inward_items WHERE inward_id=$edit_id");
            
            // Update main record
            $stmt = $conn->prepare("UPDATE inwards SET party_id=?, date=?, bill_no=? WHERE id=?");
            $stmt->bind_param("issi", $party_id, $date, $bill_no, $edit_id);
            $stmt->execute();
            $inward_id = $edit_id;
        } else {
            // Insert into inwards
            $dept_id = (int)$_SESSION['dept_id'];
            $stmt = $conn->prepare("INSERT INTO inwards (dept_id, party_id, date, bill_no) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $dept_id, $party_id, $date, $bill_no);
            $stmt->execute();
            $inward_id = $conn->insert_id;
        }
        
        foreach ($product_ids as $key => $p_id) {
            $unit = $units[$key];
            $qty_pcs = (float)$qty_pcs_array[$key];
            $qty_kgs = (float)$qty_kgs_array[$key];
            $rate = (float)$rates[$key];
            
            $item_total = ($unit == 'Pcs') ? ($qty_pcs * $rate) : ($qty_kgs * $rate);
            
            $sub_total += $item_total;
            
            // Insert into inward_items
            $stmt_item = $conn->prepare("INSERT INTO inward_items (inward_id, product_id, unit, qty_pcs, qty_kgs, rate, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_item->bind_param("iisdddd", $inward_id, $p_id, $unit, $qty_pcs, $qty_kgs, $rate, $item_total);
            $stmt_item->execute();
            
            // Update Stock (Increase)
            $conn->query("UPDATE products SET total_pcs = total_pcs + $qty_pcs, total_kgs = total_kgs + $qty_kgs, current_stock = CASE WHEN unit = 'Pcs' THEN total_pcs ELSE total_kgs END WHERE id = $p_id");
        }
        
        $total_amount = $sub_total;
        $conn->query("UPDATE inwards SET sub_total=$sub_total, gst_amount=0, total_amount=$total_amount WHERE id=$inward_id");
        
        $conn->commit();
        redirect('inward_crud.php?success=1');
    } catch (Exception $e) {
        $conn->rollback();
        redirect('inward_crud.php?error=1');
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Reduct stock before deleting? PRD says stock increase on inward. So decrease on delete.
    $items = $conn->query("SELECT * FROM inward_items WHERE inward_id=$id");
    while($item = $items->fetch_assoc()) {
        $conn->query("UPDATE products SET total_pcs = total_pcs - " . $item['qty_pcs'] . ", total_kgs = total_kgs - " . $item['qty_kgs'] . ", current_stock = CASE WHEN unit = 'Pcs' THEN total_pcs ELSE total_kgs END WHERE id = " . $item['product_id']);
    }
    $conn->query("DELETE FROM inwards WHERE id=$id");
    redirect('inward_crud.php?deleted=1');
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Purchase Entries (Inward)</h4>
        <?php if ($mode === 'list'): ?>
            <a href="inward_crud.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> New Purchase
            </a>
        <?php else: ?>
            <a href="inward_crud.php" class="btn btn-outline-secondary">
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
                            <th>Date</th>
                            <th>Bill No.</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th>Total (₹)</th>
                            <th class="text-center">Share</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dept_id = (int)$_SESSION['dept_id'];
                        $res = $conn->query("SELECT i.*, p.name as supplier_name, p.mobile FROM inwards i JOIN parties p ON i.party_id = p.id WHERE i.dept_id = $dept_id ORDER BY i.id DESC");
                        
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                        $host = $_SERVER['HTTP_HOST'];
                        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                        $base_url = $protocol . "://" . $host . $path . "/";

                        while ($row = $res->fetch_assoc()):
                            $item_count = $conn->query("SELECT COUNT(*) FROM inward_items WHERE inward_id=".$row['id'])->fetch_row()[0];
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                            <td class="fw-bold">#<?php echo $row['bill_no']; ?></td>
                            <td><?php echo $row['supplier_name']; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $item_count; ?> Items</span></td>
                            <td class="fw-bold"><?php echo format_currency($row['total_amount']); ?></td>
                            <td class="text-center">
                                <?php if ($row['mobile']): ?>
                                    <a href="print_invoice.php?type=purchase&id=<?php echo $row['id']; ?>&autoshare=1" class="p-2 text-success" title="Send PDF on WhatsApp" target="_blank">
                                        <i class="fa-brands fa-whatsapp fs-4"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No Mobile</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="print_invoice.php?type=purchase&id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" title="Print" target="_blank">
                                        <i class="fa fa-print"></i>
                                    </a>
                                    <a href="inward_crud.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="inward_crud.php?mode=view&id=<?php echo $row['id']; ?>" class="btn btn-outline-info" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <?php if (is_admin()): ?>
                                    <a href="inward_crud.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
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
<?php 
elseif ($mode === 'add' || $mode === 'edit' || $mode === 'view'): 
    $inward = null;
    $inward_items = null;
    $next_bill_no = '';

    if (($mode === 'edit' || $mode === 'view') && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $inward = $conn->query("SELECT * FROM inwards WHERE id=$id")->fetch_assoc();
        
        $items_res = $conn->query("SELECT * FROM inward_items WHERE inward_id=$id");
        while($item = $items_res->fetch_assoc()) {
            $inward_items[] = $item;
        }
    }

    if ($mode === 'add') {
        $dept_id = (int)$_SESSION['dept_id'];
        $last_bill = $conn->query("SELECT bill_no FROM inwards WHERE dept_id = $dept_id ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if ($last_bill) {
            $num = (int)$last_bill['bill_no'];
            $next_bill_no = str_pad($num + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $next_bill_no = '01';
        }
    }
?>
    <div class="card card-bajot">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Purchase Entry</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" id="inwardForm">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($inward): ?>
                    <input type="hidden" name="id" value="<?php echo $inward['id']; ?>">
                <?php endif; ?>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Supplier *</label>
                        <div class="input-group">
                            <select name="party_id" class="form-select border-secondary shadow-sm" required>
                                <option value="">Select Supplier</option>
                                <?php 
                                $sups = $conn->query("SELECT id, name FROM parties WHERE type='supplier'");
                                while($s = $sups->fetch_assoc()) {
                                    $sel = ($inward && $inward['party_id'] == $s['id']) ? 'selected' : '';
                                    echo "<option value='{$s['id']}' $sel>{$s['name']}</option>";
                                }
                                ?>
                            </select>
                            <button type="button" class="btn btn-outline-gold" data-bs-toggle="modal" data-bs-target="#quickAddPartyModal" onclick="document.getElementById('quick_party_type').value='supplier';">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Purchase Date *</label>
                        <input type="date" name="date" class="form-control" value="<?php echo $inward ? $inward['date'] : date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bill / Invoice No.</label>
                        <input type="text" name="bill_no" class="form-control" value="<?php echo $inward ? $inward['bill_no'] : $next_bill_no; ?>" placeholder="e.g. 01">
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered border-secondary" id="itemsTable">
                        <thead class="bg-dark">
                            <tr>
                                <th style="width: 30%;">Product</th>
                                <th style="width: 10%;">Unit</th>
                                <th style="width: 10%;">Qty/Pcs</th>
                                <th style="width: 15%;">Weight/Kg</th>
                                <th style="width: 15%;">Rate (₹)</th>
                                <th style="width: 15%;">Total</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($inward_items): foreach($inward_items as $iit): ?>
                            <tr class="item-row">
                                <td>
                                    <div class="input-group">
                                        <select name="product_id[]" class="form-select border-secondary product-select" required>
                                            <option value="">Select Product</option>
                                            <?php 
                                            $dept_id = (int)$_SESSION['dept_id'];
                                            $prods = $conn->query("SELECT id, name, rate FROM products WHERE dept_id = $dept_id");
                                            while($p = $prods->fetch_assoc()) {
                                                $sel = ($iit['product_id'] == $p['id']) ? 'selected' : '';
                                                echo "<option value='{$p['id']}' data-rate='{$p['rate']}' $sel>{$p['name']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-gold" data-bs-toggle="modal" data-bs-target="#quickAddProductModal"><i class="fa fa-plus"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <select name="unit[]" class="form-select border-secondary unit-select">
                                        <option value="Pcs" <?php echo ($iit['unit'] == 'Pcs') ? 'selected' : ''; ?>>Pcs</option>
                                        <option value="Kgs" <?php echo ($iit['unit'] == 'Kgs') ? 'selected' : ''; ?>>Kgs</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="qty_pcs[]" class="form-control qty-pcs-input" required value="<?php echo $iit['qty_pcs']; ?>"></td>
                                <td><input type="number" step="0.01" name="qty_kgs[]" class="form-control qty-kgs-input" required value="<?php echo $iit['qty_kgs']; ?>"></td>
                                <td><input type="number" step="0.01" name="rate[]" class="form-control rate-input" required value="<?php echo $iit['rate']; ?>"></td>
                                <td><input type="text" class="form-control item-total" readonly value="<?php echo $iit['total']; ?>"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr class="item-row">
                                <td>
                                    <div class="input-group">
                                        <select name="product_id[]" class="form-select border-secondary product-select" required>
                                            <option value="">Select Product</option>
                                            <?php 
                                            $dept_id = (int)$_SESSION['dept_id'];
                                            $prods = $conn->query("SELECT id, name, rate FROM products WHERE dept_id = $dept_id");
                                            while($p = $prods->fetch_assoc()) echo "<option value='{$p['id']}' data-rate='{$p['rate']}'>{$p['name']}</option>";
                                            ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-gold" data-bs-toggle="modal" data-bs-target="#quickAddProductModal"><i class="fa fa-plus"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <select name="unit[]" class="form-select border-secondary unit-select">
                                        <option value="Pcs">Pcs</option>
                                        <option value="Kgs">Kgs</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="qty_pcs[]" class="form-control qty-pcs-input" required value="0"></td>
                                <td><input type="number" step="0.01" name="qty_kgs[]" class="form-control qty-kgs-input" required value="0"></td>
                                <td><input type="number" step="0.01" name="rate[]" class="form-control rate-input" required value="0"></td>
                                <td><input type="text" class="form-control item-total" readonly value="0.00"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa fa-times"></i></button></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($mode !== 'view'): ?>
                        <button type="button" class="btn btn-sm btn-outline-gold" id="addRow"><i class="fa fa-plus me-1"></i> Add Item</button>
                    <?php endif; ?>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="card card-bajot border-gold p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Amount:</span>
                                <span id="lblSubTotal">₹<?php echo $inward ? number_format($inward['sub_total'], 2) : '0.00'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top border-secondary pt-2">
                                <span style="color: var(--gold);">GRAND TOTAL:</span>
                                <span id="lblGrandTotal" style="color: var(--gold);">₹<?php echo $inward ? number_format($inward['total_amount'], 2) : '0.00'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <?php if ($mode !== 'view'): ?>
                        <button type="submit" name="save_inward" class="btn btn-gold px-5"><?php echo ($mode === 'edit') ? 'UPDATE' : 'SAVE'; ?> PURCHASE ENTRY <i class="fa fa-check ms-1"></i></button>
                    <?php else: ?>
                        <a href="print_invoice.php?type=purchase&id=<?php echo $inward['id']; ?>" class="btn btn-outline-warning px-5" target="_blank">PRINT VOUCHER <i class="fa fa-print ms-1"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#itemsTable tbody');
        const addBtn = document.getElementById('addRow');
        
        function calculateRow(row) {
            const unit = row.querySelector('.unit-select').value;
            const qty_pcs = parseFloat(row.querySelector('.qty-pcs-input').value) || 0;
            const qty_kgs = parseFloat(row.querySelector('.qty-kgs-input').value) || 0;
            const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
            
            let total = 0;
            if (unit === 'Pcs') {
                total = qty_pcs * rate;
            } else {
                total = qty_kgs * rate;
            }
            
            row.querySelector('.item-total').value = total.toFixed(2);
            calculateGrand();
        }

        function calculateGrand() {
            let sub = 0;
            document.querySelectorAll('.item-total').forEach(input => {
                sub += parseFloat(input.value) || 0;
            });
            const grand = sub;
            
            document.getElementById('lblSubTotal').innerText = '₹' + sub.toFixed(2);
            document.getElementById('lblGrandTotal').innerText = '₹' + grand.toFixed(2);
        }

        addBtn.addEventListener('click', () => {
            const newRow = table.rows[0].cloneNode(true);
            newRow.querySelectorAll('input').forEach(i => {
                if (i.classList.contains('item-total')) i.value = '0.00';
                else i.value = '0';
            });
            newRow.querySelector('.unit-select').selectedIndex = 0;
            newRow.querySelector('.product-select').selectedIndex = 0;
            table.appendChild(newRow);
            attachRowEvents(newRow);
        });

        function attachRowEvents(row) {
            row.querySelector('.qty-pcs-input').addEventListener('input', () => calculateRow(row));
            row.querySelector('.qty-kgs-input').addEventListener('input', () => calculateRow(row));
            row.querySelector('.unit-select').addEventListener('change', () => calculateRow(row));
            row.querySelector('.rate-input').addEventListener('input', () => calculateRow(row));
            row.querySelector('.product-select').addEventListener('change', function() {
                const rate = this.options[this.selectedIndex].dataset.rate || 0;
                row.querySelector('.rate-input').value = rate;
                calculateRow(row);
            });
            row.querySelector('.remove-row').addEventListener('click', () => {
                if(table.rows.length > 1) {
                    row.remove();
                    calculateGrand();
                }
            });
        }

        document.querySelectorAll('.item-row').forEach(attachRowEvents);
    });
    </script>
<?php endif; ?>

<?php include_once 'includes/quick_party_modal.php'; ?>
<?php include_once 'includes/quick_product_modal.php'; ?>
<?php require_once 'includes/footer.php'; ?>
