<?php
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$ad_id = intval($_POST['ad_id'] ?? 0);
$watch_time = intval($_POST['watch_time'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($ad_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ad ID']);
    exit();
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Get ad details
    $stmt = $conn->prepare("SELECT reward, is_active, minimum_watch_time FROM advertisements WHERE id = ?");
    $stmt->bind_param("i", $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Advertisement not found');
    }
    
    $ad = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ad['is_active']) {
        throw new Exception('This advertisement is no longer active');
    }
    
    // Check minimum watch time
    if ($watch_time < $ad['minimum_watch_time']) {
        throw new Exception('You must watch the ad for at least ' . $ad['minimum_watch_time'] . ' seconds');
    }
    
    // Check if user already viewed this ad
    $stmt = $conn->prepare("SELECT id FROM ad_views WHERE user_id = ? AND ad_id = ?");
    $stmt->bind_param("ii", $user_id, $ad_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('You have already viewed this ad');
    }
    $stmt->close();
    
    // Record the view
    $reward = $ad['reward'];
    $stmt = $conn->prepare("INSERT INTO ad_views (user_id, ad_id, reward_earned) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $user_id, $ad_id, $reward);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to record view');
    }
    $stmt->close();
    
    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $reward, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update balance');
    }
    $stmt->close();
    
    // Update daily earnings
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        INSERT INTO daily_earnings (user_id, date, total_earned, ads_viewed) 
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
        total_earned = total_earned + ?,
        ads_viewed = ads_viewed + 1
    ");
    $stmt->bind_param("isdd", $user_id, $today, $reward, $reward);
    $stmt->execute();
    $stmt->close();
    
    // Get updated stats
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $new_balance = $stmt->get_result()->fetch_assoc()['balance'];
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT total_earned, ads_viewed FROM daily_earnings WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $daily_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(reward_earned), 0) as total FROM ad_views WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_rewards = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Update session
    $_SESSION['balance'] = $new_balance;
    
    echo json_encode([
        'success' => true,
        'message' => 'Reward claimed successfully!',
        'new_balance' => $new_balance,
        'reward' => $reward,
        'today_earnings' => $daily_data['total_earned'],
        'ads_today' => $daily_data['ads_viewed'],
        'total_rewards' => $total_rewards
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>