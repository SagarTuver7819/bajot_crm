<?php
$page_title = 'Expense Category';
require_once 'includes/header.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'];

    if ($mode === 'add' || $mode === 'edit') {
        $name = trim($_POST['name']);
        
        if ($mode === 'add') {
            $stmt = $conn->prepare("INSERT INTO expense_categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE expense_categories SET name=? WHERE id=?");
            $stmt->bind_param("si", $name, $id);
        }

        if ($stmt->execute()) {
            redirect('expense_category.php?success=1');
        } else {
            redirect('expense_category.php?error=1');
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM expense_categories WHERE id=$id");
    redirect('expense_category.php?deleted=1');
}

// Fetch data for form
$category = null;
if ($mode === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM expense_categories WHERE id=$id");
    $category = $result->fetch_assoc();
}
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0 text-theme">Manage Expense Categories</h4>
        <?php if ($mode === 'list'): ?>
            <a href="expense_category.php?mode=add" class="btn btn-gold">
                <i class="fa fa-plus me-1"></i> Add New Category
            </a>
        <?php else: ?>
            <a href="expense_category.php" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mode === 'list'): ?>
    <div class="card card-bajot">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom datatable w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT * FROM expense_categories ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="expense_category.php?mode=edit&id=<?php echo $row['id']; ?>" class="btn btn-outline-gold" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <?php if (is_admin()): ?>
                                    <a href="expense_category.php?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger delete-btn" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card card-bajot max-width-600 mx-auto">
        <div class="card-header bg-transparent border-0 pt-4 px-4">
            <h5 class="fw-bold text-theme"><?php echo ucfirst($mode); ?> Expense Category</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="mode" value="<?php echo $mode; ?>">
                <?php if ($category): ?>
                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo $category ? htmlspecialchars($category['name']) : ''; ?>" placeholder="e.g. Electricity, Rent, Salary">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-gold">
                        <?php echo ($mode === 'add') ? 'Save' : 'Update'; ?> Category <i class="fa fa-save ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
