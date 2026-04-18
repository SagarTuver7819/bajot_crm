<?php
require_once 'config.php';

// This script fixes the vouchers with invalid dates (0000-00-00)
// by setting them to the current date or a logical default.

$sql = "SELECT id, date FROM vouchers WHERE date < '1900-01-01' OR date IS NULL";
$result = $conn->query($sql);

$fixed_count = 0;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        // We set it to current date as a fix. 
        // User can manually edit later if they need a specific past date.
        $conn->query("UPDATE vouchers SET date = CURDATE() WHERE id = $id");
        $fixed_count++;
        echo "Fixed Voucher ID: $id <br>";
    }
    echo "<b>Total $fixed_count entries fixed successfully.</b>";
} else {
    echo "No corrupted dates found in the database.";
}
?>
