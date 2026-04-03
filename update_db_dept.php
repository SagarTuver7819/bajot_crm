<?php
require_once 'config.php';

$tables = ['products', 'inwards', 'outwards', 'vouchers'];

foreach ($tables as $table) {
    // Check if column exists
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'dept_id'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `dept_id` INT DEFAULT 1 AFTER `id` ");
        echo "Added dept_id to $table<br>";
    } else {
        echo "dept_id already exists in $table<br>";
    }
}

echo "Database Update Complete.";
?>
