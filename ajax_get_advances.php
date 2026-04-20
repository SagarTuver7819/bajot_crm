<?php
require_once 'config.php';

if (isset($_GET['employee_id']) && isset($_GET['month']) && isset($_GET['year'])) {
    $emp_id = (int)$_GET['employee_id'];
    $month = $_GET['month'];
    $year = (int)$_GET['year'];
    
    // Calculate total advances for this employee in this month/year
    // Note: month is stored as name (e.g. 'April'), year as INT
    $res = $conn->query("SELECT SUM(amount) as total FROM salary_advances 
                         WHERE employee_id = $emp_id 
                         AND YEAR(date) = $year 
                         AND DATE_FORMAT(date, '%M') = '$month'");
    
    $row = $res->fetch_assoc();
    echo json_encode(['total' => $row['total'] ?: 0]);
}
?>
