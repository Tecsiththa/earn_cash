<?php
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

// Get withdrawal ID
$withdrawal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($withdrawal_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid withdrawal ID'
    ]);
    exit;
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get withdrawal details (ensure it belongs to the logged-in user)
$stmt = $conn->prepare("
    SELECT * FROM withdrawals 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $withdrawal_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Withdrawal not found'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$withdrawal = $result->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'data' => $withdrawal
]);
?>