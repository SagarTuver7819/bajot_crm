<?php
require_once 'config.php';
check_login();

$type = $_GET['type'] ?? 'sales';
$id = (int)($_GET['id'] ?? 0);

if (!$id) die("Invalid Request");

$s = get_settings();

if ($type == 'sales') {
    $res = $conn->query("SELECT o.*, p.name, p.address, p.mobile FROM outwards o JOIN parties p ON o.party_id = p.id WHERE o.id = $id");
    $data = $res->fetch_assoc();
    $items = $conn->query("SELECT oi.*, p.name as prod_name FROM outward_items oi JOIN products p ON oi.product_id = p.id WHERE oi.outward_id = $id");
    $title = "ESTIMATE";
} elseif ($type == 'purchase') {
    $res = $conn->query("SELECT i.*, p.name, p.address, p.mobile FROM inwards i JOIN parties p ON i.party_id = p.id WHERE i.id = $id");
    $data = $res->fetch_assoc();
    $items = $conn->query("SELECT ii.*, p.name as prod_name FROM inward_items ii JOIN products p ON ii.product_id = p.id WHERE ii.inward_id = $id");
    $title = "PURCHASE VOUCHER";
} elseif ($type == 'voucher') {
    $res = $conn->query("SELECT v.*, p.name, p.address, p.mobile FROM vouchers v LEFT JOIN parties p ON v.party_id = p.id WHERE v.id = $id");
    $data = $res->fetch_assoc();
    $title = strtoupper($data['type']) . " VOUCHER";
    $items = null; // No multi-items for vouchers
} else {
    die("Unsupported Print Type");
}

if (!$data) die("Record not found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print | Kaizer CRM</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; margin: 0; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 14px; line-height: 24px; color: #555; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #C9A14A; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { color: #C9A14A; margin: 0; font-size: 28px; }
        .details { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .details div { width: 45%; }
        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        table th { background: #f9f9f9; border-bottom: 1px solid #ddd; padding: 10px; font-weight: bold; }
        table td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-box { margin-top: 20px; text-align: right; }
        .total-box table { width: auto; margin-left: auto; }
        .total-box td { border-bottom: none; padding: 5px 10px; }
        .grand-total { font-weight: bold; color: #C9A14A; font-size: 18px; border-top: 2px solid #C9A14A; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; }
        @media print {
            .btn-print { display: none; }
            .invoice-box { box-shadow: none; border: none; }
        }
        .btn-print { background: #C9A14A; color: white; padding: 10px 20px; border: none; cursor: pointer; float: right; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">PRINT NOW</button>
    
    <div class="invoice-box">
        <div class="header">
            <div>
                <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
                    <img src="<?php echo $s['company_logo']; ?>" alt="Logo" style="height: 100px; margin-bottom: 10px;">
                <?php else: ?>
                    <h1><?php echo strtoupper($s['company_name']); ?></h1>
                <?php endif; ?>
                <p><?php echo $s['company_address']; ?></p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0;"><?php echo $title; ?></h2>
                <p><?php echo ($type == 'voucher' ? 'Voucher No:' : 'Bill No:'); ?> <b>#<?php echo $data['bill_no'] ?? $data['id']; ?></b><br>Date: <?php echo date('d-m-Y', strtotime($data['date'])); ?></p>
            </div>
        </div>

        <div class="details">
            <div>
                <strong>Billed To:</strong><br>
                <?php echo $data['name']; ?><br>
                <?php echo $data['address']; ?><br>
                Mobile: <?php echo $data['mobile']; ?>
            </div>
        </div>

        <?php if ($items): ?>
        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Unit</th>
                    <?php if ($data['dept_id'] != 2): ?>
                    <th>Pcs</th>
                    <?php endif; ?>
                    <th>Kgs</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['prod_name']; ?></td>
                    <td><?php echo ($data['dept_id'] == 2) ? 'kg' : $item['unit']; ?></td>
                    <?php if ($data['dept_id'] != 2): ?>
                    <td><?php echo $item['qty_pcs']; ?></td>
                    <?php endif; ?>
                    <td><?php echo $item['qty_kgs']; ?></td>
                    <td><?php echo format_currency($item['rate']); ?></td>
                    <td><?php echo format_currency($item['total']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="padding: 20px; border: 1px solid #eee; margin-top: 20px;">
                <strong>Description:</strong><br>
                <?php echo $data['description'] ?: 'No details provided.'; ?>
            </div>
        <?php endif; ?>

        <div class="total-box">
            <table>
                <?php if ($items): ?>
                <tr>
                    <td>Total Amount:</td>
                    <td><?php echo format_currency($data['total_amount'] ?? 0); ?></td>
                </tr>
                <tr class="grand-total">
                    <td>Grand Total:</td>
                    <td><?php echo format_currency($data['total_amount'] ?? 0); ?></td>
                </tr>
                <?php else: ?>
                <tr class="grand-total">
                    <td>Amount:</td>
                    <td><?php echo format_currency($data['amount'] ?? 0); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer generated invoice.</p>
        </div>
    </div>
</body>
</html>
