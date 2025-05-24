<?php
require_once '../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: http://localhost/BusinessServiceTracker/admin/dashboard.php');
    } elseif (isProvider()) {
        header('Location: http://localhost/BusinessServiceTracker/provider/dashboard.php');
    } else {
        header('Location: http://localhost/BusinessServiceTracker/customer/dashboard.php');
    }
    exit();
}

$errors = [];
$userType = isset($_GET['type']) && $_GET['type'] === 'provider' ? 'provider' : 'customer';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $userType = isset($_POST['user_type']) ? sanitizeInput($_POST['user_type']) : 'customer';

    // Basic validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Check if username or email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = 'Username already taken';
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already registered';
        }
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }

    // If no validation errors, create user
    if (empty($errors)) {
        try {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, phone, user_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $phone, $userType]);

            $userId = $pdo->lastInsertId();

            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $userType;

            // Create a welcome notification
            createNotification(
                $userId,
                'welcome',
                'Welcome to Business Service Connect! Get started by exploring the platform.',
                null
            );

            // Redirect based on user type
            if ($userType === 'provider') {
                setFlashMessage('success', 'Registration successful! Please complete your business profile.');
                header('Location: http://localhost/BusinessServiceTracker/provider/profile.php');
            } else {
                setFlashMessage('success', 'Registration successful! You can now start using the platform.');
                header('Location: http://localhost/BusinessServiceTracker/customer/dashboard.php');
            }
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Register as a <?php echo ucfirst($userType); ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors) && isset($errors['database'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?type=' . $userType); ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="user_type" value="<?php echo $userType; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['username']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Password must be at least 6 characters</div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                <?php if ($userType === 'customer'): ?>
                    <p class="mb-0 mt-2">Want to offer services? <a href="register.php?type=provider">Register as a Provider</a></p>
                <?php else: ?>
                    <p class="mb-0 mt-2">Looking for services? <a href="register.php?type=customer">Register as a Customer</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Terms of Service Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Acceptance of Terms</h5>
                <p>By accessing and using the Business Service Connect platform, you agree to be bound by these Terms of Service.</p>

                <h5>2. User Accounts</h5>
                <p>You are responsible for maintaining the confidentiality of your account information and password.</p>

                <h5>3. Service Provider Responsibilities</h5>
                <p>Service providers are responsible for the accuracy of their service listings and for fulfilling service requests as agreed upon with customers.</p>

                <h5>4. Customer Responsibilities</h5>
                <p>Customers are responsible for providing accurate information when submitting service requests.</p>

                <h5>5. Prohibited Activities</h5>
                <p>Users may not engage in fraudulent activities, post false information, or use the platform for illegal purposes.</p>

                <h5>6. Termination</h5>
                <p>We reserve the right to terminate or suspend accounts that violate these terms.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Information We Collect</h5>
                <p>We collect personal information such as name, email address, phone number, and business details for service providers.</p>

                <h5>2. How We Use Your Information</h5>
                <p>We use your information to provide and improve our services, process service requests, and communicate with you.</p>

                <h5>3. Information Sharing</h5>
                <p>We share your information with service providers or customers as necessary to facilitate service requests.</p>

                <h5>4. Data Security</h5>
                <p>We implement appropriate security measures to protect your personal information.</p>

                <h5>5. Your Rights</h5>
                <p>You have the right to access, correct, or delete your personal information.</p>

                <h5>6. Changes to This Policy</h5>
                <p>We may update this policy from time to time. We will notify you of any significant changes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>