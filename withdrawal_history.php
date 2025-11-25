<?php
require_once 'config.php';
requireCustomer();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $conn->prepare("SELECT username, email, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get withdrawal statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as approved_amount,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END), 0) as rejected_amount
    FROM withdrawals 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'pending', 'approved', 'completed', 'rejected'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Build query based on filter
if ($filter === 'all') {
    $stmt = $conn->prepare("
        SELECT * FROM withdrawals 
        WHERE user_id = ? 
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT * FROM withdrawals 
        WHERE user_id = ? AND status = ?
        ORDER BY id DESC
    ");
    $stmt->bind_param("is", $user_id, $filter);
}

$stmt->execute();
$withdrawals = $stmt->get_result();
$stmt->close();

$conn->close();

// Calculate totals
$total_withdrawn = $stats['completed_amount'];
$pending_withdrawals = $stats['pending_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal History - EarnCash</title>
    <link rel="stylesheet" href="assets/css/user_pyament_history.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-container">
            <h1>EarnCash</h1>
            <div class="nav-links">
                <a href="customer_dashboard.php">DASHBOARD</a>
                <a href="ad_viewer.php">WATCH ADS</a>
                
                <a href="index.html">HOME</a>
                
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="header-content">
                    <h2>📊 Withdrawal History</h2>
                    <p>Track all your withdrawal requests and their status</p>
                </div>
                <button class="btn btn-new-request" onclick="window.location.href='withdraw.php'">
                    <span class="btn-icon">➕</span> New Withdrawal Request
                </button>
            </div>

            <!-- STATISTICS CARDS -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-header">
                        <div class="stat-icon">💰</div>
                        <div class="stat-badge">All Time</div>
                    </div>
                    <div class="stat-body">
                        <h3>Total Withdrawn</h3>
                        <p class="stat-value">$<?php echo number_format($total_withdrawn, 2); ?></p>
                        <span class="stat-label"><?php echo $stats['total_requests']; ?> total requests</span>
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-header">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-badge">Processing</div>
                    </div>
                    <div class="stat-body">
                        <h3>Pending Amount</h3>
                        <p class="stat-value">$<?php echo number_format($pending_withdrawals, 2); ?></p>
                        <span class="stat-label">Being processed</span>
                    </div>
                </div>

                <div class="stat-card available">
                    <div class="stat-header">
                        <div class="stat-icon">✅</div>
                        <div class="stat-badge">Available</div>
                    </div>
                    <div class="stat-body">
                        <h3>Current Balance</h3>
                        <p class="stat-value">$<?php echo number_format($user['balance'], 2); ?></p>
                        <span class="stat-label">Ready to withdraw</span>
                    </div>
                </div>

                <div class="stat-card completed">
                    <div class="stat-header">
                        <div class="stat-icon">🎉</div>
                        <div class="stat-badge">Success</div>
                    </div>
                    <div class="stat-body">
                        <h3>Completed</h3>
                        <p class="stat-value">$<?php echo number_format($stats['completed_amount'], 2); ?></p>
                        <span class="stat-label">Successfully paid</span>
                    </div>
                </div>
            </div>

            <!-- WITHDRAWAL HISTORY TABLE -->
            <div class="history-card">
                <div class="history-header">
                    <h3>All Withdrawal Requests</h3>
                    <div class="filter-tabs">
                        <button class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>" onclick="filterWithdrawals('all')">
                            All (<?php echo $stats['total_requests']; ?>)
                        </button>
                        <button class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>" onclick="filterWithdrawals('pending')">
                            Pending
                        </button>
                        <button class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>" onclick="filterWithdrawals('approved')">
                            Approved
                        </button>
                        <button class="filter-tab <?php echo $filter === 'completed' ? 'active' : ''; ?>" onclick="filterWithdrawals('completed')">
                            Completed
                        </button>
                        <button class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>" onclick="filterWithdrawals('rejected')">
                            Rejected
                        </button>
                    </div>
                </div>

                <div class="history-body">
                    <?php if ($withdrawals->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="withdrawal-table">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Processed Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($withdrawal = $withdrawals->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="request-id">#<?php echo str_pad($withdrawal['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <?php
                                                    // Get the appropriate date field
                                                    $dateField = null;
                                                    if (!empty($withdrawal['request_date'])) {
                                                        $dateField = $withdrawal['request_date'];
                                                    } elseif (!empty($withdrawal['created_at'])) {
                                                        $dateField = $withdrawal['created_at'];
                                                    } elseif (!empty($withdrawal['request_time'])) {
                                                        $dateField = $withdrawal['request_time'];
                                                    }
                                                    if ($dateField):
                                                ?>
                                                <span class="date"><?php echo date('M d, Y', strtotime($dateField)); ?></span>
                                                <span class="time"><?php echo date('h:i A', strtotime($dateField)); ?></span>
                                                <?php else: ?>
                                                <span class="date">—</span>
                                                <span class="time">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="amount">$<?php echo number_format($withdrawal['amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <div class="payment-method">
                                                <?php
                                                $method_icons = [
                                                    'paypal' => '💳',
                                                    'bank_transfer' => '🏦',
                                                    'mobile_money' => '📱',
                                                    'cryptocurrency' => '₿'
                                                ];
                                                $icon = $method_icons[$withdrawal['payment_method']] ?? '💰';
                                                echo $icon . ' ' . ucwords(str_replace('_', ' ', $withdrawal['payment_method']));
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $withdrawal['status']; ?>">
                                                <?php 
                                                $status_icons = [
                                                    'pending' => '⏳',
                                                    'approved' => '👍',
                                                    'completed' => '✅',
                                                    'rejected' => '❌'
                                                ];
                                                echo $status_icons[$withdrawal['status']] . ' ' . ucfirst($withdrawal['status']); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($withdrawal['processed_date']) {
                                                echo '<div class="date-info">';
                                                echo '<span class="date">' . date('M d, Y', strtotime($withdrawal['processed_date'])) . '</span>';
                                                echo '<span class="time">' . date('h:i A', strtotime($withdrawal['processed_date'])) . '</span>';
                                                echo '</div>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn-view" onclick="viewDetails(<?php echo $withdrawal['id']; ?>)">
                                                👁️ View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <div class="no-data-icon">📭</div>
                            <h3>No withdrawal requests found</h3>
                            <p>
                                <?php 
                                if ($filter === 'all') {
                                    echo "You haven't made any withdrawal requests yet.";
                                } else {
                                    echo "No " . $filter . " withdrawal requests.";
                                }
                                ?>
                            </p>
                            <button class="btn btn-primary" onclick="window.location.href='withdraw.php'">
                                Make Your First Withdrawal
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- WITHDRAWAL DETAILS MODAL -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Withdrawal Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Details will be loaded here -->
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

    <script src="assets/js/withdrawal_history.js"></script>
</body>
</html>