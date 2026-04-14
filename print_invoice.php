<?php
require_once 'config.php';
require_once 'departments.php'; // Department definitions - safe to commit to git
require_once 'includes/oceanhub.php';
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

$wa_phone = '';
$wa_msg_raw = '';
$wa_msg = '';
if (!empty($data['mobile'])) {
    $wa_phone = preg_replace('/[^0-9]/', '', $data['mobile']);
    if (strlen($wa_phone) == 10) $wa_phone = "91" . $wa_phone;
    $curr_url = get_base_url() . "print_invoice.php?type=" . $type . "&id=" . $id;
    $wa_msg_raw = "Hello " . $data['name'] . ",\n\nYour " . $title . " #" . ($data['bill_no'] ?? $data['id']) . " is ready.\nTotal Amount: " . format_currency($data['total_amount'] ?? $data['amount'] ?? 0) . "\n\nYou can view/download it here: " . $curr_url;
    $wa_msg = urlencode($wa_msg_raw);
}
$oceanhub_ready = function_exists('oceanhub_ready') && oceanhub_ready();

// Helper functions for printing
function indian_number_to_words($num) {
    if ($num == 0) return "";
    $dict = array(0=>'', 1=>'one', 2=>'two', 3=>'three', 4=>'four', 5=>'five', 6=>'six', 7=>'seven', 8=>'eight', 9=>'nine', 10=>'ten', 11=>'eleven', 12=>'twelve', 13=>'thirteen', 14=>'fourteen', 15=>'fifteen', 16=>'sixteen', 17=>'seventeen', 18=>'eighteen', 19=>'nineteen', 20=>'twenty', 30=>'thirty', 40=>'forty', 50=>'fifty', 60=>'sixty', 70=>'seventy', 80=>'eighty', 90=>'ninety');
    
    $n = (int)$num;
    $res = "";
    if ($n >= 10000000) {
        $res .= indian_number_to_words((int)($n/10000000)) . " crore ";
        $n %= 10000000;
    }
    if ($n >= 100000) {
        $res .= indian_number_to_words((int)($n/100000)) . " lakh ";
        $n %= 100000;
    }
    if ($n >= 1000) {
        $res .= indian_number_to_words((int)($n/1000)) . " thousand ";
        $n %= 1000;
    }
    if ($n > 0) {
        if ($n < 21) $res .= $dict[$n];
        else if ($n < 100) {
            $tens = ((int)($n/10))*10;
            $units = $n % 10;
            $res .= $dict[$tens] . ($units ? '-' . $dict[$units] : '');
        } else {
            $res .= $dict[(int)($n/100)] . ' hundred ' . ($n%100 ? 'and ' . indian_number_to_words($n%100) : '');
        }
    }
    return trim($res);
}

