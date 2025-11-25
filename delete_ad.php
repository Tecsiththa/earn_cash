<?php
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$ad_id = isset($data['ad_id']) ? intval($data['ad_id']) : 0;

if ($ad_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid advertisement ID'
    ]);
    exit;
}

$conn = getDBConnection();

// Start transaction
$conn->begin_transaction();

try {
    // First, delete all ad views for this advertisement
    $stmt = $conn->prepare("DELETE FROM ad_views WHERE ad_id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $stmt->close();
    
    // Then, delete the advertisement
    $stmt = $conn->prepare("DELETE FROM advertisements WHERE id = ?");
    $stmt->bind_param("i", $ad_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Advertisement deleted successfully!'
        ]);
    } else {
        throw new Exception('Failed to delete advertisement');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete advertisement: ' . $e->getMessage()
    ]);
}

$conn->close();
?>