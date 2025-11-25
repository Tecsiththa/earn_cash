<?php
// ============================================
// REPORTS PAGE - Admin Analytics & Reports
// ============================================
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Total Revenue
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(reward_earned), 0) as total_revenue
    FROM ad_views
    WHERE DATE(viewed_at) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total_revenue'];
$stmt->close();

// Total Withdrawals
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_withdrawn
    FROM withdrawals
    WHERE status = 'approved' AND DATE(processed_at) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_withdrawn = $stmt->get_result()->fetch_assoc()['total_withdrawn'];
$stmt->close();

// New Users
$stmt = $conn->prepare("
    SELECT COUNT(*) as new_users
    FROM users
    WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$new_users = $stmt->get_result()->fetch_assoc()['new_users'];
$stmt->close();

// Total Views
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_views
    FROM ad_views
    WHERE DATE(viewed_at) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_views = $stmt->get_result()->fetch_assoc()['total_views'];
$stmt->close();

// Top Users
$stmt = $conn->prepare("
    SELECT u.username, u.email, COUNT(av.id) as views, COALESCE(SUM(av.reward_earned), 0) as earned
    FROM users u
    JOIN ad_views av ON u.id = av.user_id
    WHERE DATE(av.viewed_at) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY earned DESC
    LIMIT 10
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_users = $stmt->get_result();
$stmt->close();

// Top Ads
$stmt = $conn->prepare("
    SELECT a.title, COUNT(av.id) as views, COALESCE(SUM(av.reward_earned), 0) as paid
    FROM advertisements a
    JOIN ad_views av ON a.id = av.ad_id
    WHERE DATE(av.viewed_at) BETWEEN ? AND ?
    GROUP BY a.id
    ORDER BY views DESC
    LIMIT 10
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_ads = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - EarnCash Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <style>
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .filter-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
        }
        .btn-filter {
            padding: 0.8rem 2rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-export {
            padding: 0.8rem 2rem;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .profit-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
        }
        .profit-box h2 {
            font-size: 3rem;
            margin: 1rem 0;
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
            <a href="reports.php" class="nav-item active"><span class="icon">📈</span><span>Reports</span></a>
            <a href="settings.php" class="nav-item"><span class="icon">⚙️</span><span>Settings</span></a>
            <a href="logout.php" class="nav-item logout"><span class="icon">🚪</span><span>Logout</span></a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Reports & Analytics</h1>
                <p>Detailed reports and statistics</p>
            </div>
        </header>

        <!-- DATE FILTER -->
        <div class="filter-section">
            <form class="filter-form" method="GET">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <button type="button" class="btn-export" onclick="exportReport()">Export PDF</button>
            </form>
        </div>

        <!-- PROFIT SUMMARY -->
        <div class="profit-box">
            <h3>Net Profit (Revenue - Withdrawals)</h3>
            <h2>$<?php echo number_format($total_revenue - $total_withdrawn, 2); ?></h2>
            <p><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
        </div>

        <!-- STATISTICS -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-icon">💰</div>
                <div class="stat-details">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">💸</div>
                <div class="stat-details">
                    <h3>Total Withdrawals</h3>
                    <p class="stat-number">$<?php echo number_format($total_withdrawn, 2); ?></p>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">👥</div>
                <div class="stat-details">
                    <h3>New Users</h3>
                    <p class="stat-number"><?php echo $new_users; ?></p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">👁️</div>
                <div class="stat-details">
                    <h3>Total Views</h3>
                    <p class="stat-number"><?php echo number_format($total_views); ?></p>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <!-- TOP USERS -->
            <div class="card">
                <div class="card-header">
                    <h3>Top Earning Users</h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Views</th>
                                <th>Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; while ($user = $top_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['views']; ?></td>
                                <td>$<?php echo number_format($user['earned'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOP ADS -->
            <div class="card">
                <div class="card-header">
                    <h3>Top Performing Ads</h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Ad Title</th>
                                <th>Views</th>
                                <th>Total Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; while ($ad = $top_ads->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($ad['title']); ?></td>
                                <td><?php echo number_format($ad['views']); ?></td>
                                <td>$<?php echo number_format($ad['paid'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportReport() {
            alert('Export to PDF functionality - Integrate with library like TCPDF or FPDF');
            // window.location.href = 'export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
        }
    </script>
</body>
</html>