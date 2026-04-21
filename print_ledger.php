<?php
require_once 'config.php';
require_once 'includes/oceanhub.php';
check_login();

$party_id = isset($_GET['party_id']) ? (int)$_GET['party_id'] : 0;
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : (isset($_SESSION['dept_id']) ? (int)$_SESSION['dept_id'] : 0);

if (!$party_id) die("Invalid Party");

$settings = get_settings();
$party = $conn->query("SELECT * FROM parties WHERE id=$party_id")->fetch_assoc();
if (!$party) die("Party not found");

// Department Definitions
$departments = [
    1 => 'Aluminium Section',
    2 => 'Powder Coating',
    3 => 'Anodizing Section'
];

// Fetch transactions logic (same as ledger.php)
$transactions = [];
$is_supplier = ($party['type'] == 'supplier');
$where_dept = $dept_id ? " AND dept_id=$dept_id " : "";

// Department-wise Opening Balance Logic
$base_opening = (float)($party['opening_balance'] ?? 0);
if ($dept_id == 1) {
    $base_opening = (float)($party['ob_alum'] ?? 0);
} elseif ($dept_id == 2) {
    $base_opening = (float)($party['ob_pwdr'] ?? 0);
} elseif ($dept_id == 3) {
    $base_opening = (float)($party['ob_anod'] ?? 0);
}

// Calculate aggregated transactions BEFORE from_date to get the true opening balance
$before_debit = 0;
$before_credit = 0;

// Sales before
$sales_before = $conn->query("SELECT SUM(total_amount) as total FROM outwards WHERE party_id=$party_id AND date < '$from_date' $where_dept")->fetch_assoc();
$before_debit += (float)($sales_before['total'] ?? 0);

// Purchases before
$purchases_before = $conn->query("SELECT SUM(total_amount) as total FROM inwards WHERE party_id=$party_id AND date < '$from_date' $where_dept")->fetch_assoc();
$before_credit += (float)($purchases_before['total'] ?? 0);

// Vouchers before
$vouchers_before = $conn->query("SELECT type, SUM(amount) as total FROM vouchers WHERE party_id=$party_id AND date < '$from_date' $where_dept GROUP BY type");
if ($vouchers_before) {
    while($vb = $vouchers_before->fetch_assoc()){
        if($vb['type'] == 'receipt') $before_credit += (float)$vb['total'];
        else $before_debit += (float)$vb['total'];
    }
}

// Kasars before
$kasars_before = $conn->query("SELECT type, SUM(amount) as total FROM kasars WHERE party_id=$party_id AND date < '$from_date' $where_dept GROUP BY type");
if ($kasars_before) {
    while($kb = $kasars_before->fetch_assoc()){
        if($kb['type'] == 'allowed') $before_credit += (float)$kb['total'];
        else $before_debit += (float)$kb['total'];
    }
}

if ($is_supplier) {
    $opening_balance = $base_opening + ($before_credit - $before_debit);
} else {
    $opening_balance = $base_opening + ($before_debit - $before_credit);
}

// Sales
$sales = $conn->query("SELECT id, dept_id, date, bill_no, total_amount as amount, 'Sales' as type FROM outwards WHERE party_id=$party_id AND (date BETWEEN '$from_date' AND '$to_date') $where_dept");
while($sale = $sales->fetch_assoc()) {
    $sale['debit'] = $sale['amount']; $sale['credit'] = 0; $transactions[] = $sale;
}
// Purchases
$purchases = $conn->query("SELECT id, dept_id, date, bill_no, total_amount as amount, 'Purchase' as type FROM inwards WHERE party_id=$party_id AND (date BETWEEN '$from_date' AND '$to_date') $where_dept");
while($p = $purchases->fetch_assoc()) {
    $p['debit'] = 0; $p['credit'] = $p['amount']; $transactions[] = $p;
}
// Vouchers
$vouchers = $conn->query("SELECT id, dept_id, date, type as vtype, amount, description, 'Voucher' as type FROM vouchers WHERE party_id=$party_id AND (date BETWEEN '$from_date' AND '$to_date') $where_dept");
while($v = $vouchers->fetch_assoc()) {
    $v['bill_no'] = "VCH-" . $v['id'];
    if ($v['vtype'] == 'receipt') { $v['debit'] = 0; $v['credit'] = $v['amount']; }
    else { $v['debit'] = $v['amount']; $v['credit'] = 0; }
    $transactions[] = $v;
}
// Kasars
$kasars = $conn->query("SELECT id, dept_id, date, amount, type as ktype, description, 'Kasar' as type FROM kasars WHERE party_id=$party_id AND (date BETWEEN '$from_date' AND '$to_date') $where_dept");
if ($kasars) while($k = $kasars->fetch_assoc()) {
    $k['bill_no'] = "KSR-" . $k['id'];
    if ($k['ktype'] == 'allowed') { $k['debit'] = 0; $k['credit'] = $k['amount']; }
    else { $k['debit'] = $k['amount']; $k['credit'] = 0; }
    $transactions[] = $k;
}
usort($transactions, function($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });

