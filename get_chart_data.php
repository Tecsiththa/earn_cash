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
?>

<?php
// ============================================
// FILE: get_chart_data.php
// AJAX endpoint to get earnings chart data
// ============================================
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$days = max(1, min($days, 365)); // Limit between 1-365 days

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$chart_data = [];

try {
    // Generate data for the requested number of days
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Format day label based on period
        if ($days <= 7) {
            $day_label = date('D', strtotime($date)); // Mon, Tue, etc.
        } elseif ($days <= 31) {
            $day_label = date('M j', strtotime($date)); // Jan 1, Jan 2, etc.
        } else {
            $day_label = date('M d', strtotime($date)); // Jan 01, Jan 02, etc.
        }
        
        $stmt = $conn->prepare("
            SELECT COALESCE(total_earned, 0) as earned 
            FROM daily_earnings 
            WHERE user_id = ? AND date = ?
        ");
        $stmt->bind_param("is", $user_id, $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $chart_data[] = [
            'day' => $day_label,
            'amount' => $result ? floatval($result['earned']) : 0,
            'date' => $date
        ];
        
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'chart_data' => $chart_data,
        'period' => $days
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading chart data'
    ]);
}

$conn->close();
?>