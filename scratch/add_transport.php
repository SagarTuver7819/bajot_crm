<?php
require_once 'config.php';
$conn->query("ALTER TABLE outwards ADD COLUMN transport_charge DECIMAL(15,2) DEFAULT 0.00 AFTER sub_total");
echo "Done";
?>
