<?php
// ============================================
// CUSTOMER DASHBOARD - COMPLETE PHP BACKEND
// ============================================
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// ============================================
// GET USER INFORMATION
// ============================================
$stmt = $conn->prepare("SELECT username, email, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ============================================
// GET TODAY'S EARNINGS
// ============================================
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COALESCE(total_earned, 0) as today_earned, COALESCE(ads_viewed, 0) as ads_today 
    FROM daily_earnings 
    WHERE user_id = ? AND date = ?
");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_data = $stmt->get_result()->fetch_assoc();
$today_earnings = $today_data ? $today_data['today_earned'] : 0;
$ads_today = $today_data ? $today_data['ads_today'] : 0;
$stmt->close();

// ============================================
// GET TOTAL EARNINGS AND VIEWS
// ============================================
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(reward_earned), 0) as total_earned, COUNT(*) as total_views
    FROM ad_views 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_data = $stmt->get_result()->fetch_assoc();
$total_earned = $total_data['total_earned'];
$total_views = $total_data['total_views'];
$stmt->close();

// ============================================
// GET AVAILABLE ADS (TOP 3)
// ============================================
$stmt = $conn->prepare("
    SELECT a.* FROM advertisements a 
    WHERE a.is_active = 1 
    AND a.id NOT IN (SELECT ad_id FROM ad_views WHERE user_id = ?)
    ORDER BY a.created_at DESC
    LIMIT 3
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_ads = $stmt->get_result();
$total_available = $available_ads->num_rows;
$stmt->close();

// ============================================
// GET RECENT ACTIVITY (LAST 5)
// ============================================
$stmt = $conn->prepare("
    SELECT 'ad_view' as type, a.title as description, av.reward_earned as amount, av.viewed_at as date
    FROM ad_views av
    JOIN advertisements a ON av.ad_id = a.id
    WHERE av.user_id = ?
    ORDER BY av.viewed_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activity = $stmt->get_result();
$stmt->close();

// ============================================
// GET LAST 7 DAYS FOR CHART
// ============================================
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

$max_value = max(array_column($chart_data, 'amount'));
$max_value = $max_value > 0 ? $max_value : 1;

// ============================================
// GET WITHDRAWAL INFO
// ============================================
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_withdrawn 
    FROM withdrawals 
    WHERE user_id = ? AND status = 'approved'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_withdrawn = $stmt->get_result()->fetch_assoc()['total_withdrawn'];
$stmt->close();

$conn->close();

$ads_remaining = 10 - $ads_today;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - EarnCash</title>
    <link rel="stylesheet" href="assets/css/customer_dashboard.css">
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>EarnCash</h1>
            </div>
            
            <div class="user-section">
                <div class="user-profile">
                    
                    <span>👨‍💼<?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <button class="logout-btn" onclick="window.location.href='index.html'">HOME</button>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">
            <!-- WELCOME SECTION -->
            <section class="welcome-section">
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Welcome Back, <?php echo htmlspecialchars($user['username']); ?>! 👋</h2>
                        <p>You're doing great! Keep watching ads to earn more rewards.</p>
                    </div>
                    <div class="quick-actions">
                        <button class="btn btn-primary" onclick="window.location.href='ad_viewer.php'">Watch Ads Now</button>
                        <button class="btn btn-secondary" onclick="window.location.href='withdraw.php'">Request Withdrawal</button>
                    </div>
                </div>
            </section>

            <!-- EARNINGS STATS -->
            <section class="earnings-stats">
                <div class="stat-card purple">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Today's Earnings</h3>
                        <p class="stat-value" id="todayEarnings">$<?php echo number_format($today_earnings, 2); ?></p>
                        <span class="stat-subtext"><?php echo $ads_today; ?> ads watched</span>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3>Balance Earnings</h3>
                        <p class="stat-value" id="userBalance">$<?php echo number_format($user['balance'], 2); ?></p>
                        <span class="stat-subtext"></span>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <h3>Total Earned</h3>
                        <p class="stat-value" id="totalEarned">$<?php echo number_format($total_earned, 2); ?></p>
                        <span class="stat-subtext"><?php echo $total_views; ?> total views</span>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-info">
                        <h3>Ads Remaining Today</h3>
                        <p class="stat-value" id="adsRemaining"><?php echo $ads_remaining; ?>/10</p>
                        <span class="stat-subtext">Watch now to earn</span>
                    </div>
                </div>
            </section>

            <!-- CONTENT GRID -->
            <div class="content-grid">
                <!-- AVAILABLE ADS -->
                <div class="card available-ads">
                    <div class="card-header">
                        <h3>Available Advertisements</h3>
                        <span class="badge"><?php echo $total_available; ?> New</span>
                    </div>
                    <div class="ads-list">
                        <?php if ($available_ads->num_rows > 0): ?>
                            <?php while ($ad = $available_ads->fetch_assoc()): ?>
                            <?php
                                // Normalize image URL to HTTPS to avoid mixed-content warnings
                                $image_url = null;
                                if (!empty($ad['image_url'])) {
                                    $image_url = trim($ad['image_url']);
                                    if (strpos($image_url, '//') === 0) {
                                        $image_url = 'https:' . $image_url;
                                    } elseif (stripos($image_url, 'http://') === 0) {
                                        $image_url = 'https://' . substr($image_url, 7);
                                    }
                                }
                            ?>
                            <div class="ad-item">
                                <img src="<?php echo htmlspecialchars($image_url ?? 'assets/img/ad_placeholder.png'); ?>" alt="Ad Thumbnail">
                                <div class="ad-details">
                                    <h4><?php echo htmlspecialchars($ad['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($ad['description']); ?></p>
                                    <div class="ad-meta">
                                        <span class="reward">💵 $<?php echo number_format($ad['reward'], 2); ?></span>
                                        <span class="duration">⏱️ <?php echo $ad['duration']; ?> sec</span>
                                    </div>
                                </div>
                                <button class="btn-watch" onclick="window.location.href='ad_viewer.php'">Watch Now</button>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 2rem; color: #999;">No ads available right now</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="ad_viewer.php" class="view-all-link">View All Ads →</a>
                    </div>
                </div>

                <!-- RECENT ACTIVITY -->
                <div class="card recent-activity">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="activity-list">
                        <?php if ($recent_activity->num_rows > 0): ?>
                            <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-icon earned">✓</div>
                                <div class="activity-details">
                                    <h4>Ad Viewed</h4>
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="activity-time"><?php echo time_ago($activity['date']); ?></span>
                                </div>
                                <div class="activity-amount earned">+$<?php echo number_format($activity['amount'], 2); ?></div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 2rem; color: #999;">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- EARNINGS CHART -->
                <div class="card earnings-chart">
                    <div class="card-header">
                        <h3>Earnings Overview</h3>
                        <select class="period-select" onchange="changePeriod(this.value)">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 3 Months</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <div class="chart-bars" id="chartBars">
                            <?php foreach ($chart_data as $data): 
                                $height = ($data['amount'] / $max_value) * 100;
                                $height = max($height, 5);
                            ?>
                            <div class="chart-bar" style="height: <?php echo $height; ?>%">
                                <span class="bar-value">$<?php echo number_format($data['amount'], 2); ?></span>
                                <span class="bar-label"><?php echo $data['day']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- WITHDRAWAL INFO -->
                <div class="card withdrawal-info">
                    <div class="card-header">
                        <h3>Withdrawal Information</h3>
                    </div>
                    <div class="withdrawal-content">
                        <div class="info-item">
                            <span class="info-label">Available Balance</span>
                            <span class="info-value">$<?php echo number_format($user['balance'], 2); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Minimum Withdrawal</span>
                            <span class="info-value">$1.00</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pending Requests</span>
                            <span class="info-value">0</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Withdrawn</span>
                            <span class="info-value">$<?php echo number_format($total_withdrawn, 2); ?></span>
                        </div>
                        <button class="btn btn-withdraw" onclick="window.location.href='withdraw.php'">Request Withdrawal</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
    
    <script src="assets/js/customer_dashboard.js"></script>
</body>
</html>

<?php
// ============================================
// HELPER FUNCTION: TIME AGO
// ============================================
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' seconds ago';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' minutes ago';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' hours ago';
    } else {
        return floor($difference / 86400) . ' days ago';
    }
}
?>