<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getRequestData();
        break;
    case 'POST':
        handleRequestActions();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

// Function to get request data
function getRequestData() {
    global $pdo;
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    try {
        if ($requestId) {
            // Get single request details
            if (isAdmin()) {
                // Admin can view any request
                $stmt = $pdo->prepare("
                    SELECT sr.*, s.title as service_title, s.description as service_description, 
                           u.username as customer_name, u.email as customer_email, u.phone as customer_phone,
                           pp.business_name, pu.email as provider_email, pu.phone as provider_phone
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN users u ON sr.customer_id = u.id
                    JOIN provider_profiles pp ON s.provider_id = pp.id
                    JOIN users pu ON pp.user_id = pu.id
                    WHERE sr.id = ?
                ");
                $stmt->execute([$requestId]);
            } else if (isProvider()) {
                // Provider can only view requests for their services
                $user = getCurrentUser();
                $providerProfile = getProviderProfile($user['id']);
                
                if (!$providerProfile) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You do not have a provider profile']);
                    exit();
                }
                
                $stmt = $pdo->prepare("
                    SELECT sr.*, s.title as service_title, s.description as service_description, 
                           u.username as customer_name, u.email as customer_email, u.phone as customer_phone
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN users u ON sr.customer_id = u.id
                    WHERE sr.id = ? AND s.provider_id = ?
                ");
                $stmt->execute([$requestId, $providerProfile['id']]);
            } else if (isCustomer()) {
                // Customer can only view their own requests
                $stmt = $pdo->prepare("
                    SELECT sr.*, s.title as service_title, s.description as service_description, 
                           pp.business_name, u.email as provider_email, u.phone as provider_phone
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN provider_profiles pp ON s.provider_id = pp.id
                    JOIN users u ON pp.user_id = u.id
                    WHERE sr.id = ? AND sr.customer_id = ?
                ");
                $stmt->execute([$requestId, $_SESSION['user_id']]);
            }
            
            $request = $stmt->fetch();
            
            if ($request) {
                echo json_encode(['success' => true, 'data' => $request]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Request not found or you do not have permission to view it']);
            }
        } else {
            // Get list of requests
            $statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
            
            if (isAdmin()) {
                // Admin can view all requests
                $sql = "
                    SELECT sr.*, s.title as service_title, u.username as customer_name, pp.business_name
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN users u ON sr.customer_id = u.id
                    JOIN provider_profiles pp ON s.provider_id = pp.id
                ";
                $params = [];
                
                if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])) {
                    $sql .= " WHERE sr.status = ?";
                    $params[] = $statusFilter;
                }
                
                $sql .= " ORDER BY sr.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else if (isProvider()) {
                // Provider can only view requests for their services
                $user = getCurrentUser();
                $providerProfile = getProviderProfile($user['id']);
                
                if (!$providerProfile) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You do not have a provider profile']);
                    exit();
                }
                
                $sql = "
                    SELECT sr.*, s.title as service_title, u.username as customer_name, u.email as customer_email
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN users u ON sr.customer_id = u.id
                    WHERE s.provider_id = ?
                ";
                $params = [$providerProfile['id']];
                
                if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])) {
                    $sql .= " AND sr.status = ?";
                    $params[] = $statusFilter;
                }
                
                $sql .= " ORDER BY sr.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else if (isCustomer()) {
                // Customer can only view their own requests
                $sql = "
                    SELECT sr.*, s.title as service_title, pp.business_name
                    FROM service_requests sr
                    JOIN services s ON sr.service_id = s.id
                    JOIN provider_profiles pp ON s.provider_id = pp.id
                    WHERE sr.customer_id = ?
                ";
                $params = [$_SESSION['user_id']];
                
                if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])) {
                    $sql .= " AND sr.status = ?";
                    $params[] = $statusFilter;
                }
                
                $sql .= " ORDER BY sr.created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $requests = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $requests]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to handle request actions (create, update status)
function handleRequestActions() {
    global $pdo;
    
    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    
    if ($action === 'update_status') {
        updateRequestStatus();
    } else {
        createServiceRequest();
    }
}

