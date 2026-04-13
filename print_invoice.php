<?php
require_once 'config.php';
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
                margin: 0; 
            }
            body { padding: 0; background: none; }
            .no-print { display: none !important; }
            .invoice-box { 
                box-shadow: none; 
                border: none; 
                padding: 10mm; 
                width: 190mm; /* A4 width minus margins */
                height: 138mm; /* Slightly less than half of 297mm A4 height */
                max-height: 138mm;
                overflow: hidden;
                margin: 0 auto;
                position: relative;
            }
            .invoice-footer { 
                position: absolute; 
                bottom: 10mm; 
                left: 0; 
                right: 0; 
                text-align: center; 
            }
            .accounting-table { margin-top: 10px; }
            .totals-container { margin-top: 20px; }
        }
    </style>
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
    </div>

    <div id="invoice-content">
        <div class="invoice-box">
            <div class="flex-container">
                <div class="logo-section">
                    <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
                        <img src="<?php echo $s['company_logo']; ?>" alt="Logo">
                    <?php else: ?>
                        <div class="company-title"><?php echo strtoupper($s['company_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="title-section">
                    <div class="estimate-text" style="font-size: 20px;"><?php echo strtoupper($title); ?></div>
                    <?php if (isset($data['dept_id']) && isset($departments[$data['dept_id']])): ?>
                        <div style="font-size: 13px; color: #C9A14A; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">
                            <?php echo $departments[$data['dept_id']]; ?>
                        </div>
                    <?php endif; ?>
                    <div class="bill-info">
                        Bill No: <b>#<?php echo $data['bill_no'] ?? $data['id']; ?></b><br>
                        Date: <b><?php echo date('d-m-Y', strtotime($data['date'])); ?></b>
                    </div>
                </div>
            </div>

            <div class="company-address" style="margin-bottom: 20px;">
                <?php echo $s['company_address']; ?>
            </div>

            <div class="gold-line" style="margin: 20px 0;"></div>

            <div class="billed-to-label">Billed To:</div>
            <div class="billed-to-content" style="margin-bottom: 25px;">
                <span class="billed-to-name"><?php echo $data['name']; ?></span>
                <?php if($data['address']): ?>
                    <?php echo nl2br(strtoupper($data['address'])); ?><br>
                <?php endif; ?>
                <?php if($data['mobile']): ?>
                    Mobile: <?php echo $data['mobile']; ?>
                <?php endif; ?>
            </div>

            <table class="accounting-table">
                <thead>
                    <tr>
                        <th style="width: 45%; padding: 8px 10px;">Item Description</th>
                        <th class="text-center" style="width: 10%; padding: 8px 10px;">Unit</th>
                        <th class="text-right" style="width: 10%; padding: 8px 10px;">Pcs</th>
                        <th class="text-right" style="width: 10%; padding: 8px 10px;">Kgs</th>
                        <th class="text-right" style="width: 12%; padding: 8px 10px;">Rate</th>
                        <th class="text-right" style="width: 13%; padding: 8px 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items): while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 10px;">
                            <?php echo $item['prod_name']; ?>
                            <?php if (!empty($item['color'])) echo " (".$item['color'].")"; ?>
                        </td>
                        <td class="text-center" style="padding: 10px;"><?php echo $item['unit']; ?></td>
                        <td class="text-right" style="padding: 10px;"><?php echo number_format($item['qty_pcs'], 2); ?></td>
                        <td class="text-right" style="padding: 10px;"><?php echo number_format($item['qty_kgs'], 2); ?></td>
                        <td class="text-right" style="padding: 10px;">₹<?php echo number_format($item['rate'], 2); ?></td>
                        <td class="text-right" style="padding: 10px;"><b>₹<?php echo number_format($item['total'], 2); ?></b></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>

            <div class="totals-container" style="margin-top: 25px;">
                <div class="total-row">
                    <span>Total Amount:</span>
                    <span>₹<?php echo number_format($data['sub_total'] ?? $data['amount'] ?? 0, 2); ?></span>
                </div>
                <?php if (($data['discount'] ?? 0) > 0): ?>
                <div class="total-row">
                    <span>Discount:</span>
                    <span>-₹<?php echo number_format($data['discount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (($data['transport_charge'] ?? 0) > 0): ?>
                <div class="total-row">
                    <span>Transport:</span>
                    <span>+₹<?php echo number_format($data['transport_charge'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="grand-total-row" style="padding: 10px 0;">
                    <span class="grand-total-label" style="font-size: 17px;">Grand Total:</span>
                    <span class="grand-total-value" style="font-size: 17px;">₹<?php echo number_format($data['total_amount'] ?? $data['amount'] ?? 0, 2); ?></span>
                </div>
            </div>

            <div class="invoice-footer" style="bottom: 15px;">
                <div class="thanks-msg">Thank you for your business!</div>
                <div class="generated-msg">This is a computer generated invoice.</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const fileName = 'Invoice_#<?php echo $data['bill_no'] ?? $data['id']; ?>.pdf';

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
            // Use outputPdf() directly which is more stable in 0.10.1
            return html2pdf().set(opt).from(element).outputPdf('blob');
        }

        async function sendPdfToWhatsApp(btn, forceTest = false) {
            if (!apiReady) {
                alert('WhatsApp API not configured.');
                return;
            }
            if (!waPhone) {
                alert('No customer mobile number found.');
                return;
            }

            const icon = document.getElementById('wa-icon');
            const spinner = document.getElementById('wa-spinner');
            
            if (btn) {
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
                if (icon) icon.style.display = 'none';
                if (spinner) spinner.style.display = 'block';
            }

            try {
                const urlParams = new URLSearchParams(window.location.search);
                const isTestMode = forceTest || urlParams.has('testmode');
                const formData = new FormData();
                if (!isTestMode) {
                    const pdfBlob = await generatePdfBlob();
                    formData.append('pdf', pdfBlob, fileName);
                } else {
                    formData.append('test_mode', '1');
                }
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
                    const parts = [];
                    const baseMsg = data.error || data.response || data.raw || 'Failed to send PDF';
                    parts.push(baseMsg);
                    if (data.http_code) parts.push('HTTP ' + data.http_code);
                    if (data.response && data.response !== baseMsg) parts.push('Resp: ' + data.response);
                    if (data.curl_error) parts.push('cURL: ' + data.curl_error);
                    throw new Error(parts.join(' | '));
                }

                alert('PDF sent successfully on WhatsApp.');
            } catch (err) {
                console.error('Send failed:', err);
                alert('Failed to send PDF: ' + (err.message || 'Please try again.'));
            } finally {
                if (btn) {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                    if (icon) icon.style.display = 'block';
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
