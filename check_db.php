<?php
require_once 'config.php';
$tables = ['products', 'inward_items', 'outward_items'];
$output = "";
foreach ($tables as $table) {
    $output .= "Table: $table\n";
    $result = $conn->query("DESCRIBE $table");
    while($row = $result->fetch_assoc()) {
        $output .= "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    $output .= "\n";
}
file_put_contents('db_schema.txt', $output);
echo "Written to db_schema.txt";
?>
