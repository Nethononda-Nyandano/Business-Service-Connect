<?php
require_once '../includes/header.php';

// Require provider login
requireProvider();

// Get provider data
$user = getCurrentUser();
$providerProfile = getProviderProfile($user['id']);

// If provider hasn't completed profile, redirect to profile page
if (!$providerProfile) {
    setFlashMessage('info', 'Please complete your business profile to continue');
    header('Location: /provider/profile.php');
    exit();
}

$providerId = $providerProfile['id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Handle service form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $priceRange = isset($_POST['price_range']) ? sanitizeInput($_POST['price_range']) : '';
    $availability = isset($_POST['availability']) ? sanitizeInput($_POST['availability']) : '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Basic validation
    if (empty($title)) {
        $errors['title'] = 'Service title is required';
    }
    
    if ($categoryId <= 0) {
        $errors['category_id'] = 'Please select a category';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Service description is required';
    }
    
    // If no validation errors, save the service
    if (empty($errors)) {
        try {
            if ($action === 'add' || ($action === 'edit' && !$serviceId)) {
                // Create new service
                $stmt = $pdo->prepare("
                    INSERT INTO services 
                    (provider_id, category_id, title, description, price_range, availability, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $providerId, $categoryId, $title, $description, $priceRange, $availability, $isActive
                ]);
                
                setFlashMessage('success', 'Service created successfully!');
            } else {
                // Update existing service
                $stmt = $pdo->prepare("
                    UPDATE services 
                    SET category_id = ?, title = ?, description = ?, price_range = ?, availability = ?, is_active = ?
                    WHERE id = ? AND provider_id = ?
                ");
                $stmt->execute([
                    $categoryId, $title, $description, $priceRange, $availability, $isActive, $serviceId, $providerId
                ]);
                
                setFlashMessage('success', 'Service updated successfully!');
            }
            
            header('Location: /provider/services.php');
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle service deletion
if ($action === 'delete' && $serviceId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND provider_id = ?");
        $stmt->execute([$serviceId, $providerId]);
        
        setFlashMessage('success', 'Service deleted successfully!');
        header('Location: /provider/services.php');
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Get service data for editing
$service = null;
if ($action === 'edit' && $serviceId) {
    $stmt = $pdo->prepare("
        SELECT * FROM services 
        WHERE id = ? AND provider_id = ?
    ");
    $stmt->execute([$serviceId, $providerId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        setFlashMessage('error', 'Service not found');
        header('Location: /provider/services.php');
        exit();
    }
}

// Get all categories
$categories = getAllCategories();

// Get all services for this provider for the list view
$services = [];
if ($action === 'list') {
    $services = getServicesByProvider($providerId);
}
?>

<?php if ($action === 'list'): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Manage Services</h2>
                <a href="/provider/services.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Add New Service
                </a>
            </div>
            
            <?php if (!empty($errors) && isset($errors['database'])): ?>
                <div class="alert alert-danger mt-3">
                    <?php echo $errors['database']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($services)): ?>
        <div class="alert alert-info">
            <p class="mb-0">You haven't added any services yet. <a href="/provider/services.php?action=add">Add your first service</a> to start receiving requests.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($services as $service): ?>
                <div class="col-md-4 mb-4">
                    <div class="card service-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $service['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="badge bg-info"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($service['description'], 0, 150))); ?>...</p>
                            
                            <?php if (!empty($service['price_range'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Price Range: <?php echo htmlspecialchars($service['price_range']); ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($service['availability'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Availability: <?php echo htmlspecialchars($service['availability']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <div class="d-flex justify-content-between">
                                <a href="/provider/services.php?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-service" data-service-id="<?php echo $service['id']; ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <!-- Add/Edit Service Form -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo $action === 'add' ? 'Add New Service' : 'Edit Service'; ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors) && isset($errors['database'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $errors['database']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($serviceId ? '&id=' . $serviceId : '')); ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Service Title *</label>
                            <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : (isset($service['title']) ? htmlspecialchars($service['title']) : ''); ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['title']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) || (isset($service['category_id']) && $service['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['category_id']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : (isset($service['description']) ? htmlspecialchars($service['description']) : ''); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['description']; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Provide a detailed description of your service, including what's included and any special features.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_range" class="form-label">Price Range</label>
                            <input type="text" class="form-control" id="price_range" name="price_range" value="<?php echo isset($_POST['price_range']) ? htmlspecialchars($_POST['price_range']) : (isset($service['price_range']) ? htmlspecialchars($service['price_range']) : ''); ?>" placeholder="e.g., $50-$100, Starting from $75, etc.">
                            <div class="form-text">Provide a general price range or starting price for your service.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="availability" class="form-label">Availability</label>
                            <input type="text" class="form-control" id="availability" name="availability" value="<?php echo isset($_POST['availability']) ? htmlspecialchars($_POST['availability']) : (isset($service['availability']) ? htmlspecialchars($service['availability']) : ''); ?>" placeholder="e.g., Mon-Fri 9am-5pm, Weekends only, etc.">
                            <div class="form-text">Optional: Specify when this service is available.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo (isset($_POST['is_active']) || (isset($service['is_active']) && $service['is_active']) || $action === 'add') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active (visible to customers)</label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/provider/services.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action === 'add' ? 'Add Service' : 'Save Changes'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
