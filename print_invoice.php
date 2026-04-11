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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; background: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 14px; line-height: 24px; color: #555; }
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
        .grand-total { font-weight: bold; color: #000; font-size: 18px; border-top: 2px solid #000; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .border-bottom-dark { border-bottom: 2px solid #000; }
        .mt-3 { margin-top: 15px; }
        .mb-0 { margin-bottom: 0px; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        
        @media print {
            body { padding: 0; background: none; }
            .no-print { display: none !important; }
            .invoice-box { box-shadow: none; border: none; max-width: 100%; margin: 0; padding: 0; }
        }

        .btn-toolbar {
            margin: 0 auto 30px auto;
            max-width: 800px;
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            position: sticky;
            top: 20px;
            z-index: 1000;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .btn-toolbar button, .btn-toolbar a {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-toolbar button:hover, .btn-toolbar a:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            filter: brightness(1.1);
        }
        .btn-toolbar button:active {
            transform: translateY(0);
        }
        .btn-download { background-color: #007bff; }
        .btn-print { background-color: #C9A14A; }
        .btn-whatsapp { background-color: #075E54; }
        
        .sending-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
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
        <?php if (!empty($wa_phone) && $oceanhub_ready): ?>
            <button id="btn-send-pdf" onclick="sendPdfToWhatsApp(this)" class="btn-whatsapp">
                <svg id="wa-icon" style="width: 18px; height: 18px;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 .018 5.393 0 12.03c0 2.122.554 4.197 1.604 6.04L0 24l6.101-1.6c1.785.973 3.799 1.488 5.845 1.489h.005c6.634 0 12.03-5.394 12.033-12.033a11.812 11.812 0 00-3.46-8.508z"/></svg>
                <div id="wa-spinner" class="sending-spinner" style="display: none;"></div>
                SEND ON WHATSAPP
            </button>
        <?php endif; ?>
    </div>
    
    <div id="invoice-content">
        <div class="invoice-box">
            <!-- Miracle Style Layout -->
            <div class="text-center mb-0">
                <h1 style="color: #6b4e1a; margin-bottom: 0; letter-spacing: 2px;"><?php echo strtoupper($s['company_name']); ?></h1>
                <h5 style="margin-top: 0; margin-bottom: 10px; text-decoration: underline;">ESTIMATE</h5>
            </div>

            <div class="d-flex justify-content-between">
                <div class="fw-bold">DEBIT MEMO</div>
                <div class="text-center">
                    <h3 style="margin: 0; text-decoration: underline; font-weight: 800;"><?php echo $title; ?></h3>
                </div>
                <div class="fw-bold">ORIGINAL</div>
            </div>

            <div style="border: 2px solid #000; margin-top: 10px; padding: 10px;">
                <div class="d-flex justify-content-between">
                    <div style="width: 70%;">
                        <strong>M/s.: <?php echo strtoupper($data['name']); ?></strong><br>
                        <div style="margin-left: 35px;">
                            <?php echo $data['address']; ?>
                        </div>
                    </div>
                    <div style="width: 25%; border-left: 2px solid #000; padding-left: 10px;">
                        <div>NO. : <b><?php echo $data['bill_no'] ?? $data['id']; ?></b></div>
                        <div>DATE : <b><?php echo date('d/m/Y', strtotime($data['date'])); ?></b></div>
                    </div>
                </div>
            </div>

            <?php if ($items): ?>
            <table>
                <thead>
                        <th>Item Description</th>
                        <?php if ($data['dept_id'] == 3): ?>
                            <th>Foot</th>
                            <th>PCS</th>
                            <th>RFT</th>
                        <?php else: ?>
                            <th style="<?php echo ($data['dept_id'] == 2) ? 'display: none;' : ''; ?>">Unit</th>
                            <?php if ($data['dept_id'] == 2): ?>
                                <th>Color</th>
                            <?php endif; ?>
                            <?php if ($data['dept_id'] != 2): ?>
                                <th>Pcs</th>
                            <?php endif; ?>
                            <th><?php echo ($data['dept_id'] == 2) ? 'Weight' : 'Kgs'; ?></th>
                        <?php endif; ?>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    $total_pcs = 0;
                    $total_kgs = 0;
                    $total_feet = 0;
                    while($item = $items->fetch_assoc()): 
                        $total_pcs += $item['qty_pcs'];
                        $total_kgs += $item['qty_kgs'];
                        $total_feet += $item['feet'];
                    ?>
                    <tr>
                        <td style="border-right: 2px solid #000; text-align: center;"><?php echo ($sr++); ?></td>
                        <td style="border-right: 2px solid #000;"><?php echo $item['prod_name']; ?><?php echo ($data['dept_id'] == 2 && !empty($item['color'])) ? " (".$item['color'].")" : ""; ?></td>
                        
                        <?php if ($data['dept_id'] == 3): ?>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($item['feet'], 2); ?></td>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($item['qty_pcs'], 2); ?></td>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($item['qty_kgs'], 3); ?></td>
                        <?php else: ?>
                            <td style="<?php echo ($data['dept_id'] == 2) ? 'display: none;' : ''; ?> border-right: 2px solid #000;"><?php echo $item['unit']; ?></td>
                            <?php if ($data['dept_id'] == 2): ?>
                                <td style="border-right: 2px solid #000;"><?php echo $item['color']; ?></td>
                            <?php endif; ?>
                            <?php if ($data['dept_id'] != 2): ?>
                                <td style="border-right: 2px solid #000; text-align: right;"><?php echo $item['qty_pcs']; ?></td>
                            <?php endif; ?>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo $item['qty_kgs']; ?><?php echo ($data['dept_id'] == 2) ? ' kg' : ''; ?></td>
                        <?php endif; ?>
                        
                        <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($item['rate'], 2); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot style="font-weight: bold; border-top: 2px solid #000;">
                    <tr>
                        <td colspan="2" style="border-right: 2px solid #000;">TOTAL</td>
                        <?php if ($data['dept_id'] == 3): ?>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($total_feet, 2); ?></td>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($total_pcs, 2); ?></td>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($total_kgs, 3); ?></td>
                        <?php else: ?>
                            <td style="<?php echo ($data['dept_id'] == 2) ? 'display: none;' : ''; ?> border-right: 2px solid #000;"></td>
                            <?php if ($data['dept_id'] == 2): ?>
                                <td style="border-right: 2px solid #000;"></td>
                            <?php endif; ?>
                            <?php if ($data['dept_id'] != 2): ?>
                                <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($total_pcs, 2); ?></td>
                            <?php endif; ?>
                            <td style="border-right: 2px solid #000; text-align: right;"><?php echo number_format($total_kgs, 2); ?><?php echo ($data['dept_id'] == 2) ? ' kg' : ''; ?></td>
                        <?php endif; ?>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            </div>

            <div style="border: 2px solid #000; border-top: none; padding: 10px;" class="d-flex justify-content-between">
                <div style="width: 60%;">
                    <div class="fw-bold">Rs : <?php echo convert_to_words($data['total_amount'] ?? 0); ?> Only</div>
                    <?php if ($data['dept_id'] == 2 || $data['dept_id'] == 3): ?>
                        <div class="mt-3 small"><b>Colour : NEW ANODISE</b></div>
                    <?php endif; ?>
                </div>
                <div style="width: 35%; border-left: 2px solid #000; padding-left: 10px;">
                    <div class="d-flex justify-content-between">
                        <span>Sub Total</span>
                        <span class="fw-bold"><?php echo number_format($data['total_amount'] ?? 0, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top border-dark">
                        <span class="fw-bold">Grand Total</span>
                        <span class="fw-bold"><?php echo number_format($data['total_amount'] ?? 0, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="footer">
                <p>Thank you for your business!</p>
                <p>This is a computer generated invoice.</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const waMsg = <?php echo json_encode($wa_msg_raw); ?>;
        const waPhone = <?php echo json_encode($wa_phone); ?>;
        const apiReady = <?php echo $oceanhub_ready ? 'true' : 'false'; ?>;
        const fileName = 'Invoice_#<?php echo $data['bill_no'] ?? $data['id']; ?>.pdf';

        function downloadPDF() {
            const element = document.getElementById('invoice-content');
            const opt = {
                margin:       10,
                filename:     fileName,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
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
