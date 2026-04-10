<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_kasar') {
    $party_id = (int)$_POST['party_id'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type'] ?? ''; // may be empty from kasar.php
    $date = $_POST['date'];
    $description = $conn->real_escape_string($_POST['description']);

    if (!$party_id || $amount <= 0 || !$date) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid data']);
        exit;
    }

    // Automatically determine type if not provided
    if (empty($type)) {
        // Calculate current balance (global to match ledger)
        $sql_bal = "SELECT 
            (SELECT COALESCE(opening_balance, 0) FROM parties WHERE id = $party_id) +
            (SELECT COALESCE(SUM(total_amount), 0) FROM outwards WHERE party_id = $party_id) +
            (SELECT COALESCE(SUM(amount), 0) FROM vouchers WHERE party_id = $party_id AND type IN ('payment', 'expense')) +
            (SELECT COALESCE(SUM(amount), 0) FROM kasars WHERE party_id = $party_id AND type = 'received') -
            (SELECT COALESCE(SUM(total_amount), 0) FROM inwards WHERE party_id = $party_id) -
            (SELECT COALESCE(SUM(amount), 0) FROM vouchers WHERE party_id = $party_id AND type = 'receipt') -
            (SELECT COALESCE(SUM(amount), 0) FROM kasars WHERE party_id = $party_id AND type = 'allowed') 
            as balance";
        $res_bal = $conn->query($sql_bal);
        $row_bal = $res_bal->fetch_assoc();
        $balance = $row_bal['balance'] ?? 0;
        
        $type = ($balance > 0) ? 'allowed' : 'received';
    }

    $dept_id = (int)$_SESSION['dept_id'];
    $sql = "INSERT INTO kasars (dept_id, party_id, amount, type, date, description) VALUES ($dept_id, $party_id, $amount, '$type', '$date', '$description')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

if ($action === 'delete_kasar') {
    $id = (int)$_POST['id'];
    if ($conn->query("DELETE FROM kasars WHERE id=$id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'get_balance') {
    $party_id = (int)$_POST['party_id'];
    
    $sql_bal = "SELECT 
        (SELECT COALESCE(opening_balance, 0) FROM parties WHERE id = $party_id) +
        (SELECT COALESCE(SUM(total_amount), 0) FROM outwards WHERE party_id = $party_id) +
        (SELECT COALESCE(SUM(amount), 0) FROM vouchers WHERE party_id = $party_id AND type IN ('payment', 'expense')) +
        (SELECT COALESCE(SUM(amount), 0) FROM kasars WHERE party_id = $party_id AND type = 'received') -
        (SELECT COALESCE(SUM(total_amount), 0) FROM inwards WHERE party_id = $party_id) -
        (SELECT COALESCE(SUM(amount), 0) FROM vouchers WHERE party_id = $party_id AND type = 'receipt') -
        (SELECT COALESCE(SUM(amount), 0) FROM kasars WHERE party_id = $party_id AND type = 'allowed') 
        as balance";
    $res_bal = $conn->query($sql_bal);
    $row_bal = $res_bal->fetch_assoc();
    $balance = $row_bal['balance'] ?? 0;
    
    echo json_encode(['success' => true, 'balance' => $balance, 'formatted_balance' => format_currency(abs($balance)), 'is_debit' => ($balance > 0)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
?>
