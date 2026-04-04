<?php
require_once 'config.php';

$table = 'outward_items';
$column = 'color';

// Check if column exists
$res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` VARCHAR(100) DEFAULT NULL AFTER `product_id` ");
    echo "Added color to $table<br>";
} else {
    echo "color already exists in $table<br>";
}

echo "Database Update Complete.";
?>
