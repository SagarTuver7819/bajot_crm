<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit'] ?? 'Kgs');
    $opening_pcs = (float)($_POST['opening_pcs'] ?? 0);
    $opening_kgs = (float)($_POST['opening_kgs'] ?? 0);
    $rate = (float)($_POST['rate'] ?? 0);
    $dept_id = (int)$_SESSION['dept_id'];

    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product Name is required.']);
        exit;
    }

    // Duplicate Check
    $clean_name = clean($name);
    $check = $conn->query("SELECT id FROM products WHERE name = '$clean_name' AND dept_id = $dept_id");
    if ($check->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product already exists in this department.']);
        exit;
    }

    $current_stock = ($unit === 'Pcs') ? $opening_pcs : $opening_kgs;
    $stmt = $conn->prepare("INSERT INTO products (dept_id, name, unit, opening_pcs, opening_kgs, total_pcs, total_kgs, rate, current_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdddddd", $dept_id, $name, $unit, $opening_pcs, $opening_kgs, $opening_pcs, $opening_kgs, $rate, $current_stock);
    
    header('Content-Type: application/json');
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'rate' => $rate]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}
?>
