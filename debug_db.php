<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting DB Fix...\n";
$conn = new mysqli('localhost', 'root', '', 'bajot_crm');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected successfully\n";

$queries = [
    "ALTER TABLE products ADD COLUMN opening_pcs DECIMAL(15,2) DEFAULT 0.00 AFTER unit",
    "ALTER TABLE products ADD COLUMN opening_kgs DECIMAL(15,2) DEFAULT 0.00 AFTER opening_pcs",
    "ALTER TABLE products ADD COLUMN total_pcs DECIMAL(15,2) DEFAULT 0.00 AFTER opening_kgs",
    "ALTER TABLE products ADD COLUMN total_kgs DECIMAL(15,2) DEFAULT 0.00 AFTER total_pcs"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Executed: $q\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
$conn->close();
echo "Done.\n";
?>
