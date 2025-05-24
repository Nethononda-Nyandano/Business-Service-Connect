<?php
require_once '../includes/header.php';

// Get service ID
$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$serviceId) {
    setFlashMessage('error', 'Service ID is required');
    header('Location: search.php');
    exit();
}

// Get service details
$service = getServiceById($serviceId);

if (!$service) {
    setFlashMessage('error', 'Service not found');
    header('Location: search.php');
    exit();
}

// Get provider details
$providerId = $service['provider_id'];
$stmt = $pdo->prepare("
    SELECT pp.*, u.email, u.phone
    FROM provider_profiles pp
    JOIN users u ON pp.user_id = u.id
    WHERE pp.id = ?
");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
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
                <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($service['category_name']); ?></span>

                <h5>Description</h5>
                <div class="mb-4">
                    <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                </div>

                <?php if (!empty($service['price_range'])): ?>
                    <h5>Price Range</h5>
                    <div class="mb-4">
                        <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($service['price_range']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($service['availability'])): ?>
                    <h5>Availability</h5>
                    <div class="mb-4">
                        <i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($service['availability']); ?>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2 d-md-flex mt-4">
                    <?php if (isLoggedIn() && isCustomer()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestServiceModal">
                            <i class="fas fa-paper-plane me-2"></i>Request This Service
                        </button>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="http://localhost/BusinessServiceTracker/auth/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Request
                        </a>
                        <a href="http://localhost/BusinessServiceTracker/auth/register.php?type=customer" class="btn btn-outline-secondary">
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
                <h5 class="mb-0">Service Provider</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="mb-3">
                        <i class="fas fa-building fa-3x text-primary"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($provider['business_name']); ?></h5>
                    <?php if ($provider['is_verified']): ?>
                        <span class="badge bg-success">Verified</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <h6>About</h6>
                    <p class="small"><?php echo nl2br(htmlspecialchars(substr($provider['description'], 0, 200))); ?>...</p>
                </div>

                <div class="mb-3">
                    <h6>Contact Information</h6>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($provider['email']); ?></p>
                    <?php if (!empty($provider['phone'])): ?>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($provider['phone']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($provider['city']) || !empty($provider['state'])): ?>
                    <div class="mb-3">
                        <h6>Location</h6>
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php
                            $location = [];
                            if (!empty($provider['city'])) $location[] = htmlspecialchars($provider['city']);
                            if (!empty($provider['state'])) $location[] = htmlspecialchars($provider['state']);
                            echo implode(', ', $location);
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($provider['website'])): ?>
                    <div class="mb-3">
                        <h6>Website</h6>
                        <p class="mb-1">
                            <i class="fas fa-globe me-2"></i>
                            <a href="<?php echo htmlspecialchars($provider['website']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $provider['website'])); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
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

<?php
require_once '../includes/footer.php';
?>