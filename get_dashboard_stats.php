<?php
// ============================================
// FILE: get_dashboard_stats.php
// AJAX endpoint to refresh dashboard statistics
// ============================================
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

try {
    // Get today's earnings
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT COALESCE(total_earned, 0) as today_earned, COALESCE(ads_viewed, 0) as ads_today 
        FROM daily_earnings 
        WHERE user_id = ? AND date = ?
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $today_data = $stmt->get_result()->fetch_assoc();
    $today_earnings = $today_data ? $today_data['today_earned'] : 0;
    $ads_today = $today_data ? $today_data['ads_today'] : 0;
    $stmt->close();
    
    // Get current balance
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $balance = $stmt->get_result()->fetch_assoc()['balance'];
    $stmt->close();
    
    // Get total earned
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(reward_earned), 0) as total_earned
        FROM ad_views 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_earned = $stmt->get_result()->fetch_assoc()['total_earned'];
    $stmt->close();
    
    $ads_remaining = 10 - $ads_today;
    
    echo json_encode([
        'success' => true,
        'today_earnings' => $today_earnings,
        'balance' => $balance,
        'total_earned' => $total_earned,
        'ads_today' => $ads_today,
        'ads_remaining' => $ads_remaining
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats'
    ]);
}

$conn->close();
