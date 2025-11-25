<?php
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['amount']) || !isset($data['payment_method']) || !isset($data['payment_details'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = floatval($data['amount']);
$payment_method = $data['payment_method'];
$payment_details = json_encode($data['payment_details']);

// Validate amount
if ($amount < 1.00) {
    echo json_encode([
        'success' => false,
        'message' => 'Minimum withdrawal amount is $1.00'
    ]);
    exit;
}

$conn = getDBConnection();

// Get user's current balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$current_balance = $result['balance'];
$stmt->close();

// Get pending withdrawals
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as pending_amount 
    FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_amount = $stmt->get_result()->fetch_assoc()['pending_amount'];
$stmt->close();

// Calculate available balance
$available_balance = $current_balance - $pending_amount;

// Validate sufficient balance
if ($amount > $available_balance) {
    echo json_encode([
        'success' => false,
        'message' => 'Insufficient balance. Available: $' . number_format($available_balance, 2)
    ]);
    $conn->close();
    exit;
}

// Check if user has pending withdrawal
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_count 
    FROM withdrawals 
    WHERE user_id = ? AND status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_count = $stmt->get_result()->fetch_assoc()['pending_count'];
$stmt->close();

if ($pending_count > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.'
    ]);
    $conn->close();
    exit;
}

// Validate payment method
$valid_methods = ['paypal', 'bank_transfer', 'mobile_money', 'cryptocurrency'];
if (!in_array($payment_method, $valid_methods)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid payment method'
    ]);
    $conn->close();
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert withdrawal request
    $stmt = $conn->prepare("
        INSERT INTO withdrawals (user_id, amount, payment_method, payment_details, status, request_date) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->bind_param("idss", $user_id, $amount, $payment_method, $payment_details);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create withdrawal request');
    }
    
    $withdrawal_id = $conn->insert_id;
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal request submitted successfully! Request ID: #' . $withdrawal_id,
        'withdrawal_id' => $withdrawal_id
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process withdrawal request. Please try again.'
    ]);
}

$conn->close();
?>