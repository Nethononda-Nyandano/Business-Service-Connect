<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get search parameters
$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';

// Build SQL query
$sql = "
    SELECT s.*, c.name as category_name, pp.business_name, pp.city, pp.state, pp.zip
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN provider_profiles pp ON s.provider_id = pp.id
    WHERE s.is_active = 1
";
$params = [];

if (!empty($query)) {
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ? OR pp.business_name LIKE ?)";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($categoryId > 0) {
    $sql .= " AND s.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($location)) {
    $sql .= " AND (pp.city LIKE ? OR pp.state LIKE ? OR pp.zip LIKE ?)";
    $locationTerm = "%$location%";
    $params[] = $locationTerm;
    $params[] = $locationTerm;
    $params[] = $locationTerm;
}

$sql .= " ORDER BY s.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();
    
    // Return search results as JSON
    echo json_encode($services);
} catch (PDOException $e) {
    // Return error
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
