<?php
// Ensure all errors are caught and returned as JSON
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

require_once 'config.php';
requireAdmin();

header('Content-Type: application/json');
ob_start();

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

$conn = getDBConnection();

// Extract data
$ad_id = isset($data['ad_id']) && $data['ad_id'] !== '' ? intval($data['ad_id']) : null;
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$url = trim($data['url'] ?? '');
$video_url = trim($data['video_url'] ?? '');
$image_url = trim($data['image_url'] ?? '');
$reward = floatval($data['reward'] ?? 0.05);
$duration = intval($data['duration'] ?? 30);
$minimum_watch_time = intval($data['minimum_watch_time'] ?? 30);
$is_active = intval($data['is_active'] ?? 1);

// Validation
$errors = [];

if (empty($title)) {
    $errors[] = 'Title is required';
}

if (strlen($title) < 3) {
    $errors[] = 'Title must be at least 3 characters';
}

if (strlen($title) > 255) {
    $errors[] = 'Title must not exceed 255 characters';
}

if (empty($description)) {
    $errors[] = 'Description is required';
}

if (strlen($description) < 10) {
    $errors[] = 'Description must be at least 10 characters';
}

if (empty($video_url)) {
    $errors[] = 'Video URL is required';
}

if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
    $errors[] = 'Video URL is invalid';
}

if (empty($url)) {
    $errors[] = 'Landing page URL is required';
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    $errors[] = 'Landing page URL is invalid';
}

if (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
    $errors[] = 'Thumbnail image URL is invalid';
}

if ($reward <= 0) {
    $errors[] = 'Reward amount must be greater than 0';
}

if ($reward > 100) {
    $errors[] = 'Reward amount cannot exceed $100';
}

if ($duration <= 0) {
    $errors[] = 'Duration must be greater than 0';
}

if ($duration > 3600) {
    $errors[] = 'Duration cannot exceed 3600 seconds (1 hour)';
}

if ($minimum_watch_time <= 0) {
    $errors[] = 'Minimum watch time must be greater than 0';
}

if ($minimum_watch_time > $duration) {
    $errors[] = 'Minimum watch time cannot be greater than duration';
}

if (!in_array($is_active, [0, 1])) {
    $errors[] = 'Invalid status value';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    $conn->close();
    exit;
}

try {
    if ($ad_id) {
        // ============================================
        // UPDATE EXISTING ADVERTISEMENT
        // ============================================
        
        // First, check if the ad exists
        $stmt = $conn->prepare("SELECT id FROM advertisements WHERE id = ?");
        $stmt->bind_param("i", $ad_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Advertisement not found'
            ]);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();
        
        // Update the advertisement
        $stmt = $conn->prepare("
            UPDATE advertisements 
            SET title = ?, 
                description = ?, 
                url = ?, 
                video_url = ?, 
                image_url = ?, 
                reward = ?, 
                duration = ?, 
                minimum_watch_time = ?, 
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssssdiiii", 
            $title, 
            $description, 
            $url, 
            $video_url, 
            $image_url, 
            $reward, 
            $duration, 
            $minimum_watch_time, 
            $is_active,
            $ad_id
        );
        
        if ($stmt->execute()) {
            // Check if any row was actually updated
            if ($stmt->affected_rows >= 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Advertisement updated successfully!',
                    'ad_id' => $ad_id,
                    'action' => 'update'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No changes were made to the advertisement'
                ]);
            }
        } else {
            throw new Exception('Failed to update advertisement: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } else {
        // ============================================
        // INSERT NEW ADVERTISEMENT
        // ============================================
        
        $stmt = $conn->prepare("
            INSERT INTO advertisements 
            (title, description, url, video_url, image_url, reward, duration, minimum_watch_time, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param(
            "sssssdiii", 
            $title, 
            $description, 
            $url, 
            $video_url, 
            $image_url, 
            $reward, 
            $duration, 
            $minimum_watch_time, 
            $is_active
        );
        
        if ($stmt->execute()) {
            $new_ad_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Advertisement created successfully!',
                'ad_id' => $new_ad_id,
                'action' => 'insert'
            ]);
        } else {
            throw new Exception('Failed to create advertisement: ' . $stmt->error);
        }
        
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log('Advertisement save error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>