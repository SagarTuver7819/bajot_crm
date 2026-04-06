<?php
require 'config.php';
$res = $conn->query('SELECT id, name FROM products WHERE id IN (105, 181, 182)');
while($r = $res->fetch_assoc()) {
    echo "ID " . $r['id'] . ": [" . $r['name'] . "]\n";
}
?>
