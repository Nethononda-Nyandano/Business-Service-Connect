<?php
require_once '../includes/header.php';

// Get search parameters
$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Get all categories for filter
$categories = getAllCategories();

// If a specific service is requested, get the details
$service = null;
if ($serviceId) {
    $service = getServiceById($serviceId);
    
    if (!$service) {
        setFlashMessage('error', 'Service not found');
        header('Location: /customer/search.php');
        exit();
    }
}

// Get search results if not viewing a specific service
$services = [];
if (!$serviceId) {
    $sql = "
        SELECT s.*, c.name as category_name, pp.business_name, pp.city, pp.state, u.email as provider_email, u.phone as provider_phone  
        FROM services s
        JOIN categories c ON s.category_id = c.id
        JOIN provider_profiles pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE s.is_active = 1
    ";
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (s.title LIKE ? OR s.description LIKE ? OR pp.business_name LIKE ?)";
        $searchTerm = "%$query%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryId > 0) {
        $sql .= " AND s.category_id = ?";
        $params[] = $categoryId;
    }
    
    if (!empty($location)) {
        $sql .= " AND (pp.city LIKE ? OR pp.state LIKE ? OR pp.zip LIKE ?)";
        $locationTerm = "%$location%";
        $params[] = $locationTerm;
        $params[] = $locationTerm;
        $params[] = $locationTerm;
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();
}
?>

<?php if ($service): ?>
    <!-- Single Service View -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/customer/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/customer/search.php">Search</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($service['title']); ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($service['title']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($service['price_range'])): ?>
                        <div class="mb-4">
                            <h5>Price Range</h5>
                            <p><i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($service['price_range']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($service['availability'])): ?>
                        <div class="mb-4">
                            <h5>Availability</h5>
                            <p><i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($service['availability']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex mt-4">
                        <?php if (isLoggedIn() && isCustomer()): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestServiceModal">
                                <i class="fas fa-paper-plane me-2"></i>Request This Service
                            </button>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="/auth/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Request
                            </a>
                            <a href="/auth/register.php?type=customer" class="btn btn-outline-secondary">
                                Register as Customer
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Provider Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="mb-3">
                            <i class="fas fa-building fa-3x text-primary"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($service['business_name']); ?></h5>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Contact Information</h6>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($service['email']); ?></p>
                        <?php if (!empty($service['phone'])): ?>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($service['phone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Request Service Modal -->
    <?php if (isLoggedIn() && isCustomer()): ?>
        <div class="modal fade" id="requestServiceModal" tabindex="-1" aria-labelledby="requestServiceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="requestServiceModalLabel">Request Service: <?php echo htmlspecialchars($service['title']); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="ajaxServiceRequestForm" class="needs-validation" novalidate>
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="request_title" class="form-label">Request Title</label>
                                <input type="text" class="form-control" id="request_title" name="title" required>
                                <div class="invalid-feedback">Please provide a title for your request.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="request_description" class="form-label">Describe What You Need</label>
                                <textarea class="form-control" id="request_description" name="description" rows="4" required></textarea>
                                <div class="invalid-feedback">Please describe what you need from this service.</div>
                                <div class="form-text">
                                    Be as specific as possible about what you need, including any relevant details that will help the service provider understand your requirements.
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="requested_date" class="form-label">Preferred Date (optional)</label>
                                    <input type="date" class="form-control" id="requested_date" name="requested_date">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="requested_time" class="form-label">Preferred Time (optional)</label>
                                    <input type="text" class="form-control" id="requested_time" name="requested_time" placeholder="e.g., Morning, Afternoon, 2-4 PM">
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    By submitting this request, you agree to share your contact information with the service provider.
                                    The provider will review your request and respond directly to you.
                                </small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <!-- Search View -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Find Services</h2>
            <div class="search-form p-4 rounded shadow-sm mb-4">
                <form id="searchForm" action="/customer/search.php" method="get">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="searchInput" class="form-label">What service do you need?</label>
                            <input type="text" class="form-control form-control-lg" id="searchInput" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search for services...">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="categoryFilter" class="form-label">Category</label>
                            <select class="form-select form-select-lg" id="categoryFilter" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($categoryId == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="locationFilter" class="form-label">Location</label>
                            <input type="text" class="form-control form-control-lg" id="locationFilter" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="City or ZIP">
                        </div>
                        
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="/customer/search.php" class="list-group-item list-group-item-action <?php echo (!$categoryId) ? 'active' : ''; ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="/customer/search.php?category=<?php echo $category['id']; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?><?php echo !empty($location) ? '&location=' . urlencode($location) : ''; ?>" class="list-group-item list-group-item-action <?php echo ($categoryId == $category['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="mb-3">
                <h5>
                    <?php
                    $resultsCount = count($services);
                    if (!empty($query) || $categoryId > 0 || !empty($location)) {
                        echo 'Search Results';
                        $filters = [];
                        if (!empty($query)) $filters[] = '"' . htmlspecialchars($query) . '"';
                        if ($categoryId > 0) {
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $categoryId) {
                                    $filters[] = 'Category: ' . htmlspecialchars($cat['name']);
                                    break;
                                }
                            }
                        }
                        if (!empty($location)) $filters[] = 'Location: ' . htmlspecialchars($location);
                        
                        if (!empty($filters)) {
                            echo ' for ' . implode(', ', $filters);
                        }
                    } else {
                        echo 'All Services';
                    }
                    
                    echo ' (' . $resultsCount . ' ' . ($resultsCount == 1 ? 'service' : 'services') . ' found)';
                    ?>
                </h5>
            </div>
            
            <div id="searchResults">
                <?php if (empty($services)): ?>
                    <div class="text-center my-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h3>No services found</h3>
                        <p>Try adjusting your search criteria or browse categories.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($services as $service): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card service-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($service['business_name']); ?></h6>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($service['description'], 0, 150)); ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($service['category_name']); ?></span>
                                            <?php if (!empty($service['city'])): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($service['city']); ?><?php echo !empty($service['state']) ? ', ' . htmlspecialchars($service['state']) : ''; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($service['price_range'])): ?>
                                            <div class="small text-muted mb-2">
                                                <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($service['price_range']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <a href="/customer/search.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
