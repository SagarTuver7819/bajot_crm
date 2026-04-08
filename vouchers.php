<?php
$page_title = 'Cash / Bank Vouchers';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle Voucher Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $type = trim($_POST['type']);
    $party_id = (int)$_POST['party_id'] ?: null;
    $amount = (float)$_POST['amount'];
    $date = trim($_POST['date']);
    $desc = trim($_POST['description']);
    $edit_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($edit_id > 0) {
        $stmt = $conn->prepare("UPDATE vouchers SET type=?, party_id=?, amount=?, date=?, description=? WHERE id=?");
        $stmt->bind_param("sidssi", $type, $party_id, $amount, $date, $desc, $edit_id);
    } else {
        $dept_id = (int)$_SESSION['dept_id'];
        $stmt = $conn->prepare("INSERT INTO vouchers (dept_id, type, party_id, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isidss", $dept_id, $type, $party_id, $amount, $date, $desc);
    }
    
    if ($stmt->execute()) {
        redirect('vouchers.php?success=1');
    } else {
        redirect('vouchers.php?error=1');
    }
}
// Fetch data for form
$voucher = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM vouchers WHERE id=$id");
    $voucher = $res->fetch_assoc();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM vouchers WHERE id=$id");
    redirect('vouchers.php?deleted=1');
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Cash & Bank Transactions</h4>
        <?php if ($mode === 'list'): ?>
            <div class="d-flex gap-2">
                <a href="vouchers.php?mode=add&type=receipt" class="btn btn-success"><i class="fa fa-arrow-down me-1"></i> Receipt</a>
                <a href="vouchers.php?mode=add&type=payment" class="btn btn-danger"><i class="fa fa-arrow-up me-1"></i> Payment</a>
                <a href="vouchers.php?mode=add&type=expense" class="btn btn-info"><i class="fa fa-minus me-1"></i> Expense</a>
            </div>
        <?php else: ?>
            <a href="vouchers.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back to List</a>
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
                            <th>Date</th>
                            <th>Type</th>
                            <th>Party / Payee</th>
                            <th>Amount (₹)</th>
                            <th>Description</th>
                            <th class="text-center">Share</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dept_id = (int)$_SESSION['dept_id'];
                        $res = $conn->query("SELECT v.*, p.name as party_name, p.mobile FROM vouchers v LEFT JOIN parties p ON v.party_id = p.id WHERE v.dept_id = $dept_id ORDER BY v.date DESC");
                        
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                        $host = $_SERVER['HTTP_HOST'];
                        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                        $base_url = $protocol . "://" . $host . $path . "/";

                        while ($row = $res->fetch_assoc()):
                            $color = ($theme === 'dark' ? 'text-white' : 'text-dark');
                            if($row['type'] == 'receipt') $color = 'text-success';
                            elseif($row['type'] == 'payment') $color = 'text-danger';
                            elseif($row['type'] == 'expense') $color = 'text-info';
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                            <td><span class="badge <?php echo ($row['type'] == 'receipt') ? 'bg-success' : (($row['type'] == 'payment') ? 'bg-danger' : 'bg-info'); ?>"><?php echo strtoupper($row['type']); ?></span></td>
                            <td><?php echo $row['party_name'] ?: 'General / Self'; ?></td>
                            <td class="fw-bold <?php echo $color; ?>"><?php echo format_currency($row['amount']); ?></td>
                            <td class="small"><?php echo $row['description']; ?></td>
                            <td class="text-center">
                                <?php if ($row['mobile']): 
                                    $wa_phone = preg_replace('/[^0-9]/', '', $row['mobile']);
                                    if (strlen($wa_phone) == 10) $wa_phone = "91" . $wa_phone;
                                ?>
                                    <a href="https://wa.me/<?php echo $wa_phone; ?>?text=<?php echo urlencode("Hello " . ($row['party_name'] ?: 'Customer') . ",\n\n" . ucfirst($row['type']) . " Voucher details for amount ₹" . number_format($row['amount'], 2) . ".\nDate: " . date('d-m-Y', strtotime($row['date'])) . "\n\nView here: " . $base_url . "print_invoice.php?type=voucher&id=" . $row['id']); ?>" class="p-2 text-success" title="Share on WhatsApp" target="_blank">
                                        <i class="fa-brands fa-whatsapp fs-4"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="print_invoice.php?type=voucher&id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" target="_blank"><i class="fa fa-print"></i></a>
                                    <a href="vouchers.php?mode=edit&type=<?php echo $row['type']; ?>&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold"><i class="fa fa-edit"></i></a>
                                    <?php if (is_admin()): ?>
                                    <a href="vouchers.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger delete-btn"><i class="fa fa-trash"></i></a>
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
    <?php $vtype = (isset($voucher) && $voucher) ? $voucher['type'] : ($_GET['type'] ?? 'receipt'); ?>
    <div class="card card-bajot max-width-600 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($vtype); ?> Voucher</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="type" value="<?php echo $vtype; ?>">
                <?php if ($voucher): ?>
                    <input type="hidden" name="id" value="<?php echo $voucher['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Transaction Date *</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $voucher ? $voucher['date'] : date('Y-m-d'); ?>" required>
                </div>
                
                <?php if ($vtype !== 'expense'): ?>
                <div class="mb-3">
                    <label class="form-label">Select Party / Person</label>
                    <div class="input-group">
                        <select name="party_id" class="form-select border-secondary">
                            <option value="">Select Party</option>
                            <?php 
                            $types = ($vtype == 'receipt') ? "'customer'" : "'supplier'";
                            $ps = $conn->query("SELECT id, name FROM parties WHERE type=$types");
                            while($p = $ps->fetch_assoc()) {
                                $sel = ($voucher && $voucher['party_id'] == $p['id']) ? 'selected' : '';
                                echo "<option value='{$p['id']}' $sel>{$p['name']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn btn-outline-gold" data-bs-toggle="modal" data-bs-target="#quickAddPartyModal" onclick="document.getElementById('quick_party_type').value='<?php echo ($vtype == 'receipt') ? 'customer' : 'supplier'; ?>';">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Amount (₹) *</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $voucher ? $voucher['amount'] : ''; ?>" placeholder="0.00" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description / Remarks</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Notes..."><?php echo $voucher ? $voucher['description'] : ''; ?></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" name="save_voucher" class="btn btn-gold"><?php echo ($vtype == 'receipt') ? (($voucher ? 'Update' : 'Receive') . ' Payment') : (($vtype == 'payment') ? (($voucher ? 'Update' : 'Make') . ' Payment') : (($voucher ? 'Update' : 'Save') . ' Expense')); ?> <i class="fa fa-arrow-right ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include_once 'includes/quick_party_modal.php'; ?>
<?php require_once 'includes/footer.php'; ?>
