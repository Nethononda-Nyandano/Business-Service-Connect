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
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Get single request details
$request = null;
if ($requestId) {
    $stmt = $pdo->prepare("
        SELECT sr.*, s.title as service_title, s.description as service_description, 
               u.username as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM service_requests sr
        JOIN services s ON sr.service_id = s.id
        JOIN users u ON sr.customer_id = u.id
        WHERE sr.id = ? AND s.provider_id = ?
    ");
    $stmt->execute([$requestId, $providerId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        setFlashMessage('error', 'Request not found');
        header('Location: /provider/requests.php');
        exit();
    }
}

// Get all requests for this provider with optional status filter
$requests = [];
$sql = "
    SELECT sr.*, s.title as service_title, u.username as customer_name, u.email as customer_email
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN users u ON sr.customer_id = u.id
    WHERE s.provider_id = ?
";

$params = [$providerId];

if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])) {
    $sql .= " AND sr.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY sr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<?php if ($request): ?>
    <!-- Single Request View -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/provider/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/provider/requests.php">Requests</a></li>
                    <li class="breadcrumb-item active">Request #<?php echo $request['id']; ?></li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Request Details</h4>
                    <span class="ms-3"><?php echo getStatusBadge($request['status']); ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5>Request Information</h5>
                            <div class="mb-3">
                                <strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>Requested Date:</strong> <?php echo $request['requested_date'] ? formatDate($request['requested_date']) : 'Not specified'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Requested Time:</strong> <?php echo $request['requested_time'] ? htmlspecialchars($request['requested_time']) : 'Not specified'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Submitted:</strong> <?php echo formatDateTime($request['created_at']); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5>Customer Information</h5>
                            <div class="mb-3">
                                <strong>Name:</strong> <?php echo htmlspecialchars($request['customer_name']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($request['customer_email']); ?>"><?php echo htmlspecialchars($request['customer_email']); ?></a>
                            </div>
                            <?php if (!empty($request['customer_phone'])): ?>
                                <div class="mb-3">
                                    <strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($request['customer_phone']); ?>"><?php echo htmlspecialchars($request['customer_phone']); ?></a>
                                </div>
                            <?php endif; ?>
                            
                            <h5 class="mt-4">Service Information</h5>
                            <div class="mb-3">
                                <strong>Service:</strong> <?php echo htmlspecialchars($request['service_title']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5>Update Status</h5>
                            <p>Current status: <?php echo getStatusBadge($request['status']); ?></p>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success update-request-status" data-request-id="<?php echo $request['id']; ?>" data-status="accepted">
                                        <i class="fas fa-check me-1"></i> Accept
                                    </button>
                                    <button class="btn btn-danger update-request-status" data-request-id="<?php echo $request['id']; ?>" data-status="rejected">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </div>
                            <?php elseif ($request['status'] === 'accepted'): ?>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-info update-request-status" data-request-id="<?php echo $request['id']; ?>" data-status="completed">
                                        <i class="fas fa-check-double me-1"></i> Mark as Completed
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="/provider/requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Request List View -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Service Requests</h2>
                <div>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                            if ($statusFilter) {
                                echo 'Status: ' . ucfirst($statusFilter);
                            } else {
                                echo 'Filter by Status';
                            }
                            ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
                            <li><a class="dropdown-item <?php echo $statusFilter === '' ? 'active' : ''; ?>" href="/provider/requests.php">All Requests</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="/provider/requests.php?status=pending">Pending</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>" href="/provider/requests.php?status=accepted">Accepted</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>" href="/provider/requests.php?status=completed">Completed</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" href="/provider/requests.php?status=rejected">Rejected</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>" href="/provider/requests.php?status=cancelled">Cancelled</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <p class="mb-0">
                <?php if ($statusFilter): ?>
                    No <?php echo $statusFilter; ?> requests found.
                <?php else: ?>
                    You don't have any service requests yet.
                <?php endif; ?>
            </p>
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
                                        <th>Service</th>
                                        <th>Customer</th>
                                        <th>Date Requested</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td>#<?php echo $req['id']; ?></td>
                                            <td><?php echo htmlspecialchars($req['service_title']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($req['customer_name']); ?><br>
                                                <small><a href="mailto:<?php echo htmlspecialchars($req['customer_email']); ?>"><?php echo htmlspecialchars($req['customer_email']); ?></a></small>
                                            </td>
                                            <td>
                                                <?php echo $req['requested_date'] ? formatDate($req['requested_date']) : 'Not specified'; ?><br>
                                                <small><?php echo $req['requested_time'] ? htmlspecialchars($req['requested_time']) : 'Any time'; ?></small>
                                            </td>
                                            <td><?php echo getStatusBadge($req['status']); ?></td>
                                            <td><?php echo formatDate($req['created_at']); ?></td>
                                            <td>
                                                <a href="/provider/requests.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <div class="btn-group btn-group-sm mt-1">
                                                        <button class="btn btn-success update-request-status" data-request-id="<?php echo $req['id']; ?>" data-status="accepted">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger update-request-status" data-request-id="<?php echo $req['id']; ?>" data-status="rejected">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>
