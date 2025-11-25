<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$conn = getDBConnection();

// Get user from database
$stmt = $conn->prepare("SELECT id, username, email, password, role, balance FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['balance'] = $user['balance'];

// Determine redirect URL based on role
$redirect_url = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'ad_viewer.php';

echo json_encode([
    'success' => true, 
    'message' => 'Login successful!',
    'redirect' => $redirect_url,
    'role' => $user['role']
]);
?>