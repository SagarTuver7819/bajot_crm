<?php
$vmethod = isset($_GET['vmethod']) ? $_GET['vmethod'] : 'cash';
$page_title = ($vmethod == 'bank' ? 'Bank' : 'Cash') . ' Vouchers';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle Voucher Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $type = trim($_POST['type']);
    $payment_method = trim($_POST['payment_method']);
    $bank_id = (isset($_POST['bank_id']) && $_POST['bank_id'] != '') ? (int)$_POST['bank_id'] : null;
    $party_id = (int)$_POST['party_id'] ?: null;
    $amount = (float)$_POST['amount'];
    $date = trim($_POST['date']);
    $desc = trim($_POST['description']);
    $edit_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($edit_id > 0) {
        $stmt = $conn->prepare("UPDATE vouchers SET type=?, payment_method=?, bank_id=?, party_id=?, amount=?, date=?, description=? WHERE id=?");
        $stmt->bind_param("ssiiidss", $type, $payment_method, $bank_id, $party_id, $amount, $date, $desc, $edit_id);
    } else {
        $dept_id = (int)$_SESSION['dept_id'];
        $stmt = $conn->prepare("INSERT INTO vouchers (dept_id, type, payment_method, bank_id, party_id, amount, date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiids", $dept_id, $type, $payment_method, $bank_id, $party_id, $amount, $date, $desc);
    }
    
    if ($stmt->execute()) {
        redirect('vouchers.php?vmethod=' . $payment_method . '&success=1');
    } else {
        redirect('vouchers.php?vmethod=' . $payment_method . '&error=1');
    }
}
// Fetch data for form
$voucher = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM vouchers WHERE id=$id");
    $voucher = $res->fetch_assoc();
    $vmethod = $voucher['payment_method'];
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM vouchers WHERE id=$id");
    redirect('vouchers.php?vmethod=' . $vmethod . '&deleted=1');
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme"><?php echo ($vmethod == 'bank' ? 'Bank' : 'Cash'); ?> Transactions</h4>
        <?php if ($mode === 'list'): ?>
            <div class="d-flex gap-2">
                <a href="vouchers.php?mode=add&vmethod=<?php echo $vmethod; ?>&type=receipt" class="btn btn-success"><i class="fa fa-arrow-down me-1"></i> Receipt</a>
                <a href="vouchers.php?mode=add&vmethod=<?php echo $vmethod; ?>&type=payment" class="btn btn-danger"><i class="fa fa-arrow-up me-1"></i> Payment</a>
            </div>
        <?php else: ?>
            <a href="vouchers.php?vmethod=<?php echo $vmethod; ?>" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back to List</a>
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
                            <?php if($vmethod == 'bank'): ?>
                                <th>Bank</th>
                            <?php endif; ?>
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
                        $sql = "SELECT v.*, p.name as party_name, p.mobile, b.name as bank_name 
                                FROM vouchers v 
                                LEFT JOIN parties p ON v.party_id = p.id 
                                LEFT JOIN banks b ON v.bank_id = b.id
                                WHERE v.dept_id = $dept_id AND v.payment_method = '$vmethod' 
                                ORDER BY v.date DESC";
                        $res = $conn->query($sql);
                        
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
                            <?php if($vmethod == 'bank'): ?>
                                <td class="small fw-bold text-info"><?php echo $row['bank_name'] ?: '-'; ?></td>
                            <?php endif; ?>
                            <td><?php echo $row['party_name'] ?: 'General / Self'; ?></td>
                            <td class="fw-bold <?php echo $color; ?>"><?php echo format_currency($row['amount']); ?></td>
                            <td class="small"><?php echo $row['description']; ?></td>
                            <td class="text-center">
                                <?php if ($row['mobile']): 
                                    $wa_phone = preg_replace('/[^0-9]/', '', $row['mobile']);
                                    if (strlen($wa_phone) == 10) $wa_phone = "91" . $wa_phone;
                                ?>
                                    <a href="print_invoice.php?type=voucher&id=<?php echo $row['id']; ?>&autoshare=1" class="p-2 text-success" title="Send PDF on WhatsApp" onclick="handleWhatsAppShare(this.href, this); return false;">
                                        <i class="fa-brands fa-whatsapp fs-4"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="print_invoice.php?type=voucher&id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" target="_blank"><i class="fa fa-print"></i></a>
                                    <a href="vouchers.php?mode=edit&type=<?php echo $row['type']; ?>&id=<?php echo $row['id']; ?>&vmethod=<?php echo $vmethod; ?>" class="btn btn-outline-gold"><i class="fa fa-edit"></i></a>
                                    <?php if (is_admin()): ?>
                                    <a href="vouchers.php?delete=<?php echo $row['id']; ?>&vmethod=<?php echo $vmethod; ?>" class="btn btn-sm btn-outline-danger delete-btn"><i class="fa fa-trash"></i></a>
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
    <?php 
    $vtype = (isset($voucher) && $voucher) ? $voucher['type'] : ($_GET['type'] ?? 'receipt'); 
    ?>
    <div class="card card-bajot max-width-600 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($vtype); ?> Voucher (<?php echo ucfirst($vmethod); ?>)</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="type" value="<?php echo $vtype; ?>">
                <input type="hidden" name="payment_method" value="<?php echo $vmethod; ?>">
                <?php if ($voucher): ?>
                    <input type="hidden" name="id" value="<?php echo $voucher['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Transaction Date *</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $voucher ? $voucher['date'] : date('Y-m-d'); ?>" required>
                </div>

                <?php if ($vmethod == 'bank'): ?>
                <div class="mb-3">
                    <label class="form-label">Select Bank *</label>
                    <div class="input-group">
                        <select name="bank_id" class="form-select border-secondary" required>
                            <option value="">Select Bank Account</option>
                            <?php 
                            $banks = $conn->query("SELECT id, name, account_no FROM banks ORDER BY name ASC");
                            while($b = $banks->fetch_assoc()) {
                                $sel = ($voucher && $voucher['bank_id'] == $b['id']) ? 'selected' : '';
                                echo "<option value='{$b['id']}' $sel>{$b['name']} ({$b['account_no']})</option>";
                            }
                            ?>
                        </select>
                        <a href="banks.php?mode=add" class="btn btn-outline-gold">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($vtype !== 'expense'): ?>
                <div class="mb-3">
                    <label class="form-label">Select Party / Person</label>
                    <div class="input-group">
                        <select name="party_id" class="form-select border-secondary">
                            <option value="">Select Party</option>
                            <?php 
                            $ptypes = ($vtype == 'receipt') ? "'customer', 'both'" : "'supplier', 'both'";
                            $ps = $conn->query("SELECT id, name FROM parties WHERE type IN ($ptypes)");
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

