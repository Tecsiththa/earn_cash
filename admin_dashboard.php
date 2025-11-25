<?php
// ============================================
// ADMIN DASHBOARD - COMPLETE PHP BACKEND
// ============================================
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// ============================================
// GET TOTAL USERS (CUSTOMERS ONLY)
// ============================================
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calculate user growth (last month)
$stmt = $conn->prepare("
    SELECT COUNT(*) as last_month 
    FROM users 
    WHERE role = 'customer' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
");
$stmt->execute();
$new_users_month = $stmt->get_result()->fetch_assoc()['last_month'];
$user_growth = $total_users > 0 ? round(($new_users_month / $total_users) * 100, 1) : 0;
$stmt->close();

// ============================================
// GET TOTAL ADVERTISEMENTS
// ============================================
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM advertisements");
$stmt->execute();
$total_ads = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Count new ads this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as new_ads 
    FROM advertisements 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
");
$stmt->execute();
$new_ads = $stmt->get_result()->fetch_assoc()['new_ads'];
$stmt->close();

// ============================================
// GET TOTAL AD VIEWS
// ============================================
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ad_views");
$stmt->execute();
$total_views = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calculate views growth (this week)
$stmt = $conn->prepare("
    SELECT COUNT(*) as week_views 
    FROM ad_views 
    WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
");
$stmt->execute();
$week_views = $stmt->get_result()->fetch_assoc()['week_views'];
$views_growth = $total_views > 0 ? round(($week_views / $total_views) * 100, 1) : 0;
$stmt->close();

// ============================================
// GET PENDING WITHDRAWALS
// ============================================
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawals WHERE status = 'pending'");
$stmt->execute();
$pending_withdrawals = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ============================================
// GET RECENT USERS (LAST 5)
// ============================================
$stmt = $conn->prepare("
    SELECT username, email, balance, created_at, last_login
    FROM users 
    WHERE role = 'customer'
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->get_result();
$stmt->close();

// ============================================
// GET PENDING WITHDRAWAL REQUESTS
// ============================================
$stmt = $conn->prepare("
    SELECT w.id, u.username, w.amount, w.payment_method, w.requested_at
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    WHERE w.status = 'pending'
    ORDER BY w.requested_at DESC
    LIMIT 5
");
$stmt->execute();
$withdrawal_requests = $stmt->get_result();
$stmt->close();

// ============================================
// GET WEEKLY REVENUE DATA (LAST 7 DAYS)
// ============================================
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(reward_earned), 0) as revenue
        FROM ad_views
        WHERE DATE(viewed_at) = ?
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $revenue_data[] = [
        'day' => $day_name,
        'amount' => $result['revenue']
    ];
    $stmt->close();
}

$max_revenue = max(array_column($revenue_data, 'amount'));
$max_revenue = $max_revenue > 0 ? $max_revenue : 1;

// ============================================
// GET NOTIFICATION COUNT
// ============================================
$notification_count = $pending_withdrawals;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EarnCash</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <h2>EarnCash Admin</h2>
        </div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item active">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="icon">👥</span>
                <span>Users</span>
            </a>
            <a href="manage_ads.php" class="nav-item">
                <span class="icon">📺</span>
                <span>Advertisements</span>
            </a>
            <a href="manage_withdrawals.php" class="nav-item">
                <span class="icon">💰</span>
                <span>Withdrawals</span>
            </a>
            <a href="reports.php" class="nav-item">
                <span class="icon">📈</span>
                <span>Reports</span>
            </a>
            <a href="settings.php" class="nav-item">
                <span class="icon">⚙️</span>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="nav-item logout">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <header class="header">
            <div class="header-left">
                <h1>Dashboard Overview</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search users...">
                    <span class="search-icon">🔍</span>
                </div>
                <div class="notifications" onclick="window.location.href='manage_withdrawals.php'">
                    <span class="notification-icon">🔔</span>
                    <?php if ($notification_count > 0): ?>
                    <span class="badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </div>
                <div class="admin-profile">
                    <img src="https://via.placeholder.com/40" alt="Admin">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- STATISTICS CARDS -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-icon">👥</div>
                <div class="stat-details">
                    <h3>Total Users</h3>
                    <p class="stat-number" id="totalUsers"><?php echo number_format($total_users); ?></p>
                    <span class="stat-change positive">+<?php echo $user_growth; ?>% from last month</span>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">📺</div>
                <div class="stat-details">
                    <h3>Total Ads</h3>
                    <p class="stat-number" id="totalAds"><?php echo $total_ads; ?></p>
                    <span class="stat-change positive">+<?php echo $new_ads; ?> new ads</span>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">👁️</div>
                <div class="stat-details">
                    <h3>Total Views</h3>
                    <p class="stat-number" id="totalViews"><?php echo number_format($total_views); ?></p>
                    <span class="stat-change positive">+<?php echo $views_growth; ?>% this week</span>
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">💸</div>
                <div class="stat-details">
                    <h3>Pending Withdrawals</h3>
                    <p class="stat-number" id="pendingWithdrawals"><?php echo $pending_withdrawals; ?></p>
                    <span class="stat-change">Needs attention</span>
                </div>
            </div>
        </div>

        <!-- CHARTS AND TABLES -->
        <div class="content-grid">
            <!-- RECENT USERS -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Users</h3>
                    <a href="manage_users.php" class="view-all">View All →</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): 
                                $is_active = $user['last_login'] && strtotime($user['last_login']) > strtotime('-7 days');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>$<?php echo number_format($user['balance'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PENDING WITHDRAWALS -->
            <div class="card">
                <div class="card-header">
                    <h3>Pending Withdrawals</h3>
                    <a href="manage_withdrawals.php" class="view-all">View All →</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($withdrawal_requests->num_rows > 0): ?>
                                <?php while ($withdrawal = $withdrawal_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($withdrawal['username']); ?></td>
                                    <td>$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['payment_method']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($withdrawal['requested_at'])); ?></td>
                                    <td>
                                        <button class="btn-approve" onclick="handleWithdrawal(<?php echo $withdrawal['id']; ?>, 'approve')">Approve</button>
                                        <button class="btn-reject" onclick="handleWithdrawal(<?php echo $withdrawal['id']; ?>, 'reject')">Reject</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #999;">
                                        No pending withdrawals
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REVENUE CHART -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>Revenue Overview</h3>
                    <select class="period-select" onchange="changeRevenuePeriod(this.value)">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                    </select>
                </div>
                <div class="chart-placeholder">
                    <div class="bar-chart" id="revenueChart">
                        <?php foreach ($revenue_data as $data): 
                            $height = ($data['amount'] / $max_revenue) * 100;
                            $height = max($height, 5);
                        ?>
                        <div class="bar" style="height: <?php echo $height; ?>%">
                            <span><?php echo $data['day']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>