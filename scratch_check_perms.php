<?php
require_once 'config.php';
$res = $conn->query("DESCRIBE role_permissions");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
