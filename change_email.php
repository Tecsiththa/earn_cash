<?php
require_once 'config.php';
requireCustomer();

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$user_id = $_SESSION['user_id'];
$new_email = trim($data['new_email'] ?? '');
$password = $data['password'] ?? '';

// Validate
if (empty($new_email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit;
}

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

$conn = getDBConnection();

// Get current user data
$stmt = $conn->prepare("SELECT password, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    $conn->close();
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Password is incorrect'
    ]);
    $conn->close();
    exit;
}

// Check if new email is same as current
if ($new_email === $user['email']) {
    echo json_encode([
        'success' => false,
        'message' => 'New email is the same as current email'
    ]);
    $conn->close();
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $new_email, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Email already in use. Please choose another.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Update email
$stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
$stmt->bind_param("si", $new_email, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Email changed successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to change email'
    ]);
}

$stmt->close();
$conn->close();
?>