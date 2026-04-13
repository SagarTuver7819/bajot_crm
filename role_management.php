<?php
require_once 'config.php';
require_once 'includes/auth.php';
check_login();

if (!has_permission('roles', 'view')) {
    die("Access denied.");
}

$success = '';
$error = '';

// Handle Role Creation
if (isset($_POST['add_role'])) {
    $role_name = clean($_POST['role_name']);
    if (!empty($role_name)) {
        $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
        $stmt->bind_param("s", $role_name);
        if ($stmt->execute()) {
            $role_id = $conn->insert_id;
            // Initialize permissions for all modules
            $modules = [
                'dashboard', 'parties', 'products', 'inward', 'outward', 'inventory', 
                'vouchers', 'expense_category', 'expense_dashboard', 'expenses', 
                'reports', 'ledger', 'party_balances', 'kasar', 
                'settings', 'roles', 'users'
            ];
            foreach ($modules as $mod) {
                $conn->query("INSERT INTO role_permissions (role_id, module) VALUES ($role_id, '$mod')");
            }
            $success = "Role added successfully.";
        } else {
            $error = "Error adding role.";
        }
    }
}

// Handle Permission Update
if (isset($_POST['update_permissions'])) {
    $role_id = $_POST['role_id'];
    $permissions = $_POST['permissions'] ?? []; // format: [module => [action => 1]]
    
    // Reset all permissions for this role first
    $conn->query("UPDATE role_permissions SET can_view=0, can_add=0, can_edit=0, can_delete=0 WHERE role_id = $role_id");
    
    foreach ($permissions as $module => $actions) {
        $can_view = isset($actions['view']) ? 1 : 0;
        $can_add = isset($actions['add']) ? 1 : 0;
        $can_edit = isset($actions['edit']) ? 1 : 0;
        $can_delete = isset($actions['delete']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE role_permissions SET can_view=?, can_add=?, can_edit=?, can_delete=? WHERE role_id = ? AND module = ?");
        $stmt->bind_param("iiiis", $can_view, $can_add, $can_edit, $can_delete, $role_id, $module);
        $stmt->execute();
    }
    $success = "Permissions updated successfully.";
}

$roles = $conn->query("SELECT * FROM roles ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Management | Kaizer CRM</title>
    <?php include 'includes/header.php'; ?>
    <style>
        .permission-table { background: transparent !important; color: inherit !important; }
        .permission-table tr { background: transparent !important; }
        .permission-table th { 
            background: <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.05)' : '#f8f9fa'); ?> !important; 
            color: <?php echo ($theme === 'dark' ? 'var(--gold)' : '#000'); ?> !important; 
            border-bottom: 2px solid var(--gold) !important; 
        }
        .permission-table td { 
            vertical-align: middle; 
            color: <?php echo ($theme === 'dark' ? 'var(--text-light)' : 'var(--text-dark)'); ?> !important; 
            border-color: <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'); ?> !important; 
        }
        .list-group-item {
            background-color: transparent !important;
            color: <?php echo ($theme === 'dark' ? 'var(--text-light)' : 'var(--text-dark)'); ?> !important;
            border-color: <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'); ?> !important;
        }
        .list-group-item.active {
            background-color: var(--gold) !important;
            border-color: var(--gold) !important;
            color: #000 !important;
        }
        .card-header {
            background-color: <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.02)' : 'rgba(0,0,0,0.02)'); ?> !important;
            color: <?php echo ($theme === 'dark' ? 'var(--gold)' : 'var(--text-dark)'); ?> !important;
            border-bottom: 1px solid <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'); ?> !important;
        }
        
        /* Checkbox visibility in dark mode */
        .form-check-input {
            background-color: transparent;
            border: 1px solid var(--gold);
        }
        .form-check-input:checked {
            background-color: var(--gold);
            border-color: var(--gold);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(201, 161, 74, 0.05);
        }
        .text-theme-bold {
            color: <?php echo ($theme === 'dark' ? '#fff' : '#000'); ?> !important;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content_area flex-grow-1">
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0 text-theme">Role & Permission Management</h2>
                    <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fa fa-plus"></i> Add New Role
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Roles List -->
                    <div class="col-md-3">
                        <div class="card card-bajot">
                            <div class="card-header font-weight-bold">Available Roles</div>
                            <div class="list-group list-group-flush">
                                <?php while($role = $roles->fetch_assoc()): ?>
                                    <a href="?role_id=<?php echo $role['id']; ?>" class="list-group-item list-group-item-action <?php echo (isset($_GET['role_id']) && $_GET['role_id'] == $role['id']) ? 'active' : ''; ?>">
                                        <i class="fa fa-user-tag me-2"></i> <?php echo $role['name']; ?>
                                        <?php if($role['id'] == 1): ?> <small class="badge bg-warning text-dark ms-auto">System</small> <?php endif; ?>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Grid -->
                    <div class="col-md-9">
                        <?php 
                        if (isset($_GET['role_id'])): 
                            $role_id = (int)$_GET['role_id'];
                            $role_data = $conn->query("SELECT * FROM roles WHERE id = $role_id")->fetch_assoc();
                            $perms = $conn->query("SELECT * FROM role_permissions WHERE role_id = $role_id");
                        ?>
                        <div class="card card-bajot">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Permissions for: <span class="text-gold"><?php echo $role_data['name']; ?></span></span>
                                <?php if($role_id != 1): ?>
                                    <form method="POST" id="permForm">
                                        <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                                        <button type="submit" name="update_permissions" class="btn btn-sm btn-success">
                                            <i class="fa fa-save"></i> Save Changes
                                        </button>
                                <?php else: ?>
                                    <span class="text-secondary-themed small">Admin role has full access by default.</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover permission-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Module</th>
                                            <th class="text-center">View</th>
                                            <th class="text-center">Add</th>
                                            <th class="text-center">Edit</th>
                                            <th class="text-center">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($p = $perms->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-capitalize text-theme-bold"><?php echo str_replace('_', ' ', $p['module']); ?></td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="permissions[<?php echo $p['module']; ?>][view]" value="1" <?php echo $p['can_view'] ? 'checked' : ''; ?> <?php echo $role_id == 1 ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="permissions[<?php echo $p['module']; ?>][add]" value="1" <?php echo $p['can_add'] ? 'checked' : ''; ?> <?php echo $role_id == 1 ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="permissions[<?php echo $p['module']; ?>][edit]" value="1" <?php echo $p['can_edit'] ? 'checked' : ''; ?> <?php echo $role_id == 1 ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" name="permissions[<?php echo $p['module']; ?>][delete]" value="1" <?php echo $p['can_delete'] ? 'checked' : ''; ?> <?php echo $role_id == 1 ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <?php if($role_id != 1): ?></form><?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card card-bajot p-5 text-center">
                            <i class="fa fa-shield-alt fa-3x text-gold opacity-50 mb-3"></i>
                            <h4 class="text-theme">Select a role to manage permissions</h4>
                            <p class="text-secondary-themed">You can customize what each role can see and do within the system.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" name="role_name" class="form-control" required placeholder="e.g. Sales Manager, Accountant">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_role" class="btn btn-primary">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
