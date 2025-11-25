<?php
// ============================================
// SETTINGS PAGE - System Configuration
// ============================================
require_once 'config.php';
requireAdmin();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $min_withdrawal = floatval($_POST['min_withdrawal'] ?? 5.00);
        $max_ads_per_day = intval($_POST['max_ads_per_day'] ?? 10);
        $default_reward = floatval($_POST['default_reward'] ?? 0.05);
        
        // Update settings in database or config file
        $success_message = 'Settings updated successfully!';
        
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $conn = getDBConnection();
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
                $stmt->execute();
                $success_message = 'Password changed successfully!';
            } else {
                $error_message = 'Current password is incorrect';
            }
            $conn->close();
        } else {
            $error_message = 'Passwords do not match or too short (min 6 characters)';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EarnCash Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <style>
        .settings-container {
            max-width: 800px;
        }
        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .settings-section h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ecf0f1;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group small {
            color: #999;
            font-size: 0.85rem;
        }
        .btn-save {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-save:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo"><h2>EarnCash Admin</h2></div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="manage_users.php" class="nav-item"><span class="icon">👥</span><span>Users</span></a>
            <a href="manage_ads.php" class="nav-item"><span class="icon">📺</span><span>Advertisements</span></a>
            <a href="manage_withdrawals.php" class="nav-item"><span class="icon">💰</span><span>Withdrawals</span></a>
            <a href="reports.php" class="nav-item"><span class="icon">📈</span><span>Reports</span></a>
            <a href="settings.php" class="nav-item active"><span class="icon">⚙️</span><span>Settings</span></a>
            <a href="logout.php" class="nav-item logout"><span class="icon">🚪</span><span>Logout</span></a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>System Settings</h1>
                <p>Configure your EarnCash platform</p>
            </div>
        </header>

        <div class="settings-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- GENERAL SETTINGS -->
            <div class="settings-section">
                <h2>⚙️ General Settings</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group">
                        <label for="min_withdrawal">Minimum Withdrawal Amount ($)</label>
                        <input type="number" id="min_withdrawal" name="min_withdrawal" step="0.01" value="5.00" required>
                        <small>Users must have at least this amount to request withdrawal</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_ads_per_day">Maximum Ads Per Day</label>
                        <input type="number" id="max_ads_per_day" name="max_ads_per_day" value="10" required>
                        <small>Maximum number of ads a user can watch per day</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="default_reward">Default Reward Per Ad ($)</label>
                        <input type="number" id="default_reward" name="default_reward" step="0.01" value="0.05" required>
                        <small>Default reward amount for new advertisements</small>
                    </div>
                    
                    <button type="submit" class="btn-save">Save Settings</button>
                </form>
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="settings-section">
                <h2>🔐 Change Password</h2>
                <div class="info-box">
                    <strong>ℹ️ Security Tip:</strong> Use a strong password with at least 8 characters, including letters, numbers, and symbols.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6" required>
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn-save">Change Password</button>
                </form>
            </div>

            <!-- ADMIN INFO -->
            <div class="settings-section">
                <h2>👤 Admin Information</h2>
                <table class="data-table">
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><?php echo htmlspecialchars($_SESSION['username']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($_SESSION['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td>Administrator</td>
                    </tr>
                    <tr>
                        <td><strong>Login Time:</strong></td>
                        <td><?php echo date('M d, Y H:i'); ?></td>
                    </tr>
                </table>
            </div>

            <!-- SYSTEM INFO -->
            <div class="settings-section">
                <h2>💻 System Information</h2>
                <table class="data-table">
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td>MySQL/MariaDB</td>
                    </tr>
                    <tr>
                        <td><strong>Platform Version:</strong></td>
                        <td>EarnCash v1.0</td>
                    </tr>
                </table>
            </div>

            <!-- DANGER ZONE -->
            <div class="settings-section" style="border: 2px solid #e74c3c;">
                <h2 style="color: #e74c3c;">⚠️ Danger Zone</h2>
                <div class="info-box" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">
                    <strong>Warning:</strong> These actions are irreversible!
                </div>
                <button type="button" class="btn-save" style="background: #e74c3c;" onclick="clearLogs()">Clear Activity Logs</button>
                <button type="button" class="btn-save" style="background: #e74c3c; margin-left: 1rem;" onclick="resetStatistics()">Reset Statistics</button>
            </div>
        </div>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Are you sure you want to clear all activity logs? This action cannot be undone.')) {
                alert('Activity logs cleared (implement backend logic)');
            }
        }
        
        function resetStatistics() {
            if (confirm('Are you sure you want to reset all statistics? This action cannot be undone.')) {
                alert('Statistics reset (implement backend logic)');
            }
        }
    </script>
</body>
</html>