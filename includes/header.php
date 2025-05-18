<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$currentUser = null;
$unreadNotifications = 0;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $unreadNotifications = getUnreadNotificationsCount($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Service Connect</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.replit.com/agent/bootstrap-agent-dark-theme.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-handshake me-2"></i>Business Service Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/customer/search.php">Find Services</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/admin/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="/admin/users.php">Manage Users</a></li>
                                    <li><a class="dropdown-item" href="/admin/services.php">Manage Services</a></li>
                                    <li><a class="dropdown-item" href="/admin/categories.php">Manage Categories</a></li>
                                </ul>
                            </li>
                        <?php elseif (isProvider()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="providerDropdown" role="button" data-bs-toggle="dropdown">
                                    My Business
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/provider/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="/provider/profile.php">Business Profile</a></li>
                                    <li><a class="dropdown-item" href="/provider/services.php">Manage Services</a></li>
                                    <li><a class="dropdown-item" href="/provider/requests.php">Service Requests</a></li>
                                </ul>
                            </li>
                        <?php elseif (isCustomer()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown">
                                    My Account
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/customer/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="/customer/profile.php">My Profile</a></li>
                                    <li><a class="dropdown-item" href="/customer/requests.php">My Requests</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadNotifications > 0): ?>
                                    <span class="badge rounded-pill bg-danger"><?php echo $unreadNotifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notifications-dropdown" style="min-width: 300px;">
                                <h6 class="dropdown-header">Notifications</h6>
                                <div id="notifications-container">
                                    <div class="text-center p-2">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span class="ms-2">Loading notifications...</span>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#" id="mark-all-read">
                                    Mark all as read
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($currentUser['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (isProvider()): ?>
                                    <li><a class="dropdown-item" href="/provider/profile.php">Business Profile</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="/customer/profile.php">My Profile</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php displayFlashMessages(); ?>
