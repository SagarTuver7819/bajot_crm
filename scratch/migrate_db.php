<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "ALTER TABLE outwards ADD COLUMN entry_format VARCHAR(20) DEFAULT '' AFTER narration";
if ($conn->query($sql)) {
    echo "Column entry_format added successfully.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
