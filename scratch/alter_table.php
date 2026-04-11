<?php
require_once 'config.php';
$conn->query("ALTER TABLE outward_items ADD COLUMN feet DECIMAL(15,2) DEFAULT 0.00 AFTER color");
echo "Done";
?>
