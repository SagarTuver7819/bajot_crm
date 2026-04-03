<?php
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$queries = [
    "ALTER TABLE products ADD COLUMN total_pcs DECIMAL(15,2) DEFAULT 0.00 AFTER opening_kgs",
    "ALTER TABLE products ADD COLUMN total_kgs DECIMAL(15,2) DEFAULT 0.00 AFTER total_pcs"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Executed: $q <br>";
    } else {
        echo "Error executing $q: " . $conn->error . "<br>";
    }
}
$conn->close();
?>
