<?php
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

// Get user_id from POST
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

// Prevent admin from deleting themselves
if ($user_id === $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'You cannot delete your own account'
    ]);
    exit;
}

$conn = getDBConnection();

// Check if user exists and is a customer
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    $conn->close();
    exit;
}

// Prevent deleting admin accounts
if ($user['role'] === 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete admin accounts'
    ]);
    $conn->close();
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's ad views
    $stmt = $conn->prepare("DELETE FROM ad_views WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete user's daily earnings
    $stmt = $conn->prepare("DELETE FROM daily_earnings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete user's withdrawals
    $stmt = $conn->prepare("DELETE FROM withdrawals WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Finally, delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "User '{$user['username']}' deleted successfully!"
        ]);
    } else {
        throw new Exception('Failed to delete user');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete user: ' . $e->getMessage()
    ]);
}

$conn->close();
?>