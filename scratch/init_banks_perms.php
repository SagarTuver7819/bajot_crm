<?php
require_once 'config.php';

$roles = $conn->query('SELECT id FROM roles');
while($role = $roles->fetch_assoc()) {
    $role_id = $role['id'];
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, module, can_view, can_add, can_edit, can_delete) 
                  SELECT $role_id, 'banks', 1, 1, 1, 1 
                  WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE role_id = $role_id AND module = 'banks')");
}
echo "Role permissions initialized for banks module.\n";
