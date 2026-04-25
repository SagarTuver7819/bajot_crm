<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

echo "--- outwards ---\n";
$res = $conn->query("DESCRIBE outwards");
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n--- outward_items ---\n";
$res = $conn->query("DESCRIBE outward_items");
while($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>
