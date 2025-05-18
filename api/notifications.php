<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequests();
        break;
    case 'POST':
        handlePostRequests();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

// Function to handle GET requests
function handleGetRequests() {
    $userId = $_SESSION['user_id'];
    $action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
    
    if ($action === 'count_unread') {
        // Return count of unread notifications
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            echo json_encode(['count' => (int)$result['count']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        // Return list of notifications
        try {
            global $pdo;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $stmt = $pdo->prepare("
                SELECT *
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll();
            
            // Format the notifications
            foreach ($notifications as &$notification) {
                // Format datetime for display
                $createdAt = new DateTime($notification['created_at']);
                $notification['created_at'] = $createdAt->format('M j, g:i a');
                
                // Convert is_read to boolean for JavaScript
                $notification['is_read'] = (bool)$notification['is_read'];
            }
            
            echo json_encode($notifications);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

// Function to handle POST requests
function handlePostRequests() {
    $userId = $_SESSION['user_id'];
    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    if ($action === 'mark_read') {
        // Mark a single notification as read
        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        
        if (!$notificationId) {
            http_response_code(400);
            echo json_encode(['error' => 'Notification ID is required']);
            return;
        }
        
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'mark_all_read') {
        // Mark all notifications as read
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}
?>
