<?php
require_once 'config.php';
check_login();

$page_title = isset($page_title) ? $page_title : 'Dashboard';
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Bajot CRM</title>
    
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
        <?php else: ?>
        body { background-color: var(--light-bg); color: var(--text-dark); }
        .sidebar { background-color: var(--light-sidebar); border-right: 1px solid #ebebeb; }
        .card-bajot { background-color: var(--light-card); color: var(--text-dark); }
        .table-custom { color: var(--text-dark); }
        .nav-link { color: #555 !important; }
        <?php endif; ?>
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
                        <p class="text-muted extra-small mb-0 d-none d-md-block">Welcome back, <?php echo $_SESSION['name']; ?></p>
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