$wa_phone = preg_replace('/[^0-9]/', '', $party['mobile'] ?? '');
if (strlen($wa_phone) == 10) $wa_phone = "91" . $wa_phone;
$wa_msg = "Hello " . $party['name'] . ",\n\nPlease find your ledger statement for " . date('d/m/Y', strtotime($from_date)) . " to " . date('d/m/Y', strtotime($to_date)) . ".";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Ledger PDF | Kaizer CRM</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; font-size: 13px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { height: 80px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .title { color: #C9A14A; font-size: 20px; font-weight: bold; margin-bottom: 5px; }
    </style>
    <?php if (isset($_GET['silent'])): ?>
    <style>body { padding: 0; overflow: hidden; } .no-capture { display: none; }</style>
    <?php endif; ?>
</head>
<body>
    <div id="ledger-content">
        <div class="header">
            <div class="title"><?php echo strtoupper($settings['company_name']); ?></div>
            <div><?php echo $settings['company_address']; ?></div>
            <hr>
            <h3>PARTY LEDGER: <?php echo strtoupper($party['name']); ?></h3>
            <?php if ($dept_id): ?>
                <h4 style="color: #C9A14A;">Department: <?php echo $departments[$dept_id]; ?></h4>
            <?php endif; ?>
            <p>From: <?php echo date('d-m-Y', strtotime($from_date)); ?> To: <?php echo date('d-m-Y', strtotime($to_date)); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th><th>Type</th><th>Ref#</th>
                    <th class="text-end">Debit (Dr)</th><th class="text-end">Credit (Cr)</th><th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="text-end fw-bold">Opening Balance</td>
                    <td class="text-end fw-bold"><?php echo format_currency($opening_balance); ?></td>
                </tr>
                <?php 
                $running_balance = (float)$opening_balance;
                $is_supplier = ($party['type'] == 'supplier');
                foreach ($transactions as $tr): 
                    if ($is_supplier) $running_balance += ($tr['credit'] - $tr['debit']);
                    else $running_balance += ($tr['debit'] - $tr['credit']);
                ?>
                <tr>
                    <td><?php echo ($tr['date'] && $tr['date'] != '0000-00-00') ? date('d-m-Y', strtotime($tr['date'])) : '-'; ?></td>
                    <td><?php echo $tr['type']; ?></td>
                    <td><?php echo $tr['bill_no']; ?></td>
                    <td class="text-end"><?php echo $tr['debit'] > 0 ? format_currency($tr['debit']) : '-'; ?></td>
                    <td class="text-end"><?php echo $tr['credit'] > 0 ? format_currency($tr['credit']) : '-'; ?></td>
                    <td class="text-end fw-bold">
                        <?php echo format_currency(abs($running_balance)) . ($is_supplier ? ($running_balance >= 0 ? " (Cr)" : " (Dr)") : ($running_balance >= 0 ? " (Dr)" : " (Cr)")); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f9f9f9;">
                    <td colspan="5" class="text-end fw-bold">Closing Balance</td>
                    <td class="text-end fw-bold"><?php echo format_currency(abs($running_balance)); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const waPhone = '<?php echo $wa_phone; ?>';
        const waMsg = <?php echo json_encode($wa_msg); ?>;
        
        async function sendLedgerToWhatsApp() {
            const element = document.getElementById('ledger-content');
            const opt = { margin: 5, filename: 'Ledger_Statement.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };
            
            try {
                const pdfBlob = await html2pdf().set(opt).from(element).outputPdf('blob');
                const formData = new FormData();
                formData.append('pdf', pdfBlob, 'Ledger_Statement.pdf');
                formData.append('phone', waPhone);
                formData.append('message', waMsg);
                formData.append('type', 'ledger');
                formData.append('id', '<?php echo $party_id; ?>');

                const res = await fetch('api_send_invoice.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'whatsapp_share_result', success: data.ok, error: data.error }, '*');
                }
            } catch (err) {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'whatsapp_share_result', success: false, error: err.message }, '*');
                }
            }
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('autoshare')) {
                setTimeout(sendLedgerToWhatsApp, 500);
            } else {
                window.print();
            }
        };
    </script>
</body>
</html>
