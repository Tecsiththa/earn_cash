<?php
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$ad_id = isset($data['ad_id']) ? intval($data['ad_id']) : 0;
$is_active = isset($data['is_active']) ? intval($data['is_active']) : 0;

if ($ad_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid advertisement ID'
    ]);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("UPDATE advertisements SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $is_active, $ad_id);

if ($stmt->execute()) {
    $status = $is_active ? 'activated' : 'deactivated';
    echo json_encode([
        'success' => true,
        'message' => "Advertisement $status successfully!"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update advertisement status'
    ]);
}

$stmt->close();
$conn->close();
?>