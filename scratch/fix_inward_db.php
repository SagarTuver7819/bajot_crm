<?php
require_once 'config.php';
$queries = [
    "ALTER TABLE inward_items ADD COLUMN color VARCHAR(100) AFTER product_id",
    "ALTER TABLE inward_items ADD COLUMN feet DECIMAL(15,3) DEFAULT 0.000 AFTER color"
];
foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Executed: $q\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
?>
