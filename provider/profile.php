<?php
require_once '../includes/header.php';

// Require provider login
requireProvider();

// Get user data
$user = getCurrentUser();
$profile = getProviderProfile($user['id']);
$isNewProfile = $profile === false;

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $businessName = isset($_POST['business_name']) ? sanitizeInput($_POST['business_name']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitizeInput($_POST['city']) : '';
    $state = isset($_POST['state']) ? sanitizeInput($_POST['state']) : '';
    $zip = isset($_POST['zip']) ? sanitizeInput($_POST['zip']) : '';
    $website = isset($_POST['website']) ? sanitizeInput($_POST['website']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : $user['email'];
    
    // Basic validation
    if (empty($businessName)) {
        $errors['business_name'] = 'Business name is required';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Business description is required';
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Please enter a valid website URL';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // If no validation errors, update profile
    if (empty($errors)) {
        try {
            // Update user email and phone
            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$email, $phone, $user['id']]);
            
            if ($isNewProfile) {
                // Create new provider profile
                $stmt = $pdo->prepare("
                    INSERT INTO provider_profiles 
                    (user_id, business_name, description, address, city, state, zip, website)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'], $businessName, $description, $address, $city, $state, $zip, $website
                ]);
                
                setFlashMessage('success', 'Business profile created successfully!');
                header('Location: /provider/dashboard.php');
                exit();
            } else {
                // Update existing provider profile
                $stmt = $pdo->prepare("
                    UPDATE provider_profiles 
                    SET business_name = ?, description = ?, address = ?, city = ?, state = ?, zip = ?, website = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $businessName, $description, $address, $city, $state, $zip, $website, $user['id']
                ]);
                
                setFlashMessage('success', 'Business profile updated successfully!');
                header('Location: /provider/profile.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// If not a form submission, fill form with existing data if available
if (!$isNewProfile && empty($_POST)) {
    $businessName = $profile['business_name'];
    $description = $profile['description'];
    $address = $profile['address'];
    $city = $profile['city'];
    $state = $profile['state'];
    $zip = $profile['zip'];
    $website = $profile['website'];
    $phone = $user['phone'];
    $email = $user['email'];
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo $isNewProfile ? 'Create Business Profile' : 'Edit Business Profile'; ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors) && isset($errors['database'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $errors['database']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="business_name" class="form-label">Business Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['business_name']) ? 'is-invalid' : ''; ?>" id="business_name" name="business_name" value="<?php echo isset($businessName) ? htmlspecialchars($businessName) : ''; ?>" required>
                            <?php if (isset($errors['business_name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['business_name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Business Description *</label>
                        <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="4" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['description']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Describe your business, services offered, and expertise.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['phone']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo isset($state) ? htmlspecialchars($state) : ''; ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="zip" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="zip" name="zip" value="<?php echo isset($zip) ? htmlspecialchars($zip) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control <?php echo isset($errors['website']) ? 'is-invalid' : ''; ?>" id="website" name="website" value="<?php echo isset($website) ? htmlspecialchars($website) : ''; ?>" placeholder="https://">
                        <?php if (isset($errors['website'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['website']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if (!$isNewProfile): ?>
                            <a href="/provider/dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $isNewProfile ? 'Create Profile' : 'Save Changes'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
