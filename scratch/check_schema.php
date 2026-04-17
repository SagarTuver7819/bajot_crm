<?php
require_once 'config.php';

echo "--- Vouchers Table ---\n";
$res = $conn->query("DESCRIBE vouchers");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Checking for banks table ---\n";
$res = $conn->query("SHOW TABLES LIKE 'banks'");
if ($res->num_rows > 0) {
    echo "Banks table exists.\n";
    $res = $conn->query("DESCRIBE banks");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Banks table does not exist.\n";
}
