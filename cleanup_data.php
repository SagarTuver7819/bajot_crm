<?php
require_once 'config.php';

echo "<h2>Cleaning up escaped characters in database...</h2>";

// Cleanup Products
$res = $conn->query("SELECT id, name FROM products WHERE name LIKE '%\\\\\\%'");
if ($res->num_rows > 0) {
    echo "<h3>Products:</h3>";
    while ($row = $res->fetch_assoc()) {
        $new_name = stripslashes($row['name']);
        $id = $row['id'];
        $stmt = $conn->prepare("UPDATE products SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $new_name, $id);
        $stmt->execute();
        echo "Fixed: " . htmlspecialchars($row['name']) . " -> " . htmlspecialchars($new_name) . "<br>";
    }
} else {
    echo "No products with escaped characters found.<br>";
}

// Cleanup Parties
$res = $conn->query("SELECT id, name FROM parties WHERE name LIKE '%\\\\\\%'");
if ($res->num_rows > 0) {
    echo "<h3>Parties:</h3>";
    while ($row = $res->fetch_assoc()) {
        $new_name = stripslashes($row['name']);
        $id = $row['id'];
        $stmt = $conn->prepare("UPDATE parties SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $new_name, $id);
        $stmt->execute();
        echo "Fixed Party: " . htmlspecialchars($row['name']) . " -> " . htmlspecialchars($new_name) . "<br>";
    }
}

// Cleanup Employees
$res = $conn->query("SELECT id, name, role FROM employees WHERE name LIKE '%\\\\\\%' OR role LIKE '%\\\\\\%'");
if ($res->num_rows > 0) {
    echo "<h3>Employees:</h3>";
    while ($row = $res->fetch_assoc()) {
        $new_name = stripslashes($row['name']);
        $new_role = stripslashes($row['role']);
        $id = $row['id'];
        $stmt = $conn->prepare("UPDATE employees SET name = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_name, $new_role, $id);
        $stmt->execute();
        echo "Fixed Employee: " . htmlspecialchars($row['name']) . " -> " . htmlspecialchars($new_name) . "<br>";
    }
}

echo "<br><br><strong>Cleanup Complete!</strong> Please delete this file for security.";
?>
