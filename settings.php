<?php
$page_title = 'Settings';
require_once 'includes/header.php';

$msg = '';

$s = get_settings();

// Update Company Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $c_name = clean($_POST['company_name']);
    $c_address = clean($_POST['company_address']);
    
    // Logo Upload
    $logo_path = $s['company_logo'] ?? '';
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === 0) {
        $ext = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
        $new_name = "logo_" . time() . "." . $ext;
        $target = "assets/images/" . $new_name;
        
        if (!is_dir('assets/images')) mkdir('assets/images', 0777, true);
        
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $target)) {
            $logo_path = $target;
        }
    }
    
    $conn->query("UPDATE settings SET company_name='$c_name', company_address='$c_address', company_logo='$logo_path'");
    redirect('settings.php?success=1');
}

$s = get_settings();
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0 text-theme">System Settings</h4>
    </div>
</div>



<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-bajot">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Company Information</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo $s['company_name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Company Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?php echo $s['company_address'] ?? ''; ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Company Logo (Optional)</label>
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($s['company_logo']): ?>
                                <img src="<?php echo $s['company_logo']; ?>" alt="Logo" style="height: 60px; width: 60px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                            <?php endif; ?>
                            <input type="file" name="company_logo" class="form-control" accept="image/*">
                        </div>
                        <small class="text-muted mt-1 d-block">Recommended size: 200x200px or higher.</small>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="save_settings" class="btn btn-gold px-5">Save Settings <i class="fa fa-save ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-bajot mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Theme Customization</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Dark Mode Theme</span>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="themeSwitch" <?php echo ($_SESSION['theme'] == 'dark') ? 'checked' : ''; ?>>
                    </div>
                </div>
                <div class="alert alert-info py-2 small">
                    <i class="fa fa-info-circle me-1"></i> Use toggle at top right to switch themes instantly.
                </div>
            </div>
        </div>

        <div class="card card-bajot">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Admin Access</h5>
            </div>
            <div class="card-body p-4 text-center">
                <img src="https://img.icons8.com/color/48/000000/password.png" alt="Lock" class="mb-3">
                <p class="text-muted small">Update your admin password for better security.</p>
                <button class="btn btn-outline-gold w-100">Change Password</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('themeSwitch').addEventListener('change', function() {
        document.getElementById('themeToggle').click();
    });
</script>

<?php require_once 'includes/footer.php'; ?>
