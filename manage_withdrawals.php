<?php
// ============================================
// MANAGE WITHDRAWALS PAGE
// ============================================
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// Get all withdrawals
$stmt = $conn->prepare("
    SELECT 
        w.id,
        w.user_id,
        u.username,
        u.email,
        w.amount,
        w.payment_method,
        w.payment_details,
        w.status,
        w.requested_at,
        w.processed_at,
        u.balance as user_balance
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    ORDER BY 
        CASE w.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        w.requested_at DESC
");
$stmt->execute();
$withdrawals = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Withdrawals - EarnCash Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo"><h2>EarnCash Admin</h2></div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="manage_users.php" class="nav-item"><span class="icon">👥</span><span>Users</span></a>
            <a href="manage_ads.php" class="nav-item"><span class="icon">📺</span><span>Advertisements</span></a>
            <a href="manage_withdrawals.php" class="nav-item active"><span class="icon">💰</span><span>Withdrawals</span></a>
            <a href="logout.php" class="nav-item logout"><span class="icon">🚪</span><span>Logout</span></a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Manage Withdrawals</h1>
                <p>Process withdrawal requests</p>
            </div>
        </header>

        <div class="card">
            <div class="card-header">
                <h3>All Withdrawal Requests</h3>
                <select id="filterStatus" style="padding: 0.5rem; border: 2px solid #ecf0f1; border-radius: 8px;">
                    <option value="all">All Status</option>
                    <option value="pending" selected>Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>User Balance</th>
                            <th>Method</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($w = $withdrawals->fetch_assoc()): ?>
                        <tr data-status="<?php echo $w['status']; ?>">
                            <td><?php echo $w['id']; ?></td>
                            <td><?php echo htmlspecialchars($w['username']); ?></td>
                            <td><?php echo htmlspecialchars($w['email']); ?></td>
                            <td>$<?php echo number_format($w['amount'], 2); ?></td>
                            <td>$<?php echo number_format($w['user_balance'], 2); ?></td>
                            <td><?php echo htmlspecialchars($w['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars(substr($w['payment_details'], 0, 30)); ?></td>
                            <td><span class="status-badge <?php echo $w['status']; ?>"><?php echo ucfirst($w['status']); ?></span></td>
                            <td><?php echo date('M d, Y H:i', strtotime($w['requested_at'])); ?></td>
                            <td>
                                <?php if ($w['status'] === 'pending'): ?>
                                    <button class="btn-approve" onclick="handleWithdrawal(<?php echo $w['id']; ?>, 'approve')">Approve</button>
                                    <button class="btn-reject" onclick="handleWithdrawal(<?php echo $w['id']; ?>, 'reject')">Reject</button>
                                <?php else: ?>
                                    <span style="color: #999;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
    <script>
        // Filter by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                row.style.display = (status === 'all' || status === rowStatus) ? '' : 'none';
            });
        });
        
        // Trigger filter on load
        document.getElementById('filterStatus').dispatchEvent(new Event('change'));
    </script>
</body>
</html>