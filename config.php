<?php
session_start();
ob_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'jrosvllq_bajot_crm');
define('DB_PASS', '7[VnV^!cB}A+S8[1');
define('DB_NAME', 'jrosvllq_bajot_crm');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error connecting to database. Please make sure MySQL is running and the database 'bajot_crm' exists.");
}

// Global Variables
$primary_color = "#C9A14A"; // Gold
$secondary_color = "#1e1e2d"; // Dark

// Helper Functions
function clean($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function redirect($page) {
    if (!headers_sent()) {
        header("Location: $page");
    } else {
        echo "<script>window.location.href='$page';</script>";
    }
    exit();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

function get_settings() {
    global $conn;
    $result = $conn->query("SELECT * FROM settings LIMIT 1");
    return $result->fetch_assoc();
}

// Initialize default settings if not exists
if ($conn->query("SELECT COUNT(*) FROM settings")->fetch_row()[0] == 0) {
    $conn->query("INSERT INTO settings (company_name) VALUES ('Bajot Extrusion Pvt. Ltd.')");
}
?>
