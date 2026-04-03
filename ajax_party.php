<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $mobile = trim($_POST['mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);

    if (empty($name) || empty($type)) {
        echo json_encode(['status' => 'error', 'message' => 'Name and Type are required']);
        exit;
    }

    // Duplication Check (using cleaned name for safety in direct query)
    $clean_name = clean($name);
    $check = $conn->query("SELECT id FROM parties WHERE name = '$clean_name'");
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A party with this name already exists!']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO parties (name, type, mobile, address, opening_balance) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $name, $type, $mobile, $address, $opening_balance);

    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['status' => 'success', 'id' => $id, 'name' => $name]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