// Function to create a new service request
function createServiceRequest() {
    global $pdo;
    
    // Check if user is logged in as a customer
    if (!isLoggedIn() || !isCustomer()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in as a customer to submit requests']);
        exit();
    }
    
    // Get form data
    $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $requestedDate = isset($_POST['requested_date']) ? sanitizeInput($_POST['requested_date']) : null;
    $requestedTime = isset($_POST['requested_time']) ? sanitizeInput($_POST['requested_time']) : null;
    
    // Validate data
    $errors = [];
    
    if (!$serviceId) {
        $errors['service_id'] = 'Service ID is required';
    }
    
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit();
    }
    
    try {
        // Verify that the service exists
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        
        if (!$service) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Service not found or is inactive']);
            exit();
        }
        
        // Create the service request
        $stmt = $pdo->prepare("
            INSERT INTO service_requests 
            (service_id, customer_id, title, description, status, requested_date, requested_time)
            VALUES (?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([$serviceId, $_SESSION['user_id'], $title, $description, $requestedDate, $requestedTime]);
        
        $requestId = $pdo->lastInsertId();
        
        // Get provider info to send notification
        $stmt = $pdo->prepare("
            SELECT pp.user_id as provider_user_id, pp.business_name, s.title as service_title
            FROM services s
            JOIN provider_profiles pp ON s.provider_id = pp.id
            WHERE s.id = ?
        ");
        $stmt->execute([$serviceId]);
        $providerInfo = $stmt->fetch();
        
        // Create notification for provider
        createNotification(
            $providerInfo['provider_user_id'],
            'new_request',
            'New service request: "' . $title . '" for your service "' . $providerInfo['service_title'] . '"',
            $requestId
        );
        
        // Send email notification (in a real implementation)
        // sendEmailNotification($providerEmail, 'New Service Request', 'You have received a new service request...');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Service request submitted successfully',
            'request_id' => $requestId
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to update request status
function updateRequestStatus() {
    global $pdo;
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Get data
    $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';
    
    // Validate data
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit();
    }
    
    if (!in_array($status, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    try {
        if (isAdmin()) {
            // Admin can update any request
            $stmt = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $requestId]);
        } else if (isProvider()) {
            // Providers can only update requests for their services
            // and can't set status to 'cancelled' (only customers can cancel)
            if ($status === 'cancelled') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Providers cannot cancel requests']);
                exit();
            }
            
            $user = getCurrentUser();
            $providerProfile = getProviderProfile($user['id']);
            
            if (!$providerProfile) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have a provider profile']);
                exit();
            }
            
            // Get request details to verify ownership
            $stmt = $pdo->prepare("
                SELECT sr.*, s.provider_id, s.title as service_title, u.id as customer_id, u.username as customer_name
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN users u ON sr.customer_id = u.id
                WHERE sr.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            
            if (!$request || $request['provider_id'] != $providerProfile['id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to update this request']);
                exit();
            }
            
            // Update the request status
            $stmt = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $requestId]);
            
            // Create notification for customer
            $statusMessage = '';
            switch ($status) {
                case 'accepted':
                    $statusMessage = 'has been accepted';
                    break;
                case 'rejected':
                    $statusMessage = 'has been rejected';
                    break;
                case 'completed':
                    $statusMessage = 'has been marked as completed';
                    break;
                default:
                    $statusMessage = 'has been updated to ' . $status;
            }
            
            createNotification(
                $request['customer_id'],
                'request_update',
                'Your request "' . $request['title'] . '" for service "' . $request['service_title'] . '" ' . $statusMessage,
                $requestId
            );
            
        } else if (isCustomer()) {
            // Customers can only cancel their own requests and only if they're pending
            if ($status !== 'cancelled') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Customers can only cancel requests']);
                exit();
            }
            
            // Get request details to verify ownership and status
            $stmt = $pdo->prepare("
                SELECT sr.*, s.title as service_title, pp.user_id as provider_user_id
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.id
                JOIN provider_profiles pp ON s.provider_id = pp.id
                WHERE sr.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            
            if (!$request || $request['customer_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to cancel this request']);
                exit();
            }
            
            if ($request['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
                exit();
            }
            
            // Update the request status
            $stmt = $pdo->prepare("UPDATE service_requests SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$requestId]);
            
            // Create notification for provider
            createNotification(
                $request['provider_user_id'],
                'request_cancelled',
                'Request "' . $request['title'] . '" for service "' . $request['service_title'] . '" has been cancelled by the customer',
                $requestId
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Request status updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
