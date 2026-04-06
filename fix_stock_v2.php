<?php
require 'config.php';
$sql = "UPDATE products SET total_pcs = ABS(total_pcs), total_kgs = ABS(total_kgs), current_stock = ABS(current_stock) 
        WHERE (name LIKE '%METALLIC%' AND name LIKE '%POWDER%')
           OR (name LIKE '%REGULAR%' AND name LIKE '%POWDER%')";
if ($conn->query($sql)) {
    echo "Stock corrected for Metallic and Regular Powder Coating (flexible matching).\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
