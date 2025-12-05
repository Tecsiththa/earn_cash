<?php
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$user_id = $_SESSION['user_id'];
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

// Validate
if (empty($current_password) || empty($new_password)) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be at least 6 characters'
    ]);
    exit;
}

$conn = getDBConnection();

// Get current password hash
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    $conn->close();
    exit;
}

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Current password is incorrect'
    ]);
    $conn->close();
    exit;
}

// Hash new password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $new_password_hash, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to change password'
    ]);
}

$stmt->close();
$conn->close();
?>