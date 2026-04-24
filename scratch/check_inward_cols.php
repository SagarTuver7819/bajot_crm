<?php
require_once 'config.php';
$res = $conn->query("SHOW COLUMNS FROM inward_items");
$cols = [];
while($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'];
}
echo implode(', ', $cols);
?>
