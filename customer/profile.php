<?php
require_once '../includes/header.php';

// Require customer login
requireCustomer();

// Get user data
$user = getCurrentUser();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
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
    
    // Check if username or email already exists (excluding current user)
    try {
        if ($username !== $user['username']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['username'] = 'Username already taken';
            }
        }
        
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'Email already registered';
            }
        }
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
    
    // Password validation (only if current password is provided)
    if (!empty($currentPassword)) {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        } else {
            // Validate new password if current is correct
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'New password must be at least 6 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
        }
    }
    
    // If no validation errors, update profile
    if (empty($errors)) {
        try {
            // Start with basic update
            $sql = "UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?";
            $params = [$username, $email, $phone, $user['id']];
            
            // If password change is requested, add it to the update
            if (!empty($currentPassword) && !empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, phone = ?, password = ? WHERE id = ?";
                $params = [$username, $email, $phone, $hashedPassword, $user['id']];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Update session username if it changed
            if ($username !== $user['username']) {
                $_SESSION['username'] = $username;
            }
            
            setFlashMessage('success', 'Profile updated successfully!');
            header('Location: /customer/profile.php');
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// If not a form submission, fill form with existing data
if (empty($_POST)) {
    $username = $user['username'];
    $email = $user['email'];
    $phone = $user['phone'];
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/customer/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">My Profile</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors) && isset($errors['database'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['username']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                        <div class="form-text">Optional: Add a phone number to receive service updates.</div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Change Password</h5>
                    <p class="text-muted small">Leave these fields blank if you don't want to change your password.</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['current_password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password">
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['new_password']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Password must be at least 6 characters.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['confirm_password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/customer/dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if (!empty($user['phone'])): ?>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['phone']); ?></p>
                <?php endif; ?>
                <p class="text-muted small">Member since <?php echo formatDate($user['created_at']); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="/customer/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="/customer/requests.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-list me-2"></i>My Requests
                    </a>
                    <a href="/customer/search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search me-2"></i>Find Services
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
