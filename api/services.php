<?php
require_once '../config/database.php';
require_once '../includes/functions.php';


header('Content-Type: application/json');


if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle actions
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

switch ($action) {
    case 'delete':
        deleteService();
        break;
    case 'toggle_status':
        toggleServiceStatus();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}


function deleteService()
{
    global $pdo;


    $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

    if (!$serviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Service ID is required']);
        exit();
    }

    try {

        if (isAdmin()) {

            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
        } else if (isProvider()) {

            $user = getCurrentUser();
            $providerProfile = getProviderProfile($user['id']);

            if (!$providerProfile) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have a provider profile']);
                exit();
            }


            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND provider_id = ?");
            $stmt->execute([$serviceId, $providerProfile['id']]);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this service']);
            exit();
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Service not found or you do not have permission to delete it']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}


function toggleServiceStatus()
{
    global $pdo;


    $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

    if (!$serviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Service ID is required']);
        exit();
    }

    try {

        if (isAdmin()) {

            $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$serviceId]);
        } else if (isProvider()) {

            $user = getCurrentUser();
            $providerProfile = getProviderProfile($user['id']);

            if (!$providerProfile) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have a provider profile']);
                exit();
            }


            $stmt = $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ? AND provider_id = ?");
            $stmt->execute([$serviceId, $providerProfile['id']]);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update this service']);
            exit();
        }

        if ($stmt->rowCount() > 0) {

            $stmt = $pdo->prepare("SELECT is_active FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Service status updated successfully',
                'is_active' => (bool)$service['is_active']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Service not found or you do not have permission to update it']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
