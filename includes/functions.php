<?php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

// Function to check if user is provider
function isProvider() {
    return isLoggedIn() && $_SESSION['user_type'] === 'provider';
}

// Function to check if user is customer
function isCustomer() {
    return isLoggedIn() && $_SESSION['user_type'] === 'customer';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to access this page');
        header('Location: /auth/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'You do not have permission to access this page');
        header('Location: /index.php');
        exit();
    }
}

// Redirect if not provider
function requireProvider() {
    requireLogin();
    if (!isProvider()) {
        setFlashMessage('error', 'You do not have permission to access this page');
        header('Location: /index.php');
        exit();
    }
}

// Redirect if not customer
function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        setFlashMessage('error', 'You do not have permission to access this page');
        header('Location: /index.php');
        exit();
    }
}

// Function to sanitize user input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to set flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to display flash messages
function displayFlashMessages() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        
        $alertClass = 'alert-info';
        if ($type === 'success') {
            $alertClass = 'alert-success';
        } elseif ($type === 'error') {
            $alertClass = 'alert-danger';
        } elseif ($type === 'warning') {
            $alertClass = 'alert-warning';
        }
        
        echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>";
        echo $message;
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
        
        unset($_SESSION['flash']);
    }
}

// Function to get current user data
function getCurrentUser() {
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// Function to get provider profile
function getProviderProfile($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM provider_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Function to check if provider has completed profile
function hasProviderProfile($userId) {
    $profile = getProviderProfile($userId);
    return $profile !== false;
}

// Function to get all service categories
function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Function to get category by ID
function getCategoryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to get services by provider
function getServicesByProvider($providerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as category_name 
        FROM services s
        JOIN categories c ON s.category_id = c.id
        WHERE s.provider_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$providerId]);
    return $stmt->fetchAll();
}

// Function to get service by ID
function getServiceById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as category_name, pp.business_name, u.email, u.phone
        FROM services s
        JOIN categories c ON s.category_id = c.id
        JOIN provider_profiles pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to get requests for a provider
function getRequestsForProvider($providerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT sr.*, s.title as service_title, u.username as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM service_requests sr
        JOIN services s ON sr.service_id = s.id
        JOIN users u ON sr.customer_id = u.id
        WHERE s.provider_id = ?
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$providerId]);
    return $stmt->fetchAll();
}

// Function to get requests by customer
function getRequestsByCustomer($customerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT sr.*, s.title as service_title, pp.business_name, u.email as provider_email, u.phone as provider_phone
        FROM service_requests sr
        JOIN services s ON sr.service_id = s.id
        JOIN provider_profiles pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE sr.customer_id = ?
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

// Function to create notification
function createNotification($userId, $type, $message, $relatedId = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, related_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $type, $message, $relatedId]);
}

// Function to get unread notifications count
function getUnreadNotificationsCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get user notifications
function getUserNotifications($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT *
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Function to mark notification as read
function markNotificationAsRead($notificationId) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
    ");
    $stmt->execute([$notificationId]);
}

// Function to get all users (for admin)
function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT * FROM users
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll();
}

// Function to send email notification
function sendEmailNotification($to, $subject, $message) {
    // In a real implementation, you would use PHPMailer here
    // For this task, we'll just log the email
    error_log("Email to: $to, Subject: $subject, Message: $message");
    return true;
}

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y');
}

// Function to format datetime
function formatDateTime($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y, g:i a');
}

// Function to get request status badge
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'accepted':
            return '<span class="badge bg-success">Accepted</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Rejected</span>';
        case 'completed':
            return '<span class="badge bg-info">Completed</span>';
        case 'cancelled':
            return '<span class="badge bg-secondary">Cancelled</span>';
        default:
            return '<span class="badge bg-light">Unknown</span>';
    }
}
?>
