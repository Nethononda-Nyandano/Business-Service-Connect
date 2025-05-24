<?php
require_once '../includes/header.php';

// Require customer login
requireCustomer();

// Get customer data
$customer = getCurrentUser();

// Get customer statistics
$customerId = $customer['id'];

// Get total requests
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE customer_id = ?");
$stmt->execute([$customerId]);
$totalRequests = $stmt->fetch()['count'];

// Get pending requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests 
    WHERE customer_id = ? AND status = 'pending'
");
$stmt->execute([$customerId]);
$pendingRequests = $stmt->fetch()['count'];

// Get accepted requests
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM service_requests 
    WHERE customer_id = ? AND status = 'accepted'
");
$stmt->execute([$customerId]);
$acceptedRequests = $stmt->fetch()['count'];

// Get recent service requests
$stmt = $pdo->prepare("
    SELECT sr.*, s.title as service_title, pp.business_name, u.email as provider_email
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    JOIN users u ON pp.user_id = u.id
    WHERE sr.customer_id = ?
    ORDER BY sr.created_at DESC
    LIMIT 5
");
$stmt->execute([$customerId]);
$recentRequests = $stmt->fetchAll();

// Get recent services
$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, pp.business_name  
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    WHERE s.is_active = 1
    ORDER BY s.created_at DESC
    LIMIT 4
");
$recentServices = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Customer Dashboard</h2>
            <a href="search.php" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Find Services
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card stat-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Requests</h6>
                        <h3><?php echo $totalRequests; ?></h3>
                    </div>
                    <div class="fs-1 text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
                <a href="requests.php" class="text-decoration-none">View All Requests</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
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
                <a href="requests.php?status=pending" class="text-decoration-none">View Pending</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card stat-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Active Services</h6>
                        <h3><?php echo $acceptedRequests; ?></h3>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <a href="requests.php?status=accepted" class="text-decoration-none">View Active</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Service Requests</h5>
                <a href="requests.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentRequests)): ?>
                    <p class="text-center py-3 text-muted">You haven't submitted any service requests yet.</p>
                    <div class="text-center">
                        <a href="search.php" class="btn btn-primary">Find Services</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service</th>
                                    <th>Provider</th>
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
                                        <td><?php echo htmlspecialchars($request['business_name']); ?></td>
                                        <td><?php echo getStatusBadge($request['status']); ?></td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                        <td>
                                            <a href="requests.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($customer['username']); ?></h5>
                </div>

                <div class="mb-3">
                    <h6 class="text-muted">Contact Information</h6>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($customer['email']); ?></p>
                    <?php if (!empty($customer['phone'])): ?>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($customer['phone']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="search.php" class="list-group-ite list-group-item-action">
                        <i class="fas fa-search me-2"></i>Find Services
                    </a>
                    <a href="requests.php?status=pending" class="list-group-item list-group-item-action">
                        <i class="fas fa-clock me-2"></i>Pending Requests
                        <?php if ($pendingRequests > 0): ?>
                            <span class="badge bg-warning rounded-pill"><?php echo $pendingRequests; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="requests.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-list me-2"></i>My Service Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Services</h5>
                <a href="search.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($recentServices)): ?>
                        <div class="col-12 text-center py-3">
                            <p class="text-muted">No services available yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentServices as $service): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 service-card border-primary">
                                    <div class="card-body">
                                        <h6 class="card-title p-2 bg-primary"><?php echo htmlspecialchars($service['title']); ?></h6>

                                        <p class="card-subtitle mb-2 text-muted small">
                                            <?php echo htmlspecialchars($service['business_name']); ?>
                                        </p>
                                        <img src="<?php echo htmlspecialchars('../' . $service['image_path']) ?>"
                                            class="img-fluid rounded shadow border"
                                            style="max-width: 100%; max-height: 180px; object-fit: cover;" />
                                        <div class="mb-2">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($service['category_name']); ?></span>
                                        </div>
                                        <p class="card-text small"><?php echo htmlspecialchars(substr($service['description'], 0, 80)); ?>...</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <a href="search.php?service_id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>