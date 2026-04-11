<?php
require_once 'config.php';
$res = $conn->query("DESCRIBE outward_items");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
