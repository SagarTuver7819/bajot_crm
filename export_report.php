<?php
require_once 'config.php';
check_login();

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'sales';
$format = $_GET['format'] ?? 'excel';
$dept_id = (int)$_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'N/A';

// Fetch Data
if ($report_type == 'sales') {
    $res = $conn->query("SELECT o.*, p.name as party_name FROM outwards o JOIN parties p ON o.party_id = p.id WHERE o.date BETWEEN '$from_date' AND '$to_date' AND o.dept_id = $dept_id ORDER BY o.date DESC");
    $title = "Sales Report ($from_date to $to_date)";
    $col1 = "Bill No.";
    $col2 = "Customer Name";
} elseif ($report_type == 'purchase') {
    $res = $conn->query("SELECT i.*, p.name as party_name FROM inwards i JOIN parties p ON i.party_id = p.id WHERE i.date BETWEEN '$from_date' AND '$to_date' AND i.dept_id = $dept_id ORDER BY i.date DESC");
    $title = "Purchase Report ($from_date to $to_date)";
    $col1 = "Bill No.";
    $col2 = "Supplier Name";
} else {
    $res = $conn->query("SELECT e.*, ec.name as cat_name FROM expenses e JOIN expense_categories ec ON e.category_id = ec.id WHERE e.date BETWEEN '$from_date' AND '$to_date' AND e.dept_id = $dept_id ORDER BY e.date DESC");
    $title = "Expense Report ($from_date to $to_date)";
    $col1 = "Category";
    $col2 = "Description";
}

if ($format == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . str_replace(' ', '_', $title) . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #C9A14A; padding-bottom: 10px; }
        .company-name { font-size: 20px; font-weight: bold; color: #C9A14A; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #f2f2f2; border: 1px solid #ddd; padding: 10px; text-align: left; }
        td { border: 1px solid #ddd; padding: 8px; }
        .total-row { background-color: #fff9eb; font-weight: bold; }
        .text-end { text-align: right; }
        .footer { margin-top: 30px; font-size: 10px; color: #777; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { margin: 20px; }
        }
    </style>
</head>
<body <?php if($format == 'pdf') echo 'onload="window.print()"'; ?>>

<div class="header">
    <div class="company-name">BAJOT EXTRUSION PVT. LTD.</div>
    <div style="font-size: 14px; font-weight: bold; margin-top: 5px;"><?php echo $title; ?></div>
    <div style="font-size: 12px; color: #666;">Department: <?php echo $dept_name; ?></div>
</div>

<?php if ($format == 'pdf'): ?>
<div class="no-print" style="margin-bottom: 20px; text-align: right;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #C9A14A; color: white; border: none; border-radius: 4px; cursor: pointer;">Print / Save as PDF</button>
</div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th><?php echo $col1; ?></th>
            <th><?php echo $col2; ?></th>
            <th class="text-end">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $grand_total = 0;
        while ($row = $res->fetch_assoc()): 
            $amt = ($report_type == 'expense' ? $row['amount'] : $row['total_amount']);
            $grand_total += $amt;
        ?>
        <tr>
            <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
            <td style="font-weight: bold;"><?php echo ($report_type == 'expense' ? $row['cat_name'] : $row['bill_no']); ?></td>
            <td><?php echo ($report_type == 'expense' ? $row['description'] : $row['party_name']); ?></td>
            <td class="text-end"><?php echo number_format($amt, 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-end">Grand Total:</td>
            <td class="text-end"><?php echo number_format($grand_total, 2); ?></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    Report Generated on <?php echo date('d-m-Y H:i:s'); ?> | System Designed & Developed By Ocean Infotech
</div>

</body>
</html>
