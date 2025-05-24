<?php
require_once 'includes/header.php';


$categories = getAllCategories();


$stmt = $pdo->query("
    SELECT s.*, c.name as category_name, pp.business_name  
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    WHERE s.is_active = 1
    ORDER BY s.created_at DESC
    LIMIT 6
");
$featuredServices = $stmt->fetchAll();
?>

<!-- Hero Section -->
<div class="hero-section py-5 text-center text-white rounded">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-lg-start mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold mb-4">Find the Right Business Services</h1>
                <p class="lead mb-4">Connect with trusted service providers in your area. Submit requests and get the services you need, all in one place.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <?php if (!isLoggedIn()): ?>
                        <a href="http://localhost/BusinessServiceTracker/auth/register.php?type=customer" class="btn btn-primary btn-lg px-4 me-md-2">Find Services</a>
                        <a href="auth/register.php?type=provider" class="btn btn-outline-light btn-lg px-4">Offer Services</a>
                    <?php else: ?>
                        <a href="http://localhost/BusinessServiceTracker/customer/search.php" class="btn btn-primary btn-lg px-4 me-md-2">Find Services</a>
                        <?php if (isCustomer()): ?>
                            <a href="http://localhost/BusinessServiceTracker/customer/requests.php" class="btn btn-outline-light btn-lg px-4">My Requests</a>
                        <?php elseif (isProvider()): ?>
                            <a href="http://localhost/BusinessServiceTracker/provider/services.php" class="btn btn-outline-light btn-lg px-4">Manage Services</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://cdn.pixabay.com/photo/2017/05/01/14/59/call-center-2275745_1280.jpg" alt="Business Services" class="img-fluid rounded hero-image" style="border-radius: 10px;">
            </div>
        </div>
    </div>
</div>


<div class="container my-5">
    <div class="search-form p-4 rounded shadow-sm">
        <h2 class="text-center mb-4">Find Services</h2>
        <form action="http://localhost/BusinessServiceTracker/customer/search.php" method="get">
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="searchQuery" class="form-label">What service do you need?</label>
                    <input type="text" class="form-control form-control-lg" id="searchQuery" name="q" placeholder="Search for services...">
                </div>
                <div class="col-md-4">
                    <label for="searchCategory" class="form-label">Category</label>
                    <select class="form-select form-select-lg" id="searchCategory" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="searchLocation" class="form-label">Location</label>
                    <input type="text" class="form-control form-control-lg" id="searchLocation" name="location" placeholder="City or ZIP">
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


<div class="container my-5">
    <h2 class="text-center mb-4">Browse Service Categories</h2>
    <div class="row g-4">
        <?php

        $categoryIcons = [
            'Home Services' => 'fa-home',
            'Professional Services' => 'fa-briefcase',
            'Health & Wellness' => 'fa-heartbeat',
            'Education & Tutoring' => 'fa-graduation-cap',
            'Technical Services' => 'fa-laptop-code',
            'Beauty & Personal Care' => 'fa-cut',
            'Event Services' => 'fa-calendar-check',
            'Auto Services' => 'fa-car'
        ];

        foreach ($categories as $index => $category):

            if ($index >= 8) break;


            $iconClass = 'fa-star';
            foreach ($categoryIcons as $catName => $icon) {
                if (stripos($category['name'], $catName) !== false) {
                    $iconClass = $icon;
                    break;
                }
            }
        ?>
            <div class="col-md-3 col-sm-6">
                <div class="card text-center h-100 service-card">
                    <div class="card-body">
                        <div class="category-icon">
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="card-text small"><?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>...</p>
                        <a href="http://localhost/BusinessServiceTracker/customer/search.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">Browse Services</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Featured Services Section -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Featured Services</h2>
        <a href="http://localhost/BusinessServiceTracker/customer/search.php" class="btn btn-outline-primary">View All</a>
    </div>

    <div class="row g-4">
        <?php if (empty($featuredServices)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No services available yet. Be the first to offer a service!</p>
                <?php if (!isLoggedIn() || isCustomer()): ?>
                    <a href="http://localhost/BusinessServiceTracker/auth/register.php?type=provider" class="btn btn-primary">Register as Provider</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($featuredServices as $service): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 service-card border-primary">
                        <div class="card-body">
                            <h5 class="card-title p-2 bg-primary"><?php echo htmlspecialchars($service['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo htmlspecialchars($service['business_name']); ?>
                            </h6>
                            <?php if (!empty($service['image_path'])): ?>
                                <div class="mb-3 text-center">
                                    <img src="<?php echo htmlspecialchars($service['image_path']); ?>"
                                        alt="Service Image"
                                        class="img-fluid rounded shadow border"
                                        style="max-width: 100%; max-height: 180px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($service['category_name']); ?></span>
                                <?php if (!empty($service['price_range'])): ?>
                                    <span class="text-muted ms-2"><?php echo htmlspecialchars($service['price_range']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="card-text"><?php echo htmlspecialchars(substr($service['description'], 0, 120)); ?>...</p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="http://localhost/BusinessServiceTracker/customer/search.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- How It Works Section -->
<div class="container my-5">
    <h2 class="text-center mb-5">How It Works</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="text-center">
                <div class="feature-icon   mx-auto mb-3">
                    <i class="fas fa-search bg-primary p-4" style="border-radius: 50%;"></i>
                </div>
                <h3>1. Find Services</h3>
                <p>Search through our directory of local service providers or browse by category to find what you need.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <div class="feature-icon  mx-auto mb-3">
                    <i class="fas fa-paper-plane bg-success  p-4" style="border-radius: 50%;"></i>
                </div>
                <h3>2. Submit Request</h3>
                <p>Send a request directly to the service provider with your specific needs and preferred schedule.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <div class="feature-icon  mx-auto mb-3">
                    <i class="fas fa-handshake bg-info p-4" style="border-radius: 50%;"></i>
                </div>
                <h3>3. Get Service</h3>
                <p>The provider will respond to your request and coordinate with you to deliver the service.</p>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section
<div class="container my-5">
    <h2 class="text-center mb-5">What Our Users Say</h2>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="testimonial-card">
                <div class="d-flex mb-3">
                    <img src=" alt="Customer" class="testimonial-img me-3">
                    <div>
                        <h5 class="mb-0"></h5>
                        <small class="text-muted">Customer</small>
                    </div>
                </div>
                <p class="mb-0">""</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="testimonial-card">
                <div class="d-flex mb-3">
                    <img src=" <div>
                        <h5 class="mb-0">Mark Davis</h5>
                        <small class="text-muted">Service Provider</small>
                    </div>
                </div>
                <p class="mb-0">"As a small business owner, this platform has been a game-changer. I've connected with new clients in my area and grown my customer base significantly in just a few months."</p>
            </div>
        </div>
    </div>
</div>
                                -->

<!-- For Providers Section -->
<div class="container my-5">
    <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
            <img src="https://cdn.pixabay.com/photo/2022/11/16/18/38/mexico-7596566_1280.jpg" alt="For Providers" class="img-fluid rounded" style="border-radius: 10px;">
        </div>
        <div class="col-md-6">
            <h2>Grow Your Business</h2>
            <p class="lead">Join our platform as a service provider and reach more customers in your area.</p>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Create a professional business profile</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> List your services and pricing</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Receive service requests directly from customers</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Manage your schedule and availability</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Build your online reputation</li>
            </ul>
            <?php if (!isLoggedIn() || isCustomer()): ?>
                <a href="auth/register.php?type=provider" class="btn btn-primary btn-lg mt-3">Register as a Provider</a>
            <?php elseif (isProvider()): ?>
                <a href="provider/services.php" class="btn btn-primary btn-lg mt-3">Manage Your Services</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- For Customers Section -->
<div class="container my-5">
    <div class="row align-items-center flex-md-row-reverse">
        <div class="col-md-6 mb-4 mb-md-0">
            <img src="https://cdn.pixabay.com/photo/2018/12/09/12/29/customer-3864809_1280.jpg" alt="For Customers" class="img-fluid rounded">
        </div>
        <div class="col-md-6">
            <h2>Find Quality Services</h2>
            <p class="lead">Discover trusted service providers for all your needs in one convenient platform.</p>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Search for services by category or location</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> View provider profiles and service details</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Submit service requests with your specific needs</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Coordinate directly with service providers</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Track your service requests</li>
            </ul>
            <?php if (!isLoggedIn()): ?>
                <a href="auth/register.php?type=customer" class="btn btn-primary btn-lg mt-3">Register as a Customer</a>
            <?php elseif (isCustomer()): ?>
                <a href="customer/search.php" class="btn btn-primary btn-lg mt-3">Find Services Now</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>