function convert_to_words($number) {
    if ($number == 0) return 'Zero';
    $number = number_format($number, 2, '.', '');
    list($main, $paise) = explode('.', $number);
    
    $word_main = indian_number_to_words($main);
    $word_paise = indian_number_to_words($paise);
    
    $out = $word_main;
    if ($word_paise) {
        $out .= " and " . $word_paise . " paisa";
    }
    return ucwords($out);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print | Kaizer CRM</title>
    <style>
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 40px; background-color: #f5f5f5; }
        .invoice-box { max-width: 850px; margin: auto; padding: 50px; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.05); min-height: 1000px; position: relative; }
        
        /* Header Layout */
        .flex-container { display: flex; justify-content: space-between; align-items: flex-start; }
        .logo-section { width: 50%; }
        .title-section { width: 50%; text-align: right; }
        
        .logo-section img { height: 90px; margin-bottom: 20px; }
        .logo-section .company-title { font-size: 32px; font-weight: 800; color: #C9A14A; margin-bottom: 10px; }
        
        .estimate-text { font-size: 24px; font-weight: 800; color: #555; letter-spacing: 2px; margin-bottom: 15px; }
        .bill-info { font-size: 14px; line-height: 1.8; color: #444; }
        .bill-info b { font-weight: 700; color: #000; }
        
        .company-address { font-size: 14px; color: #666; width: 80%; line-height: 1.5; margin-bottom: 30px; }
        
        .gold-line { border-top: 2.5px solid #C9A14A; margin: 30px 0; opacity: 0.7; }
        
        .billed-to-label { font-size: 14px; font-weight: 800; color: #000; margin-bottom: 8px; }
        .billed-to-content { font-size: 15px; color: #333; line-height: 1.6; margin-bottom: 40px; }
        .billed-to-name { font-size: 17px; font-weight: 800; color: #000; text-transform: uppercase; display: block; margin-bottom: 4px; }
        
        /* Table Styling */
        .accounting-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .accounting-table thead th { background-color: #f9f9f9; padding: 12px 10px; text-align: left; font-size: 14px; font-weight: 800; color: #444; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        .accounting-table tbody td { padding: 15px 10px; font-size: 14px; color: #555; border-bottom: 1px solid #f1f1f1; }
        
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        /* Totals Section */
        .totals-container { margin-top: 40px; display: flex; flex-direction: column; align-items: flex-end; }
        .total-row { display: flex; justify-content: space-between; width: 280px; padding: 6px 0; font-size: 14px; color: #666; }
        .grand-total-row { display: flex; justify-content: space-between; width: 320px; padding: 15px 0; margin-top: 10px; border-top: 1px solid #eee; }
        .grand-total-label { font-size: 19px; font-weight: 800; color: #C9A14A; }
        .grand-total-value { font-size: 19px; font-weight: 800; color: #C9A14A; }
        
        /* Footer */
        .invoice-footer { position: absolute; bottom: 50px; left: 0; right: 0; text-align: center; }
        .thanks-msg { font-size: 12px; color: #aaa; margin-bottom: 5px; }
        .generated-msg { font-size: 11px; color: #ccc; }
        
        .btn-toolbar { margin: 0 auto 30px auto; max-width: 850px; display: flex; justify-content: center; gap: 15px; padding: 15px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px); border-radius: 12px; border: 1px solid #e0e0e0; position: sticky; top: 20px; z-index: 1000; box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
        .btn-toolbar button { padding: 10px 20px; border: none; cursor: pointer; border-radius: 8px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; color: white; }
        .btn-download { background-color: #007bff; }
        .btn-print { background-color: #C9A14A; }

        @media print {
            @page { 
                size: A4 portrait; 
                margin: 15mm; 
            }
            body { padding: 0; background: none; }
            .no-print { display: none !important; }
            .invoice-box { 
                box-shadow: none; 
                border: none; 
                padding: 0; 
                width: 100%; 
                height: auto;
                overflow: visible;
                margin: 0;
            }
            .invoice-footer { 
                margin-top: 60px;
                text-align: center; 
                border-top: 1px solid #eee;
                padding-top: 20px;
            }
            .accounting-table { margin-top: 20px; }
            .totals-container { margin-top: 30px; }
        }
    </style>
    <?php if (isset($_GET['silent'])): ?>
    <style>
        body { padding: 0; background: transparent; overflow: hidden; }
        .no-print { display: none !important; }
        .invoice-box { 
            position: absolute;
            left: -9999px;
            top: -9999px;
            visibility: visible !important;
            display: block !important;
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="btn-toolbar no-print">
        <button onclick="downloadPDF()" class="btn-download">
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            DOWNLOAD PDF
        </button>
        <button onclick="window.print()" class="btn-print">
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            PRINT NOW
        </button>
        <?php if ($wa_phone): ?>
            <button onclick="sendPdfToWhatsApp(this)" id="btn-send-pdf" style="background-color: #25D366;">
                <i class="fa-brands fa-whatsapp" id="wa-icon" style="font-size: 18px;"></i>
                <span id="wa-spinner" style="display: none; width: 18px; height: 18px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite;"></span>
                SEND ON WHATSAPP
            </button>
        <?php endif; ?>
    </div>

    <!-- Font Awesome (needed for WhatsApp icon) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" class="no-print">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" class="no-print"></script>
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <div id="invoice-content">
        <div class="invoice-box">
            <div class="flex-container">
                <div class="logo-section">
                    <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
                        <img src="<?php echo $s['company_logo']; ?>" alt="Logo">
                    <?php else: ?>
                        <div class="company-title" style="font-size: 32px; font-weight: 800; color: #C9A14A;"><?php echo strtoupper($s['company_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="title-section">
                    <div class="estimate-text" style="font-size: 24px; font-weight: 800; margin-bottom: 5px;"><?php echo strtoupper($title); ?></div>
                    <?php if (isset($data['dept_id']) && isset($departments[$data['dept_id']])): ?>
                        <div style="font-size: 13px; color: #C9A14A; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 2px;">
                            <?php echo $departments[$data['dept_id']]; ?>
                        </div>
                    <?php endif; ?>
                    <div class="bill-info" style="font-size: 14px; line-height: 1.6;">
                        Bill No: <b>#<?php echo $data['bill_no'] ?? $data['id']; ?></b><br>
                        Date: <b><?php echo date('d-m-Y', strtotime($data['date'])); ?></b>
                    </div>
                </div>
            </div>

            <div class="company-address" style="margin-bottom: 20px; font-size: 14px; color: #666; width: 60%; line-height: 1.6;">
                <?php echo $s['company_address']; ?>
            </div>

            <div class="gold-line" style="border-top: 3px solid #C9A14A; margin: 30px 0;"></div>

            <div class="billed-to-label" style="font-size: 14px; font-weight: 800;">Billed To:</div>
            <div class="billed-to-content" style="margin-bottom: 40px; font-size: 15px; line-height: 1.8;">
                <span class="billed-to-name" style="font-size: 18px; font-weight: 800; text-transform: uppercase; display: block; margin-bottom: 5px;"><?php echo $data['name']; ?></span>
                <?php if($data['address']): ?>
                    <?php echo nl2br(strtoupper($data['address'])); ?><br>
                <?php endif; ?>
                <?php if($data['mobile']): ?>
                    Mobile: <b><?php echo $data['mobile']; ?></b>
                <?php endif; ?>
            </div>

            <?php
            // Fetch all items fresh
            $item_rows = [];
            $total_pcs = 0; $total_kgs = 0; $total_feet = 0;
            if ($type == 'sales') {
                $item_q = $conn->query("SELECT oi.*, p.name as prod_name FROM outward_items oi JOIN products p ON oi.product_id = p.id WHERE oi.outward_id = $id");
            } elseif ($type == 'purchase') {
                $item_q = $conn->query("SELECT ii.*, p.name as prod_name FROM inward_items ii JOIN products p ON ii.product_id = p.id WHERE ii.inward_id = $id");
            } else { $item_q = null; }
            if ($item_q) while($row = $item_q->fetch_assoc()) {
                $total_pcs  += floatval($row['qty_pcs']);
                $total_kgs  += floatval($row['qty_kgs']);
                $total_feet += floatval($row['feet'] ?? 0);
                $item_rows[] = $row;
            }
            $dept_id = intval($data['dept_id'] ?? 0);
            ?>

            <?php if ($dept_id == 3): ?>
            <!-- ANODIZING SECTION: Product | Foot | PCS | RFT | Rate | Amount -->
            <table class="accounting-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9f9f9;border-top:1px solid #ddd;border-bottom:1px solid #ddd;">
                        <th style="width:38%;padding:12px 10px;font-weight:800;">Item Description</th>
                        <th class="text-right" style="width:10%;padding:12px 10px;">Foot</th>
                        <th class="text-right" style="width:10%;padding:12px 10px;">PCS</th>
                        <th class="text-right" style="width:10%;padding:12px 10px;">RFT</th>
                        <th class="text-right" style="width:16%;padding:12px 10px;">Rate</th>
                        <th class="text-right" style="width:16%;padding:12px 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($item_rows as $item): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px 10px;"><?php echo $item['prod_name']; ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['feet'] ?? 0, 3); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['qty_pcs'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['qty_kgs'], 3); ?></td>
                        <td class="text-right" style="padding:12px 10px;">₹<?php echo number_format($item['rate'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><b>₹<?php echo number_format($item['total'], 2); ?></b></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#fdfdfd;border-top:2px solid #C9A14A;border-bottom:2px solid #C9A14A;">
                    <tr style="font-weight:800;font-size:14px;">
                        <td class="text-right" style="padding:15px 10px;color:#C9A14A;">TOTAL</td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_feet,3); ?> <small style="font-weight:400;color:#888;">Ft</small></td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_pcs,2); ?> <small style="font-weight:400;color:#888;">Pcs</small></td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_kgs,3); ?> <small style="font-weight:400;color:#888;">RFT</small></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <?php elseif ($dept_id == 2): ?>
            <!-- POWDER COATING: Product | Color | Weight/Kg | Rate | Amount -->
            <table class="accounting-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9f9f9;border-top:1px solid #ddd;border-bottom:1px solid #ddd;">
                        <th style="width:38%;padding:12px 10px;font-weight:800;">Item Description</th>
                        <th style="width:14%;padding:12px 10px;">Color</th>
                        <th class="text-right" style="width:12%;padding:12px 10px;">Weight/Kg</th>
                        <th class="text-right" style="width:18%;padding:12px 10px;">Rate</th>
                        <th class="text-right" style="width:18%;padding:12px 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($item_rows as $item): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px 10px;"><?php echo $item['prod_name']; ?></td>
                        <td style="padding:12px 10px;"><?php echo $item['color'] ?? '-'; ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['qty_kgs'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;">₹<?php echo number_format($item['rate'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><b>₹<?php echo number_format($item['total'], 2); ?></b></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#fdfdfd;border-top:2px solid #C9A14A;border-bottom:2px solid #C9A14A;">
                    <tr style="font-weight:800;font-size:14px;">
                        <td colspan="2" class="text-right" style="padding:15px 10px;color:#C9A14A;">TOTAL</td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_kgs,2); ?> <small style="font-weight:400;color:#888;">Kgs</small></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>

            <?php else: ?>
            <!-- ALUMINIUM SECTION (default): Product | Unit | Qty/Pcs | Weight/Kg | Rate | Amount -->
            <table class="accounting-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9f9f9;border-top:1px solid #ddd;border-bottom:1px solid #ddd;">
                        <th style="width:38%;padding:12px 10px;font-weight:800;">Item Description</th>
                        <th class="text-center" style="width:10%;padding:12px 10px;">Unit</th>
                        <th class="text-right" style="width:10%;padding:12px 10px;">Qty/Pcs</th>
                        <th class="text-right" style="width:12%;padding:12px 10px;">Weight/Kg</th>
                        <th class="text-right" style="width:15%;padding:12px 10px;">Rate</th>
                        <th class="text-right" style="width:15%;padding:12px 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($item_rows as $item): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px 10px;"><?php echo $item['prod_name']; ?></td>
                        <td class="text-center" style="padding:12px 10px;"><?php echo $item['unit']; ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['qty_pcs'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><?php echo number_format($item['qty_kgs'], 3); ?></td>
                        <td class="text-right" style="padding:12px 10px;">₹<?php echo number_format($item['rate'], 2); ?></td>
                        <td class="text-right" style="padding:12px 10px;"><b>₹<?php echo number_format($item['total'], 2); ?></b></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#fdfdfd;border-top:2px solid #C9A14A;border-bottom:2px solid #C9A14A;">
                    <tr style="font-weight:800;font-size:14px;">
                        <td colspan="2" class="text-right" style="padding:15px 10px;color:#C9A14A;">TOTAL</td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_pcs,2); ?> <small style="font-weight:400;color:#888;">Pcs</small></td>
                        <td class="text-right" style="padding:15px 10px;"><?php echo number_format($total_kgs,3); ?> <small style="font-weight:400;color:#888;">Kgs</small></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>


            <div class="totals-container" style="margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end;">
                <div class="total-row" style="padding: 5px 0; font-size: 15px; width: 300px; display: flex; justify-content: space-between;">
                    <span>Total Amount:</span>
                    <span>₹<?php echo number_format($data['sub_total'] ?? $data['amount'] ?? 0, 2); ?></span>
                </div>
                <?php if (($data['discount'] ?? 0) > 0): ?>
                <div class="total-row" style="padding: 5px 0; font-size: 15px; width: 300px; display: flex; justify-content: space-between;">
                    <span>Discount:</span>
                    <span>-₹<?php echo number_format($data['discount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (($data['transport_charge'] ?? 0) > 0): ?>
                <div class="total-row" style="padding: 5px 0; font-size: 15px; width: 300px; display: flex; justify-content: space-between;">
                    <span>Transport:</span>
                    <span>+₹<?php echo number_format($data['transport_charge'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="grand-total-row" style="padding: 15px 0; margin-top: 10px; border-top: 1px solid #eee; width: 350px; display: flex; justify-content: space-between;">
                    <span class="grand-total-label" style="font-size: 20px; font-weight: 800; color: #C9A14A;">Grand Total:</span>
                    <span class="grand-total-value" style="font-size: 20px; font-weight: 800; color: #C9A14A;">₹<?php echo number_format($data['total_amount'] ?? $data['amount'] ?? 0, 2); ?></span>
                </div>
            </div>

            <?php if (!empty($data['narration'])): ?>
            <div style="margin-top: 30px; padding: 15px; border-left: 3px solid #C9A14A; background: #fafafa;">
                <div style="font-size: 12px; font-weight: 800; color: #999; text-transform: uppercase; margin-bottom: 5px;">Narration / Notes</div>
                <div style="font-size: 14px; color: #444; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($data['narration'])); ?></div>
            </div>
            <?php endif; ?>

            <div class="invoice-footer" style="margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <div class="thanks-msg" style="font-size: 13px; color: #666;">Thank you for your business!</div>
                <div class="generated-msg" style="font-size: 11px; color: #aaa;">This is a computer generated invoice.</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const fileName = 'Invoice_#<?php echo $data['bill_no'] ?? $data['id']; ?>.pdf';
        const apiReady = <?php echo $oceanhub_ready ? 'true' : 'false'; ?>;
        const waPhone = '<?php echo $wa_phone; ?>';
        const waMsg = '<?php echo addslashes($wa_msg_raw); ?>';

        // SweetAlert Toast Configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        function downloadPDF() {
            const element = document.getElementById('invoice-content');
            const opt = {
                margin:       [10, 5, 10, 5],
                filename:     fileName,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            return html2pdf().set(opt).from(element).save();
        }

        async function generatePdfBlob() {
            const element = document.getElementById('invoice-content');
            const opt = {
                margin:       10,
                filename:     fileName,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            return html2pdf().set(opt).from(element).outputPdf('blob');
        }

        async function sendPdfToWhatsApp(btn, forceTest = false) {
            if (!apiReady) {
                Swal.fire('Error', 'WhatsApp API not configured.', 'error');
                return;
            }
            if (!waPhone) {
                Swal.fire('Error', 'No customer mobile number found.', 'error');
                return;
            }

            const icon = document.getElementById('wa-icon');
            const spinner = document.getElementById('wa-spinner');
            
            if (btn) {
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
                if (icon) icon.style.display = 'none';
                if (spinner) spinner.style.display = 'inline-block';
            }

            try {
                const urlParams = new URLSearchParams(window.location.search);
                const isTestMode = forceTest || urlParams.has('testmode');
                const formData = new FormData();
                
                const pdfBlob = await generatePdfBlob();
                formData.append('pdf', pdfBlob, fileName);
                
                formData.append('phone', waPhone);
                formData.append('message', waMsg || 'Invoice PDF');
                formData.append('type', '<?php echo $type; ?>');
                formData.append('id', '<?php echo $id; ?>');

                const res = await fetch('api_send_invoice.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const raw = await res.text();
                let data = {};
                try { data = JSON.parse(raw); } catch (e) { data = { raw }; }
                
                if (!res.ok || !data.ok) {
                    throw new Error(data.error || 'Failed to send PDF');
                }

                Toast.fire({
                    icon: 'success',
                    title: 'PDF share successfully'
                });

                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'whatsapp_share_result', success: true }, '*');
                }
            } catch (err) {
                console.error('Send failed:', err);
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({ type: 'whatsapp_share_result', success: false, error: err.message }, '*');
                } else {
                    Swal.fire('Error', 'Failed to send PDF: ' + (err.message || 'Please try again.'), 'error');
                }
            } finally {
                if (btn) {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                    if (icon) icon.style.display = 'inline-block';
                    if (spinner) spinner.style.display = 'none';
                }
            }
        }

        // Auto-trigger if requested
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('autoshare')) {
                if (apiReady) {
                    const btn = document.getElementById('btn-send-pdf');
                    if (btn) {
                        setTimeout(() => sendPdfToWhatsApp(btn), 500);
                    }
                } else if (waPhone) {
                    const waUrl = `https://wa.me/${waPhone}?text=${encodeURIComponent(waMsg)}`;
                    window.open(waUrl, '_blank');
                }
            }
        };
    </script>

</body>
</html>
