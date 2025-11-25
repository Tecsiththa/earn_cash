<?php
// ============================================
// get_admin_stats.php
// AJAX endpoint to refresh admin dashboard stats
// ============================================
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

$conn = getDBConnection();

try {
    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get total ads
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM advertisements");
    $stmt->execute();
    $total_ads = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get total views
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ad_views");
    $stmt->execute();
    $total_views = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get pending withdrawals
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawals WHERE status = 'pending'");
    $stmt->execute();
    $pending_withdrawals = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'total_users' => $total_users,
        'total_ads' => $total_ads,
        'total_views' => $total_views,
        'pending_withdrawals' => $pending_withdrawals
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats'
    ]);
}

$conn->close();
?>