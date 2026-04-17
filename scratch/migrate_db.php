<?php
require_once 'config.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS banks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        branch VARCHAR(255),
        account_no VARCHAR(50),
        opening_balance DECIMAL(15, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "ALTER TABLE vouchers ADD COLUMN payment_method ENUM('cash', 'bank') NOT NULL DEFAULT 'cash' AFTER type",
    "ALTER TABLE vouchers ADD COLUMN bank_id INT DEFAULT NULL AFTER payment_method",
    "ALTER TABLE vouchers ADD CONSTRAINT fk_bank FOREIGN KEY (bank_id) REFERENCES banks(id)"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error: " . $conn->error . " for query: $sql\n";
    }
}
