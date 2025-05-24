<?php
require_once '../includes/header.php';


requireCustomer();


$customer = getCurrentUser();
$customerId = $customer['id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';


$request = null;
if ($requestId) {
    $stmt = $pdo->prepare("
        SELECT sr.*, s.title as service_title, s.description as service_description, 
               s.price_range, s.availability,
               pp.business_name, u.email as provider_email, u.phone as provider_phone
        FROM service_requests sr
        JOIN services s ON sr.service_id = s.id
        JOIN provider_profiles pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE sr.id = ? AND sr.customer_id = ?
    ");
    $stmt->execute([$requestId, $customerId]);
    $request = $stmt->fetch();

    if (!$request) {
        setFlashMessage('error', 'Request not found');
        header('Location: /customer/requests.php');
        exit();
    }
}


$requests = [];
$sql = "
    SELECT sr.*, s.title as service_title, pp.business_name
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    WHERE sr.customer_id = ?
";

$params = [$customerId];

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

    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="requests.php">My Requests</a></li>
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
                            <?php if (!empty($request['image_path'])): ?>
                                <div class="mb-3 text-center">
                                    <img src="<?php echo htmlspecialchars('../' . $service['image_path']); ?>"
                                        alt="Service Image"
                                        class="img-fluid rounded shadow border"
                                        style="max-width: 100%; max-height: 180px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
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
                            <h5>Service Information</h5>
                            <div class="mb-3">
                                <strong>Service:</strong> <?php echo htmlspecialchars($request['service_title']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Provider:</strong> <?php echo htmlspecialchars($request['business_name']); ?>
                            </div>
                            <?php if (!empty($request['price_range'])): ?>
                                <div class="mb-3">
                                    <strong>Price Range:</strong> <?php echo htmlspecialchars($request['price_range']); ?>
                                </div>
                            <?php endif; ?>

                            <h5 class="mt-4">Contact Information</h5>
                            <div class="mb-3">
                                <strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($request['provider_email']); ?>"><?php echo htmlspecialchars($request['provider_email']); ?></a>
                            </div>
                            <?php if (!empty($request['provider_phone'])): ?>
                                <div class="mb-3">
                                    <strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($request['provider_phone']); ?>"><?php echo htmlspecialchars($request['provider_phone']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($request['status'] === 'pending'): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h5>Actions</h5>
                                <p>You can cancel this request if you no longer need this service.</p>

                                <button class="btn btn-danger update-request-status" data-request-id="<?php echo $request['id']; ?>" data-status="cancelled">
                                    <i class="fas fa-times-circle me-1"></i> Cancel Request
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to My Requests
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
                <h2>My Service Requests</h2>
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
                            <li><a class="dropdown-item <?php echo $statusFilter === '' ? 'active' : ''; ?>" href="requests.php">All Requests</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="requests.php?status=pending">Pending</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>" href="requests.php?status=accepted">Accepted</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>" href="requests.php?status=completed">Completed</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" href="requests.php?status=rejected">Rejected</a></li>
                            <li><a class="dropdown-item <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>" href="requests.php?status=cancelled">Cancelled</a></li>
                        </ul>
                    </div>
                    <a href="search.php" class="btn btn-primary ms-2">
                        <i class="fas fa-search me-1"></i> Find Services
                    </a>
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
                    You haven't submitted any service requests yet.
                <?php endif; ?>
            </p>
            <div class="mt-3">
                <a href="search.php" class="btn btn-primary">Find Services</a>
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
                                        <th>Service</th>
                                        <th>Provider</th>
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
                                            <td><?php echo htmlspecialchars($req['business_name']); ?></td>
                                            <td>
                                                <?php echo $req['requested_date'] ? formatDate($req['requested_date']) : 'Not specified'; ?><br>
                                                <small><?php echo $req['requested_time'] ? htmlspecialchars($req['requested_time']) : 'Any time'; ?></small>
                                            </td>
                                            <td><?php echo getStatusBadge($req['status']); ?></td>
                                            <td><?php echo formatDate($req['created_at']); ?></td>
                                            <td>
                                                <a href="requests.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>

                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-danger mt-1 update-request-status" data-request-id="<?php echo $req['id']; ?>" data-status="cancelled">
                                                        <i class="fas fa-times-circle"></i> Cancel
                                                    </button>
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