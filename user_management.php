<?php
require_once 'config.php';
require_once 'includes/auth.php';
check_login();

if (!has_permission('users', 'view')) {
    die("Access denied.");
}

$success = '';
$error = '';

// Handle User Creation
if (isset($_POST['add_user'])) {
    $username = clean($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = clean($_POST['name']);
    $role_id = (int)$_POST['role_id'];
    
    // Get role name for the 'role' column (compatibility)
    $role_res = $conn->query("SELECT name FROM roles WHERE id = $role_id");
    $role_data = $role_res->fetch_assoc();
    $role_name = strtolower($role_data['name']);

    $stmt = $conn->prepare("INSERT INTO users (username, password, name, role, role_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $password, $name, $role_name, $role_id);
    if ($stmt->execute()) {
        $success = "User created successfully.";
    } else {
        $error = "Error creating user. Username might already exist.";
    }
}

// Handle User Status Toggle
if (isset($_GET['toggle_status'])) {
    $uid = (int)$_GET['toggle_status'];
    $conn->query("UPDATE users SET status = 1 - status WHERE id = $uid AND id != 1"); // Prevent disabling admin #1
    $success = "User status updated.";
}

// Handle User Delete
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid != 1) { // Prevent deleting admin #1
        $conn->query("DELETE FROM users WHERE id = $uid");
        $success = "User deleted successfully.";
    }
}

$users = $conn->query("SELECT u.*, r.name as role_display FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC");
$roles = $conn->query("SELECT * FROM roles ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management | Kaizer CRM</title>
    <?php include 'includes/header.php'; ?>
</head>
<body>
    <div class="d-flex">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content_area flex-grow-1">
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0 text-theme">System User Management</h2>
                    <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fa fa-user-plus"></i> Add New User
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="card card-bajot">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($u = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $u['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-theme"><?php echo $u['name']; ?></div>
                                        </td>
                                        <td><?php echo $u['username']; ?></td>
                                        <td>
                                            <span class="badge bg-gold text-dark"><?php echo $u['role_display'] ?? 'No Role'; ?></span>
                                        </td>
                                        <td>
                                            <?php if($u['status']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-secondary-themed"><?php echo date('d M Y, h:i A', strtotime($u['created_at'])); ?></td>
                                        <td class="text-end">
                                            <?php if($u['id'] != 1): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?toggle_status=<?php echo $u['id']; ?>" class="btn btn-outline-secondary" title="Toggle Status">
                                                        <i class="fa <?php echo $u['status'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                    </a>
                                                    <a href="?delete_user=<?php echo $u['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')" title="Delete User">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Main Admin Locked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Login User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required placeholder="johndoe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="********">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Role</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role...</option>
                                <?php 
                                $roles->data_seek(0);
                                while($r = $roles->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
