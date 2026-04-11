<?php
require_once '../config.php';
$queries = [
    "ALTER TABLE outwards ADD COLUMN transport_charge DECIMAL(15,2) DEFAULT 0.00 AFTER gst_amount",
    "ALTER TABLE outwards ADD COLUMN discount DECIMAL(15,2) DEFAULT 0.00 AFTER sub_total"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Success: $q\n";
    } else {
        echo "Error: " . $conn->error . " ($q)\n";
    }
}
?>
