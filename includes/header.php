<?php
require_once 'config.php';
check_login();

$page_title = isset($page_title) ? $page_title : 'Dashboard';
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';

// Department Definitions
$departments = [
    1 => 'Aluminium Section',
    2 => 'Powder Coating',
    3 => 'Anodizing Section'
];

// Handle Department Selection via AJAX/Post if needed
if (isset($_POST['set_dept_id'])) {
    $_SESSION['dept_id'] = (int)$_POST['set_dept_id'];
    $_SESSION['dept_name'] = $departments[$_SESSION['dept_id']];
    echo json_encode(['success' => true]);
    exit;
}

// Redirect to dashboard if trying to access entries without department
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'index.php' && $current_page !== 'login.php' && $current_page !== 'logout.php' && !isset($_SESSION['dept_id'])) {
    header("Location: index.php?select_dept=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Kaizer CRM</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        /* Small fixes for Dark/Light mode depending on session */
        <?php if ($theme === 'dark'): ?>
        body { background-color: var(--dark-bg); color: var(--text-light); }
        .sidebar { background-color: var(--dark-sidebar); border-right: 1px solid rgba(255, 255, 255, 0.05); }
        .card-bajot { background-color: var(--dark-card); color: var(--text-light); }
        .table-custom { color: var(--text-light); }
        :root { --text-secondary-themed: rgba(255, 255, 255, 0.5); }
        <?php else: ?>
        body { background-color: var(--light-bg); color: var(--text-dark); }
        .sidebar { background-color: var(--light-sidebar); border-right: 1px solid #ebebeb; }
        .card-bajot { background-color: var(--light-card); color: var(--text-dark); }
        .table-custom { color: var(--text-dark); }
        .nav-link { color: #555 !important; }
        :root { --text-secondary-themed: rgba(0, 0, 0, 0.5); }
        <?php endif; ?>

        .text-secondary-themed { color: var(--text-secondary-themed) !important; }
        .bg-dark-card { background-color: <?php echo ($theme === 'dark' ? '#1a1a27' : '#f8f9fa'); ?> !important; color: <?php echo ($theme === 'dark' ? '#fff' : '#212529'); ?> !important; }
        .border-secondary { border-color: <?php echo ($theme === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'); ?> !important; }
        .btn-outline-gold { border-color: var(--gold); color: var(--gold); }
        .btn-outline-gold:hover { background-color: var(--gold); color: #000; }

        /* Visibility tweaks for Dark Mode */
        .dark-mode .text-muted { color: #a0a0a5 !important; }
        .dark-mode .bg-light { background-color: rgba(255, 255, 255, 0.05) !important; color: #fff !important; }
        .dark-mode .form-label { color: #e1e1e3 !important; }
    </style>
</head>
<body class="<?php echo $theme; ?>-mode">

    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content w-100">
            <!-- Navbar Area -->
            <div class="navbar-top d-flex justify-content-between align-items-center mb-4">
                <div class="page-title-area d-flex align-items-center gap-3">
                    <div id="sidebarToggle" class="sidebar-toggle-btn cursor-pointer">
                        <i class="fa fa-bars fs-4 text-theme"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0" style="color: var(--gold); font-size: 1.1rem;"><?php echo $page_title; ?></h3>
                        <p class="text-secondary-themed extra-small mb-0 d-none d-md-block">
                            Welcome back, <span class="<?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $_SESSION['name']; ?></span>
                            <?php if(isset($_SESSION['dept_name'])): ?>
                                <span class="mx-2 text-secondary">|</span> 
                                <span class="text-gold fw-bold"><i class="fa fa-layer-group me-1"></i><?php echo $_SESSION['dept_name']; ?></span>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#deptModal" class="ms-2 text-decoration-none extra-small text-info opacity-75">(Change)</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="navbar-actions d-flex align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <div id="themeToggle" class="theme-switch" title="Toggle Light/Dark Mode">
                        <i class="fa <?php echo ($theme === 'dark') ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <div class="d-flex align-items-center gap-2 cursor-pointer" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="profile-avatar bg-gold text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: var(--gold);">
                                <i class="fa fa-user-circle fs-4"></i>
                            </div>
                            <div class="profile-info d-none d-md-block">
                                <p class="mb-0 fw-bold small"><?php echo $_SESSION['name']; ?></p>
                                <p class="mb-0 text-muted extra-small" style="font-size: 0.7rem;"><?php echo strtoupper($_SESSION['role']); ?></p>
                            </div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="settings.php"><i class="fa fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Department Selection Modal -->
            <div class="modal fade" id="deptModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark-card border border-secondary shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><i class="fa fa-building-user text-gold me-2"></i>Select Working Department</h5>
                        </div>
                        <div class="modal-body p-4 pt-1">
                            <p class="text-secondary-themed small mb-4">Please select a department to proceed with entries and data management.</p>
                            <div class="d-grid gap-3">
                                <?php foreach($departments as $id => $name): ?>
                                    <button type="button" class="btn btn-outline-gold py-3 text-start d-flex align-items-center justify-content-between select-dept-btn" data-id="<?php echo $id; ?>">
                                        <span class="fw-bold <?php echo ($theme === 'dark' ? 'text-white' : 'text-dark'); ?>"><?php echo $name; ?></span>
                                        <i class="fa fa-chevron-right op-5 text-secondary"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Show modal if dept not set and on index
                    <?php if(!isset($_SESSION['dept_id']) && basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                        var myModal = new bootstrap.Modal(document.getElementById('deptModal'));
                        myModal.show();
                    <?php endif; ?>

                    // Handle Department Selection
                    document.querySelectorAll('.select-dept-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const deptId = this.getAttribute('data-id');
                            const formData = new FormData();
                            formData.append('set_dept_id', deptId);

                            fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if(data.success) {
                                    window.location.reload();
                                }
                            });
                        });
                    });
                });
            </script>
