<?php
require_once '../config.php';
echo "--- OUTWARDS ---\n";
$res = $conn->query("DESCRIBE outwards");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "\n--- OUTWARD_ITEMS ---\n";
$res = $conn->query("DESCRIBE outward_items");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
