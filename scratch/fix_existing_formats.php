<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "UPDATE outwards o 
        SET entry_format = 'foot' 
        WHERE dept_id = 2 AND EXISTS (
            SELECT 1 FROM outward_items oi 
            WHERE oi.outward_id = o.id AND oi.feet > 0
        )";

if ($conn->query($sql)) {
    echo $conn->affected_rows . " rows updated.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
