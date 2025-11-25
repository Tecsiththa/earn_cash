<?php
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get today's earnings
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COALESCE(total_earned, 0) as today_earned, COALESCE(ads_viewed, 0) as ads_today FROM daily_earnings WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_data = $stmt->get_result()->fetch_assoc();
$today_earnings = $today_data ? $today_data['today_earned'] : 0;
$ads_today = $today_data ? $today_data['ads_today'] : 0;
$daily_limit = 10; // number of ads allowed per day (display purposes)
$stmt->close();

// Get total rewards
$stmt = $conn->prepare("SELECT COALESCE(SUM(reward_earned), 0) as total FROM ad_views WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_rewards = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get current balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$balance = $stmt->get_result()->fetch_assoc()['balance'];
$stmt->close();

// Get next available ad
$stmt = $conn->prepare("
    SELECT a.* FROM advertisements a 
    WHERE a.is_active = 1 
    AND a.video_url IS NOT NULL
    AND a.id NOT IN (SELECT ad_id FROM ad_views WHERE user_id = ?)
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ad = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

// Normalize video URL to HTTPS to avoid mixed-content when page is served over HTTPS
$video_url = null;
if ($ad && !empty($ad['video_url'])) {
    $video_url = trim($ad['video_url']);
    // If URL starts with protocol-relative or http, prefer https
    if (strpos($video_url, '//') === 0) {
        $video_url = 'https:' . $video_url;
    } elseif (stripos($video_url, 'http://') === 0) {
        $video_url = 'https://' . substr($video_url, 7);
    }
    // Note: if the remote host does not serve HTTPS, consider hosting the video locally
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Viewing - EarnCash</title>
    <link rel="stylesheet" href="assets/css/ad_viewer.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>EarnCash</h1>
            <div class="nav-links">
                <a href="index.html">HOME</a>
                <a href="about.html">ABOUT US</a>
                <a href="contact.html">CONTACT US</a>
                
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="ad-section">
            <div class="section-header">
                <h2>📺 Advertisement Viewing</h2>
                <p class="reward-text">💰 You will earn $0.05 for viewing this ad</p>
            </div>

            <div class="stats-bar">
                <div class="stat-item stat-earnings">
                    <div class="stat-icon">💵</div>
                    <div class="stat-info">
                        <div class="stat-label">Today's Earnings</div>
                        <div class="stat-value" id="todayEarnings">$<?php echo number_format($today_earnings, 2); ?></div>
                    </div>
                </div>
                <div class="stat-item stat-ads">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-info">
                        <div class="stat-label">Ads Viewed (per day)</div>
                        <div class="stat-value" id="adsToday"><?php echo $ads_today; ?>/<?php echo $daily_limit; ?></div>
                    </div>
                </div>
                <div class="stat-item stat-total">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Rewards</div>
                        <div class="stat-value" id="totalRewards">$<?php echo number_format($total_rewards, 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="video-container" id="videoContainer">
                <?php if ($ad): ?>
                    <video id="adVideo" controls>
                        <source src="<?php echo htmlspecialchars($video_url ?? $ad['video_url']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <div class="no-ads-message">
                        <div class="no-ads-icon">🎬</div>
                        <p>No more ads available today.</p>
                        <p class="small">Check back tomorrow for more earning opportunities!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="progress-section">
                <p class="progress-text">⏱️ Minimum viewing time: 30 seconds</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <div class="rewards-box">
                <div class="rewards-icon">🎁</div>
                <h3>Your Rewards</h3>
                <p class="coins-amount">$<span id="pendingReward">0.05</span></p>
                <p class="claim-instruction">Complete the ad to claim your rewards</p>
            </div>

            <div class="message" id="messageBox" style="display: none;"></div>

            <div class="action-buttons">
                <button class="btn btn-claim" id="claimBtn" disabled>
                    <span class="btn-icon">✓</span> Claim Rewards
                </button>
                <button class="btn btn-next" id="nextAdBtn">
                    <span class="btn-icon">▶</span> Next Ad
                </button>
                <button class="btn btn-dashboard" onclick="window.location.href='customer_dashboard.php'">
                    <span class="btn-icon">📊</span> Dashboard
                </button>
            </div>
        </div>
    </main>

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

    <script>
        const adData = <?php echo $ad ? json_encode($ad) : 'null'; ?>;
        const userId = <?php echo $user_id; ?>;
    </script>
    <script src="assets/js/ad_viewer.js"></script>
</body>
</html>