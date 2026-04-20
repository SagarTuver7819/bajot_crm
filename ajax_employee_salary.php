<?php
ob_clean();
header('Content-Type: application/json');
require_once 'config.php';

$response = null;

if (isset($_GET['employee_id'])) {
    $id = (int)$_GET['employee_id'];
    $res = $conn->query("SELECT * FROM employees WHERE id = $id");
    if ($res && $row = $res->fetch_assoc()) {
        $response = [
            'salary' => $row['salary'],
            'week_off' => $row['week_off'],
            'mobile_number' => $row['mobile_number']
        ];
    }
}

echo json_encode($response);
exit;
?>
