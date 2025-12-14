<?php
header('Content-Type: application/json');

// Load config and helpers
require_once 'config.php';

// Only admins can access this endpoint
requireAdmin();

// Convert PHP errors to JSON output
function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $errstr,
        'error_details' => [
            'file' => basename($errfile),
            'line' => $errline
        ]
    ]);
    exit;
}
set_error_handler('handleError');

// Start output buffering to ensure we return clean JSON
ob_start();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid user_id'
    ]);
    exit;
}

$user_id = intval($_GET['user_id']);
if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}


$conn = getDBConnection();
// Wrap db logic in try/catch so we return JSON on any db failure
try {

// Fetch user basic info with aggregated stats
$stmt = $conn->prepare("SELECT 
    u.id,
    u.username,
    u.email,
    COALESCE(u.balance, 0) as balance,
    u.role,
    u.created_at,
    u.last_login,
    COALESCE(COUNT(DISTINCT av.id), 0) as total_views,
    COALESCE(SUM(av.reward_earned), 0) as total_earned,
    COALESCE(COUNT(DISTINCT DATE(av.viewed_at)), 0) as active_days,
    COALESCE(SUM(CASE WHEN DATE(av.viewed_at) = CURRENT_DATE THEN 1 ELSE 0 END), 0) as ads_today
    FROM users u
    LEFT JOIN ad_views av ON u.id = av.user_id
    WHERE u.id = ?
    GROUP BY u.id");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}

// Fetch withdrawal stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_requests, COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount FROM withdrawals WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$wStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user['withdrawal_stats'] = [
    'total_requests' => intval($wStats['total_requests'] ?? 0),
    'completed_amount' => floatval($wStats['completed_amount'] ?? 0.0)
];

// Get today's earnings and ads today
$stmt = $conn->prepare("SELECT COALESCE(total_earned, 0) as today_earned, COALESCE(ads_viewed, 0) as ads_today FROM daily_earnings WHERE user_id = ? AND date = ?");
$today_date = date('Y-m-d');
$stmt->bind_param('is', $user_id, $today_date);
$stmt->execute();
$todayData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$user['today_earned'] = floatval($todayData['today_earned'] ?? 0);
$user['ads_today'] = intval($todayData['ads_today'] ?? ($user['ads_today'] ?? 0));

// Get recent ad views (last 5)
$stmt = $conn->prepare("SELECT av.viewed_at, av.reward_earned, a.title as ad_title FROM ad_views av LEFT JOIN advertisements a ON av.ad_id = a.id WHERE av.user_id = ? ORDER BY av.viewed_at DESC LIMIT 5");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_views = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user['recent_views'] = $recent_views;

    $recent_withdrawals = [];
    // Determine which request date column exists (try request_date, request_time, created_at)
    $dateColumns = ['request_date', 'request_time', 'created_at'];
    $selectedDateCol = null;
    foreach ($dateColumns as $col) {
        $res = $conn->query("SHOW COLUMNS FROM withdrawals LIKE '" . $conn->real_escape_string($col) . "'");
        if ($res && $res->num_rows > 0) {
            $selectedDateCol = $col;
            break;
        }
    }
    if (!$selectedDateCol) {
        // fallback to selecting NULL so we don't reference a missing column
        $dateSelect = "NULL as request_date";
    } else {
        $dateSelect = $conn->real_escape_string($selectedDateCol) . " as request_date";
    }

    $query = sprintf(
        "SELECT id, amount, payment_method, status, %s FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 3",
        $dateSelect
    );

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare withdrawal query',
            'error' => $conn->error
        ]);
        $conn->close();
        exit;
    }

    if (!$stmt->bind_param('i', $user_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to bind withdrawal query parameters',
            'error' => $stmt->error
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to execute withdrawal query',
            'error' => $stmt->error
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $recent_withdrawals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

$user['recent_withdrawals'] = $recent_withdrawals;

// Get last 7 days earnings for chart
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $stmt = $conn->prepare("SELECT COALESCE(total_earned, 0) as earned FROM daily_earnings WHERE user_id = ? AND date = ?");
    $stmt->bind_param('is', $user_id, $date);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $chart_data[] = [
        'day' => $day_name,
        'amount' => floatval($res['earned'] ?? 0)
    ];
}

$user['chart_data'] = $chart_data;

    $conn->close();
} catch (mysqli_sql_exception $e) {
    // Return JSON error if a DB exception occurs (prevents HTML error pages)
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}

// Clean any output that might have been printed accidentally
while (ob_get_level()) {
    ob_end_clean();
}

// Normalize dates for JSON
$user['created_at'] = $user['created_at'] ? date('c', strtotime($user['created_at'])) : null;
$user['last_login'] = $user['last_login'] ? date('c', strtotime($user['last_login'])) : null;
foreach ($user['recent_withdrawals'] as &$wd) {
    $wd['request_date'] = $wd['request_date'] ? date('c', strtotime($wd['request_date'])) : null;
}
unset($wd);

$json = json_encode(['success' => true, 'data' => $user]);
if ($json === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to encode response', 'error' => json_last_error_msg()]);
} else {
    echo $json;
}

?>