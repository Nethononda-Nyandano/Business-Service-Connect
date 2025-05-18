<?php
require_once '../includes/header.php';

// Require provider login
requireProvider();

// Get provider data
$provider = getCurrentUser();
$providerProfile = getProviderProfile($provider['id']);

// If provider hasn't completed profile, redirect to profile page
if (!$providerProfile) {
    setFlashMessage('info', 'Please complete your business profile to continue');
    header('Location: /provider/profile.php');
    exit();
}

// Get provider statistics
$providerId = $providerProfile['id'];

// Get total services
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM services WHERE provider_id = ?");
$stmt->execute([$providerId]);
$totalServices = $stmt->fetch()['count'];

// Get total service requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE s.provider_id = ?
");
$stmt->execute([$providerId]);
$totalRequests = $stmt->fetch()['count'];

// Get pending service requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE s.provider_id = ? AND sr.status = 'pending'
");
$stmt->execute([$providerId]);
$pendingRequests = $stmt->fetch()['count'];

// Get recent service requests
$stmt = $pdo->prepare("
    SELECT sr.*, s.title as service_title, u.username as customer_name, u.email as customer_email
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN users u ON sr.customer_id = u.id
    WHERE s.provider_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$stmt->execute([$providerId]);
$recentRequests = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Provider Dashboard</h2>
            <a href="/provider/services.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Add New Service
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card stat-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Services</h6>
                        <h3><?php echo $totalServices; ?></h3>
                    </div>
                    <div class="fs-1 text-primary">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <a href="/provider/services.php" class="text-decoration-none">View All Services</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Requests</h6>
                        <h3><?php echo $totalRequests; ?></h3>
                    </div>
                    <div class="fs-1 text-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <a href="/provider/requests.php" class="text-decoration-none">View All Requests</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Pending Requests</h6>
                        <h3><?php echo $pendingRequests; ?></h3>
                    </div>
                    <div class="fs-1 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <a href="/provider/requests.php?status=pending" class="text-decoration-none">View Pending</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Profile Views</h6>
                        <h3>-</h3>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <a href="/provider/profile.php" class="text-decoration-none">View Profile</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Service Requests</h5>
                <a href="/provider/requests.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentRequests)): ?>
                    <p class="text-center py-3 text-muted">No service requests yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['service_title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['customer_name']); ?></td>
                                        <td><?php echo getStatusBadge($request['status']); ?></td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                        <td>
                                            <a href="/provider/requests.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Business Profile</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="mb-3">
                        <i class="fas fa-building fa-3x text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($providerProfile['business_name']); ?></h5>
                    <?php if ($providerProfile['is_verified']): ?>
                        <span class="badge bg-success">Verified</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Unverified</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted">Contact Information</h6>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($provider['email']); ?></p>
                    <?php if (!empty($provider['phone'])): ?>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($provider['phone']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($providerProfile['address'])): ?>
                    <div class="mb-3">
                        <h6 class="text-muted">Address</h6>
                        <p class="mb-1"><?php echo htmlspecialchars($providerProfile['address']); ?></p>
                        <p class="mb-1">
                            <?php 
                            $location = [];
                            if (!empty($providerProfile['city'])) $location[] = htmlspecialchars($providerProfile['city']);
                            if (!empty($providerProfile['state'])) $location[] = htmlspecialchars($providerProfile['state']);
                            if (!empty($providerProfile['zip'])) $location[] = htmlspecialchars($providerProfile['zip']);
                            echo implode(', ', $location);
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <a href="/provider/profile.php" class="btn btn-outline-primary">Edit Profile</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="/provider/services.php?action=add" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i>Add New Service
                    </a>
                    <a href="/provider/requests.php?status=pending" class="list-group-item list-group-item-action">
                        <i class="fas fa-clock me-2"></i>Pending Requests
                        <?php if ($pendingRequests > 0): ?>
                            <span class="badge bg-warning rounded-pill"><?php echo $pendingRequests; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/provider/services.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-briefcase me-2"></i>Manage Services
                    </a>
                    <a href="/customer/search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i>Browse All Services
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
