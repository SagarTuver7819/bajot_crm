<div class="sidebar d-flex flex-column" id="sidebar">
    <?php $s = get_settings(); ?>
    <a href="index.php" class="logo-area text-center py-4 cursor-pointer text-decoration-none d-block">
        <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
            <img src="<?php echo $s['company_logo']; ?>" alt="Logo" class="img-fluid mb-2 px-4" style="max-height: 80px; width: 100%; object-fit: contain;">
        <?php else: ?>
            <h2 class="mb-0" style="color: var(--gold); letter-spacing: 2px;">KAIZER</h2>
            <p class="text-muted extra-small small mb-0">C R M</p>
        <?php endif; ?>
    </a>
    
    <nav class="sidebar-nav flex-grow-1 overflow-auto mt-3">
        <?php if (has_permission('dashboard', 'view')): ?>
        <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fa fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('parties', 'view')): ?>
        <a href="party_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'party_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-users"></i> <span>Party Management</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('products', 'view')): ?>
        <a href="product_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'product_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-box"></i> <span>Product Management</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('inward', 'view')): ?>
        <a href="inward_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inward_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-download"></i> <span>Inward (Purchase)</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('outward', 'view')): ?>
        <a href="outward_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'outward_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-upload"></i> <span>Outward (Sales)</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('inventory', 'view')): ?>
        <a href="inventory.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
            <i class="fa fa-warehouse"></i> <span>Stock</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('vouchers', 'view')): ?>
        <a href="vouchers.php?vmethod=cash" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vouchers.php' && ($_GET['vmethod'] ?? 'cash') == 'cash') ? 'active' : ''; ?>">
            <i class="fa fa-money-bill-wave"></i> <span>Cash Transactions</span>
        </a>
        <a href="vouchers.php?vmethod=bank" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vouchers.php' && ($_GET['vmethod'] ?? '') == 'bank') ? 'active' : ''; ?>">
            <i class="fa fa-university"></i> <span>Bank Transactions</span>
        </a>
        <a href="banks.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'banks.php') ? 'active' : ''; ?>">
            <i class="fa fa-building-columns"></i> <span>Bank Accounts</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('expense_category', 'view')): ?>
        <a href="expense_category.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_category.php') ? 'active' : ''; ?>">
            <i class="fa fa-list-alt"></i> <span>Expense Category</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('expense_dashboard', 'view')): ?>
        <a href="expense_dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_dashboard.php') ? 'active' : ''; ?>">
            <i class="fa fa-chart-pie"></i> <span>Expense Dashboard</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('expenses', 'view')): ?>
        <a href="expenses.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php') ? 'active' : ''; ?>">
            <i class="fa fa-wallet"></i> <span>Expense Management</span>
        </a>
        <?php endif; ?>
        
        
        <?php if (has_permission('reports', 'view')): ?>
        <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <i class="fa fa-file-invoice"></i> <span>Reports</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('ledger', 'view')): ?>
        <a href="ledger.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'ledger.php') ? 'active' : ''; ?>">
            <i class="fa fa-book"></i> <span>Party Ledger</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('party_balances', 'view')): ?>
        <a href="party_balances.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'party_balances.php') ? 'active' : ''; ?>">
            <i class="fa fa-list-check"></i> <span>Party Balances</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('kasar', 'view')): ?>
        <a href="kasar.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'kasar.php') ? 'active' : ''; ?>">
            <i class="fa fa-adjust"></i> <span>Kasar / Adjustment</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('roles', 'view')): ?>
        <a href="role_management.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'role_management.php') ? 'active' : ''; ?>">
            <i class="fa fa-shield-alt"></i> <span>Role Management</span>
        </a>
        <?php endif; ?>

        <?php if (has_permission('users', 'view')): ?>
        <a href="user_management.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'user_management.php') ? 'active' : ''; ?>">
            <i class="fa fa-user-shield"></i> <span>User Management</span>
        </a>
        <?php endif; ?>
        
        <?php if (has_permission('settings', 'view')): ?>
        <a href="settings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
            <i class="fa fa-cog"></i> <span>Settings</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer p-3 text-center border-top border-secondary">
        <a href="logout.php" class="text-danger text-decoration-none small d-flex align-items-center justify-content-center gap-2">
            <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>
