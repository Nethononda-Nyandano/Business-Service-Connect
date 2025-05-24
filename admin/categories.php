<?php
require_once '../includes/header.php';

// Require admin login
requireAdmin();

// Get action parameters
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list';
$errors = [];

// Process category form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

    // Basic validation
    if (empty($name)) {
        $errors['name'] = 'Category name is required';
    }

    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }

    // If no validation errors, save or update the category
    if (empty($errors)) {
        try {
            if ($action === 'add') {
                // Add new category
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);

                setFlashMessage('success', 'Category added successfully!');
                header('Location: categories.php');
                exit();
            } elseif ($action === 'edit' && $categoryId) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $categoryId]);

                setFlashMessage('success', 'Category updated successfully!');
                header('Location: categories.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle category deletion
if ($action === 'delete' && $categoryId) {
    try {
        // Check if category is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $serviceCount = $stmt->fetchColumn();

        if ($serviceCount > 0) {
            setFlashMessage('error', 'Cannot delete category because it is used by one or more services.');
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);

            setFlashMessage('success', 'Category deleted successfully!');
        }

        header('Location: categories.php');
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Get category for editing
$category = null;
if ($action === 'edit' && $categoryId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        setFlashMessage('error', 'Category not found');
        header('Location: categories.php');
        exit();
    }
}

// Get all categories
$categories = getAllCategories();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Manage Categories</h2>
            <div>
                <?php if ($action === 'list'): ?>
                    <a href="categories.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Add New Category
                    </a>
                <?php else: ?>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Categories
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($errors) && isset($errors['database'])): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $errors['database']; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category'; ?></h4>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($categoryId ? "&id=$categoryId" : '')); ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : (isset($category['name']) ? htmlspecialchars($category['name']) : ''); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : (isset($category['description']) ? htmlspecialchars($category['description']) : ''); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['description']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="categories.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action === 'add' ? 'Add Category' : 'Save Changes'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Services</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No categories found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php
                                        // Get service count for this category
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
                                        $stmt->execute([$cat['id']]);
                                        $serviceCount = $stmt->fetchColumn();
                                        ?>
                                        <tr>
                                            <td><?php echo $cat['id']; ?></td>
                                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                            <td><?php echo $serviceCount; ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="services.php?category=<?php echo $cat['id']; ?>" class="btn btn-info">
                                                        <i class="fas fa-list"></i> Services
                                                    </a>
                                                    <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <?php if ($serviceCount == 0): ?>
                                                        <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>