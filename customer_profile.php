<?php
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $conn->prepare("SELECT id, username, email, balance, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT av.id) as total_views,
        COALESCE(SUM(av.reward_earned), 0) as total_earned,
        COUNT(DISTINCT DATE(av.viewed_at)) as active_days
    FROM ad_views av
    WHERE av.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get withdrawal statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_withdrawn,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount
    FROM withdrawals
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$withdrawal_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EarnCash</title>
    <link rel="stylesheet" href="assets/css/customer_profile.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-container">
            <h1>EarnCash</h1>
            <div class="nav-links">
                <a href="customer_dashboard.php">DASHBOARD</a>
                <a href="ad_viewer.php">WATCH ADS</a>
                <a href="user_profile.php" class="active">PROFILE</a>
                <a href="logout.php">HOME</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="header-content">
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p>Member since <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

           

            <!-- PROFILE SECTIONS -->
            <div class="content-grid">
                <!-- ACCOUNT INFORMATION -->
                <div class="card">
                    <div class="card-header">
                        <h3>👤 Account Information</h3>
                        <button class="btn-edit-profile" onclick="openEditProfileModal()">
                            ✏️ Edit Profile
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">User ID:</span>
                            <span class="info-value">#<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since:</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Login:</span>
                            <span class="info-value"><?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Active Days:</span>
                            <span class="info-value"><?php echo $stats['active_days']; ?> days</span>
                        </div>
                    </div>
                </div>

                <!-- SECURITY SETTINGS -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔒 Security Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="security-item">
                            <div class="security-info">
                                <h4>🔑 Change Password</h4>
                                <p>Update your password to keep your account secure</p>
                            </div>
                            <button class="btn btn-primary" onclick="openChangePasswordModal()">
                                Change Password
                            </button>
                        </div>
                        <div class="security-item">
                            <div class="security-info">
                                <h4>📧 Change Email</h4>
                                <p>Update your email address for account recovery</p>
                            </div>
                            <button class="btn btn-secondary" onclick="openChangeEmailModal()">
                                Change Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <h3>📈 Activity Summary</h3>
                </div>
                <div class="card-body">
                    <div class="activity-grid">
                        <div class="activity-item">
                            <div class="activity-icon">🎬</div>
                            <div class="activity-details">
                                <h4><?php echo number_format($stats['total_views']); ?></h4>
                                <p>Total Ad Views</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">📅</div>
                            <div class="activity-details">
                                <h4><?php echo $stats['active_days']; ?></h4>
                                <p>Active Days</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">💵</div>
                            <div class="activity-details">
                                <h4>$<?php echo number_format($stats['total_earned'], 2); ?></h4>
                                <p>Total Earnings</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">📝</div>
                            <div class="activity-details">
                                <h4><?php echo $withdrawal_stats['total_requests']; ?></h4>
                                <p>Withdrawal Requests</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- EDIT PROFILE MODAL -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Profile</h3>
                <span class="modal-close" onclick="closeEditProfileModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editProfileForm">
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div id="profileMessage" class="message" style="display: none;"></div>
                    <button type="submit" class="btn btn-submit">Update Profile</button>
                </form>
            </div>
        </div>
    </div>

    <!-- CHANGE PASSWORD MODAL -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔑 Change Password</h3>
                <span class="modal-close" onclick="closeChangePasswordModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
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
                    <div id="passwordMessage" class="message" style="display: none;"></div>
                    <button type="submit" class="btn btn-submit">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- CHANGE EMAIL MODAL -->
    <div id="changeEmailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📧 Change Email</h3>
                <span class="modal-close" onclick="closeChangeEmailModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="changeEmailForm">
                    <div class="form-group">
                        <label for="current_email">Current Email</label>
                        <input type="email" id="current_email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_email">New Email</label>
                        <input type="email" id="new_email" name="new_email" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Password (for verification)</label>
                        <input type="password" id="password_confirm" name="password" required>
                    </div>
                    <div id="emailMessage" class="message" style="display: none;"></div>
                    <button type="submit" class="btn btn-submit">Change Email</button>
                </form>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>💰 EarnCash</h3>
                    <p>Earn money by watching advertisements. Simple, fast, and reliable.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="contact.html">Help Center</a></li>
                        <li><a href="about.html">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="contact.html">FAQ</a></li>
                    </ul>
                </div>
            </div>
           
        </div>
    </footer>

    <script src="assets/js/customer_profile.js"></script>
</body>
</html>