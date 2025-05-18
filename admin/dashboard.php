<?php
require_once '../includes/header.php';

// Require admin login
requireAdmin();

// Get admin data
$admin = getCurrentUser();

// Get system statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'");
$totalCustomers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'provider'");
$totalProviders = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
$totalServices = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM service_requests");
$totalRequests = $stmt->fetch()['count'];

// Get recent users
$stmt = $pdo->query("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$recentUsers = $stmt->fetchAll();

// Get recent service requests
$stmt = $pdo->query("
    SELECT sr.*, s.title as service_title, u.username as customer_name, pp.business_name
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN users u ON sr.customer_id = u.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$recentRequests = $stmt->fetchAll();

// Get service categories
$categories = getAllCategories();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Admin Dashboard</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card stat-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Customers</h6>
                        <h3><?php echo $totalCustomers; ?></h3>
                    </div>
                    <div class="fs-1 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <a href="/admin/users.php?type=customer" class="text-decoration-none">View Customers</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Service Providers</h6>
                        <h3><?php echo $totalProviders; ?></h3>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
                <a href="/admin/users.php?type=provider" class="text-decoration-none">View Providers</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Services</h6>
                        <h3><?php echo $totalServices; ?></h3>
                    </div>
                    <div class="fs-1 text-info">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <a href="/admin/services.php" class="text-decoration-none">View Services</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Service Requests</h6>
                        <h3><?php echo $totalRequests; ?></h3>
                    </div>
                    <div class="fs-1 text-warning">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <a href="#" class="text-decoration-none">View Details</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Service Requests</h5>
                <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['service_title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['business_name']); ?></td>
                                    <td><?php echo getStatusBadge($request['status']); ?></td>
                                    <td><?php echo formatDate($request['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Categories Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary p-2 text-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-folder text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-3">
                    <a href="/admin/categories.php" class="btn btn-outline-primary">Manage Categories</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Users</h5>
                <a href="/admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentUsers as $user): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'provider' ? 'success' : 'primary'); ?>">
                                        <?php echo ucfirst($user['user_type']); ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Admin Actions</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="/admin/users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="/admin/services.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-briefcase me-2"></i>Manage Services
                    </a>
                    <a href="/admin/categories.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-folder me-2"></i>Manage Categories
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Info</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PHP Version
                        <span class="badge bg-primary"><?php echo phpversion(); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        MySQL Version
                        <span class="badge bg-primary"><?php echo $pdo->query('select version()')->fetchColumn(); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Server Time
                        <span><?php echo date('Y-m-d H:i:s'); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
