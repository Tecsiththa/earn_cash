<?php
// ============================================
// get_revenue_data.php
// Get revenue chart data for different periods
// ============================================
require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');

$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$days = max(1, min($days, 365)); // Limit between 1-365 days

$conn = getDBConnection();
$revenue_data = [];

try {
    // Generate data for requested period
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Format day label based on period
        if ($days <= 7) {
            $day_label = date('D', strtotime($date)); // Mon, Tue, Wed
        } elseif ($days <= 31) {
            $day_label = date('M j', strtotime($date)); // Jan 1, Jan 2
        } else {
            $day_label = date('M d', strtotime($date)); // Jan 01, Jan 02
        }
        
        // Get revenue for this date
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(reward_earned), 0) as revenue
            FROM ad_views
            WHERE DATE(viewed_at) = ?
        ");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $revenue_data[] = [
            'day' => $day_label,
            'amount' => floatval($result['revenue']),
            'date' => $date
        ];
        
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'revenue_data' => $revenue_data,
        'period' => $days
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading revenue data'
    ]);
}

$conn->close();
?>