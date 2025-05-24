<?php
require_once '../includes/header.php';

// Require admin login
requireAdmin();

// Get filter parameters
$userType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list';
$errors = [];

// Handle user deletion
if ($action === 'delete' && $userId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            setFlashMessage('success', 'User deleted successfully!');
        } else {
            setFlashMessage('error', 'Cannot delete this user.');
        }

        header('Location: users.php' . ($userType ? "?type=$userType" : ''));
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Handle verification of providers
if ($action === 'verify' && $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE provider_profiles
            SET is_verified = 1
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            setFlashMessage('success', 'Provider verified successfully!');
        } else {
            setFlashMessage('error', 'Provider not found or already verified.');
        }

        header('Location: users.php?type=provider');
        exit();
    } catch (PDOException $e) {
        $errors['database'] = 'Database error: ' . $e->getMessage();
    }
}

// Get users with optional type filter
$sql = "SELECT u.*, COALESCE(pp.id, 0) as has_profile, pp.is_verified
        FROM users u
        LEFT JOIN provider_profiles pp ON u.id = pp.user_id";
$params = [];

if ($userType && in_array($userType, ['admin', 'provider', 'customer'])) {
    $sql .= " WHERE u.user_type = ?";
    $params[] = $userType;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Manage Users</h2>
            <div>
                <div class="btn-group">
                    <a href="users.php" class="btn btn-outline-primary <?php echo $userType === '' ? 'active' : ''; ?>">All Users</a>
                    <a href="users.php?type=customer" class="btn btn-outline-primary <?php echo $userType === 'customer' ? 'active' : ''; ?>">Customers</a>
                    <a href="users.php?type=provider" class="btn btn-outline-primary <?php echo $userType === 'provider' ? 'active' : ''; ?>">Providers</a>
                    <a href="users.php?type=admin" class="btn btn-outline-primary <?php echo $userType === 'admin' ? 'active' : ''; ?>">Admins</a>
                </div>
            </div>
        </div>

        <?php if (!empty($errors) && isset($errors['database'])): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $errors['database']; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'provider' ? 'success' : 'primary'); ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($user['user_type'] === 'provider'): ?>
                                                <?php if ($user['has_profile']): ?>
                                                    <?php if ($user['is_verified']): ?>
                                                        <span class="badge bg-success">Verified</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Unverified</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Incomplete</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($user['user_type'] === 'provider' && $user['has_profile'] && !$user['is_verified']): ?>
                                                    <a href="users.php?action=verify&id=<?php echo $user['id']; ?>" class="btn btn-success" onclick="return confirm('Are you sure you want to verify this provider?')">
                                                        <i class="fas fa-check"></i> Verify
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($user['user_type'] !== 'admin'): ?>
                                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>