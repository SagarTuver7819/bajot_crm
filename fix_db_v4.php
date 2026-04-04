<?php
require_once 'config.php';

$tables = ['outwards'];
$column = 'narration';

foreach ($tables as $table) {
    // Check if column exists
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` TEXT DEFAULT NULL");
        echo "Added narration to $table<br>";
    } else {
        echo "narration already exists in $table<br>";
    }
}

echo "Database Update Complete.";
?>
