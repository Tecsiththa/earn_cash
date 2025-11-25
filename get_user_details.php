<?php<?php

header('Content-Type: application/json');// Ensure all errors are caught and returned as JSON

// Minimal stub for user details APIfunction handleError($errno, $errstr, $errfile, $errline) {

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {    echo json_encode([

    echo json_encode([        'success' => false,

        'success' => false,        'message' => 'Server error: ' . $errstr,

        'message' => 'Missing or invalid user_id'        'error_details' => [

    ]);            'file' => basename($errfile),

    exit;            'line' => $errline

}        ]

$user_id = intval($_GET['user_id']);    ]);

// TODO: Replace with real DB lookup    exit;

// Example response}

$data = [set_error_handler('handleError');

    'id' => $user_id,

    'username' => 'DemoUser',require_once 'config.php';

    'email' => 'demo@example.com',requireAdmin();

    'balance' => 0.00,

    'role' => 'customer',// Ensure we're sending JSON response

    'created_at' => date('Y-m-d'),header('Content-Type: application/json');

    'last_login' => null,

    'chart_data' => [],// Start output buffering to catch any unwanted output

];ob_start();

echo json_encode([

    'success' => true,// Get user_id from GET

    'data' => $data$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

]);

?>if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

$conn = getDBConnection();

// Get user information
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.balance,
        u.role,
        u.created_at,
        u.last_login,
        COUNT(DISTINCT av.id) as total_views,
        COALESCE(SUM(av.reward_earned), 0) as total_earned,
        COUNT(DISTINCT DATE(av.viewed_at)) as active_days
    FROM users u
    LEFT JOIN ad_views av ON u.id = av.user_id
    WHERE u.id = ?
    <?php
    header('Content-Type: application/json');
    function handleError($errno, $errstr, $errfile, $errline) {
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

    if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid user_id'
        ]);
        exit;
    }
    $user_id = intval($_GET['user_id']);

    // TODO: Replace with real DB lookup
    $data = [
        'id' => $user_id,
        'username' => 'DemoUser',
        'email' => 'demo@example.com',
        'balance' => 0.00,
        'role' => 'customer',
        'created_at' => date('Y-m-d'),
        'last_login' => null,
        'chart_data' => [],
    ];
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    ?>

// Get recent ad views (last 5)
$stmt = $conn->prepare("
    SELECT 
        av.viewed_at,
        av.reward_earned,
        a.title as ad_title
    FROM ad_views av
    LEFT JOIN advertisements a ON av.ad_id = a.id
    WHERE av.user_id = ?
    ORDER BY av.viewed_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_views = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user['recent_views'] = $recent_views;

// Get recent withdrawals (last 3)
$stmt = $conn->prepare("
    SELECT 
        id,
        amount,
        payment_method,
        status,
        COALESCE(request_date, request_time, created_at) as request_date
    FROM withdrawals
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 3
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_withdrawals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user['recent_withdrawals'] = $recent_withdrawals;

// Get last 7 days earnings for chart
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    $stmt = $conn->prepare("
        SELECT COALESCE(total_earned, 0) as earned 
        FROM daily_earnings 
        WHERE user_id = ? AND date = ?
    ");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $chart_data[] = [
        'day' => $day_name,
        'amount' => $result ? $result['earned'] : 0
    ];
    $stmt->close();
}

$user['chart_data'] = $chart_data;

$conn->close();

// Clean any unwanted output
ob_clean();

// Ensure dates are in correct format for JSON
$user['created_at'] = $user['created_at'] ? date('c', strtotime($user['created_at'])) : null;
$user['last_login'] = $user['last_login'] ? date('c', strtotime($user['last_login'])) : null;

// Convert withdrawal dates
foreach ($user['recent_withdrawals'] as &$withdrawal) {
    $withdrawal['request_date'] = $withdrawal['request_date'] ? date('c', strtotime($withdrawal['request_date'])) : null;
}
unset($withdrawal); // Break the reference

try {
    echo json_encode([
        'success' => true,
        'data' => $user
    ], JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to encode response data',
        'error' => $e->getMessage()
    ]);
}
?>