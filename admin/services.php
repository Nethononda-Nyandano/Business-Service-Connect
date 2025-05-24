<?php
require_once '../includes/header.php';

// Require admin login
requireAdmin();

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$providerId = isset($_GET['provider']) ? (int)$_GET['provider'] : 0;
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list';
$errors = [];

// Handle service deletion
if ($action === 'delete' && $serviceId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);

        if ($stmt->rowCount() > 0) {
            setFlashMessage('success', 'Service deleted successfully!');
        } else {
            setFlashMessage('error', 'Service not found.');
        }

        $redirectQuery = [];
        if ($categoryId) $redirectQuery[] = "category=$categoryId";
        if ($providerId) $redirectQuery[] = "provider=$providerId";
        $redirectUrl = 'services.php' . (!empty($redirectQuery) ? '?' . implode('&', $redirectQuery) : '');

        header('Location: ' . $redirectUrl);
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Handle service status toggle
if ($action === 'toggle' && $serviceId) {
    try {
        $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$serviceId]);

        if ($stmt->rowCount() > 0) {
            setFlashMessage('success', 'Service status updated successfully!');
        } else {
            setFlashMessage('error', 'Service not found.');
        }

        $redirectQuery = [];
        if ($categoryId) $redirectQuery[] = "category=$categoryId";
        if ($providerId) $redirectQuery[] = "provider=$providerId";
        $redirectUrl = 'services.php' . (!empty($redirectQuery) ? '?' . implode('&', $redirectQuery) : '');

        header('Location: ' . $redirectUrl);
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Get all categories
$categories = getAllCategories();

// Get providers with profiles
$stmt = $pdo->query("
    SELECT pp.id, pp.business_name, u.username 
    FROM provider_profiles pp
    JOIN users u ON pp.user_id = u.id
    ORDER BY pp.business_name
");
$providers = $stmt->fetchAll();

// Get services with optional filters
$sql = "
    SELECT s.*, c.name as category_name, pp.business_name, u.username as provider_username
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($categoryId) {
    $sql .= " AND s.category_id = ?";
    $params[] = $categoryId;
}

if ($providerId) {
    $sql .= " AND s.provider_id = ?";
    $params[] = $providerId;
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Manage Services</h2>
            <div>
                <a href="categories.php" class="btn btn-outline-primary">
                    <i class="fas fa-folder me-1"></i> Manage Categories
                </a>
            </div>
        </div>

        <?php if (!empty($errors) && isset($errors['database'])): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $errors['database']; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filter Services</h5>
            </div>
            <div class="card-body">
                <form action="services.php" method="get" class="row g-3">
                    <div class="col-md-5">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($categoryId == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="provider" class="form-label">Provider</label>
                        <select class="form-select" id="provider" name="provider">
                            <option value="">All Providers</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?php echo $provider['id']; ?>" <?php echo ($providerId == $provider['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($provider['business_name']); ?> (<?php echo htmlspecialchars($provider['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Provider</th>
                                <th>Category</th>
                                <th>Price Range</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo $service['id']; ?></td>
                                        <td><?php echo htmlspecialchars($service['title']); ?></td>
                                        <td><?php echo htmlspecialchars($service['business_name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                        <td><?php echo $service['price_range'] ? htmlspecialchars($service['price_range']) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $service['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($service['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="http://localhost/BusinessServiceTracker/customer/search.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="services.php?action=toggle&id=<?php echo $service['id']; ?>" class="btn btn-<?php echo $service['is_active'] ? 'warning' : 'success'; ?>">
                                                    <i class="fas <?php echo $service['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i> <?php echo $service['is_active'] ? 'Disable' : 'Enable'; ?>
                                                </a>
                                                <a href="services.php?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this service? This action cannot be undone.')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
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

<?php
require_once '../includes/footer.php';
?>