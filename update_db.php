<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');

// Try each query and ignore errors if column exists
@$conn->query("ALTER TABLE products ADD COLUMN opening_pcs DECIMAL(15,2) DEFAULT 0.00 AFTER unit");
@$conn->query("ALTER TABLE products ADD COLUMN opening_kgs DECIMAL(15,2) DEFAULT 0.00 AFTER opening_pcs");
@$conn->query("ALTER TABLE products ADD COLUMN total_pcs DECIMAL(15,2) DEFAULT 0.00 AFTER opening_kgs");
@$conn->query("ALTER TABLE products ADD COLUMN total_kgs DECIMAL(15,2) DEFAULT 0.00 AFTER total_pcs");

echo "Schema fixes applied (if not already present).";
?>
