<?php
require_once 'config.php';

echo "<h2>Fixing Corrupted Dates</h2>";

$tables = ['vouchers', 'outwards', 'inwards', 'kasars'];
$fixed_total = 0;

foreach ($tables as $table) {
    echo "Processing <b>$table</b>...<br>";
    
    // Attempt multiple patterns to find '0000-00-00' or NULL or empty
    $queries = [
        "UPDATE `$table` SET `date` = CURDATE() WHERE `date` = '0000-00-00'",
        "UPDATE `$table` SET `date` = CURDATE() WHERE `date` IS NULL",
        "UPDATE `$table` SET `date` = CURDATE() WHERE `date` = ''",
        "UPDATE `$table` SET `date` = CURDATE() WHERE CAST(`date` AS CHAR) LIKE '0000%'"
    ];
    
    foreach($queries as $q) {
        if ($conn->query($q)) {
            $fixed = $conn->affected_rows;
            if ($fixed > 0) {
                echo "- Fixed $fixed records using query: $q<br>";
                $fixed_total += $fixed;
            }
        } else {
            echo "- Error in query $q: " . $conn->error . "<br>";
        }
    }
}

echo "<hr><h4>Total Records Fixed: $fixed_total</h4>";
echo "<p><a href='vouchers.php'>Back to Vouchers</a></p>";
?>
