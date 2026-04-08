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
    </style>
</head>
<body>
    <div class="btn-print" style="float: right; display: flex; gap: 10px; margin: 20px;">
        <button onclick="downloadPDF()" style="background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">DOWNLOAD PDF</button>
        <button onclick="window.print()" style="background: #C9A14A; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT NOW</button>
        <?php if (!empty($wa_phone)): ?>
            <?php if ($oceanhub_ready): ?>
                <a href="javascript:void(0)" id="btn-send-pdf" onclick="sendPdfToWhatsApp(this)" style="background: #075E54; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; text-decoration: none;">SEND PDF</a>
                <a href="javascript:void(0)" id="btn-test-send" onclick="sendPdfToWhatsApp(this, true)" style="background: #6c757d; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; text-decoration: none;">TEST SEND</a>
            <?php endif; ?>
            <a href="https://wa.me/<?php echo $wa_phone; ?>?text=<?php echo $wa_msg; ?>" target="_blank" style="background: #25D366; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; text-decoration: none;">SHARE LINK</a>
        <?php endif; ?>
    </div>
    
    <div id="invoice-content">
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
                        <th style="<?php echo ($data['dept_id'] == 2) ? 'display: none;' : ''; ?>">Unit</th>
                        <?php if ($data['dept_id'] == 2): ?>
                        <th>Color</th>
                        <?php endif; ?>
                        <?php if ($data['dept_id'] != 2): ?>
                        <th>Pcs</th>
                        <?php endif; ?>
                        <th><?php echo ($data['dept_id'] == 2) ? 'Weight' : 'Kgs'; ?></th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $item['prod_name']; ?></td>
                        <td style="<?php echo ($data['dept_id'] == 2) ? 'display: none;' : ''; ?>"><?php echo $item['unit']; ?></td>
                        <?php if ($data['dept_id'] == 2): ?>
                        <td><?php echo $item['color']; ?></td>
                        <?php endif; ?>
                        <?php if ($data['dept_id'] != 2): ?>
                        <td><?php echo $item['qty_pcs']; ?></td>
                        <?php endif; ?>
                        <td><?php echo $item['qty_kgs']; ?><?php echo ($data['dept_id'] == 2) ? ' kg' : ''; ?></td>
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

            <?php if (!empty($data['narration'])): ?>
            <div style="margin-top: 20px; padding: 10px; border: 1px dashed #C9A14A; background: #fffcf5;">
                <strong>Narration:</strong><br>
                <?php echo nl2br(htmlspecialchars($data['narration'])); ?>
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
                html2canvas:  { scale: 2 },
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
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            const worker = html2pdf().set(opt).from(element);
            const pdf = await worker.get('pdf');
            return pdf.output('blob');
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

            const originalText = btn ? btn.innerText : '';
            if (btn) {
                btn.innerText = 'SENDING...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none';
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
                    const apiMsg = data.error || data.response || data.raw || 'Failed to send PDF';
                    throw new Error(apiMsg);
                }

                alert('PDF sent successfully on WhatsApp.');
            } catch (err) {
                console.error('Send failed:', err);
                alert('Failed to send PDF: ' + (err.message || 'Please try again.'));
            } finally {
                if (btn) {
                    btn.innerText = originalText;
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
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
