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
        <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fa fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
        
        <a href="party_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'party_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-users"></i> <span>Party Management</span>
        </a>
        
        <a href="product_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'product_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-box"></i> <span>Product Management</span>
        </a>
        
        <a href="inward_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inward_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-download"></i> <span>Inward (Purchase)</span>
        </a>
        
        <a href="outward_crud.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'outward_crud.php') ? 'active' : ''; ?>">
            <i class="fa fa-upload"></i> <span>Outward (Sales)</span>
        </a>
        
        <a href="inventory.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
            <i class="fa fa-warehouse"></i> <span>Stock</span>
        </a>
        
        <a href="vouchers.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'vouchers.php') ? 'active' : ''; ?>">
            <i class="fa fa-receipt"></i> <span>Cash / Bank</span>
        </a>

        <a href="expense_category.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_category.php') ? 'active' : ''; ?>">
            <i class="fa fa-list-alt"></i> <span>Expense Category</span>
        </a>
        
        <a href="expense_dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expense_dashboard.php') ? 'active' : ''; ?>">
            <i class="fa fa-chart-pie"></i> <span>Expense Dashboard</span>
        </a>

        <a href="expenses.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'expenses.php') ? 'active' : ''; ?>">
            <i class="fa fa-wallet"></i> <span>Expense Management</span>
        </a>
        
        <a href="employees.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>">
            <i class="fa fa-user-tie"></i> <span>Staff & HR</span>
        </a>
        
        <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <i class="fa fa-file-invoice"></i> <span>Reports</span>
        </a>
        
        <a href="ledger.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'ledger.php') ? 'active' : ''; ?>">
            <i class="fa fa-book"></i> <span>Party Ledger</span>
        </a>

        <a href="kasar.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'kasar.php') ? 'active' : ''; ?>">
            <i class="fa fa-adjust"></i> <span>Kasar / Adjustment</span>
        </a>
        
        <a href="settings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
            <i class="fa fa-cog"></i> <span>Settings</span>
        </a>
    </nav>
    
    <div class="sidebar-footer p-3 text-center border-top border-secondary">
        <a href="logout.php" class="text-danger text-decoration-none small d-flex align-items-center justify-content-center gap-2">
            <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>
