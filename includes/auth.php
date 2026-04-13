<?php
/**
 * Authentication and Permission Helper
 * Created as an alternative to modifying config.php
 */

function has_permission($module, $action) {
    global $conn;
    
    // Check if it's the main admin (ID 1) or has 'admin' role name for backward compatibility
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 1) return true;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') return true;
    
    if (!isset($_SESSION['role_id'])) return false;
    
    $role_id = $_SESSION['role_id'];
    $column = "can_" . $action;
    
    // Safety check for action name
    $allowed_actions = ['view', 'add', 'edit', 'delete'];
    if (!in_array($action, $allowed_actions)) return false;
    
    $stmt = $conn->prepare("SELECT $column FROM role_permissions WHERE role_id = ? AND module = ?");
    $stmt->bind_param("is", $role_id, $module);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row[$column] == 1;
    }
    
    return false;
}
?>
