<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');
$res = $conn->query("SELECT id, entry_format FROM outwards WHERE id = 71");
print_r($res->fetch_assoc());

$res = $conn->query("SELECT * FROM outward_items WHERE outward_id = 71");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
