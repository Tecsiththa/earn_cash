<?php
// ============================================
// MANAGE USERS PAGE
// ============================================
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// Get all customers with statistics
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.balance,
        u.created_at,
        u.last_login,
        COUNT(DISTINCT av.id) as total_views,
        COALESCE(SUM(av.reward_earned), 0) as total_earned
    FROM users u
    LEFT JOIN ad_views av ON u.id = av.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EarnCash Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <style>
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-view { background: #3498db; color: white; padding: 0.4rem 0.8rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-delete { background: #e74c3c; color: white; padding: 0.4rem 0.8rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-view:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo"><h2>EarnCash Admin</h2></div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item"><span class="icon">📊</span><span>Dashboard</span></a>
            <a href="manage_users.php" class="nav-item active"><span class="icon">👥</span><span>Users</span></a>
            <a href="manage_ads.php" class="nav-item"><span class="icon">📺</span><span>Advertisements</span></a>
            <a href="manage_withdrawals.php" class="nav-item"><span class="icon">💰</span><span>Withdrawals</span></a>
            <a href="logout.php" class="nav-item logout"><span class="icon">🚪</span><span>Logout</span></a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Manage Users</h1>
                <p>View and manage all registered users</p>
            </div>
        </header>

        <div class="card">
            <div class="card-header">
                <h3>All Users</h3>
                <input type="text" id="searchUsers" placeholder="Search users..." style="padding: 0.5rem; border: 2px solid #ecf0f1; border-radius: 8px;">
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Total Earned</th>
                            <th>Views</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>$<?php echo number_format($user['balance'], 2); ?></td>
                            <td>$<?php echo number_format($user['total_earned'], 2); ?></td>
                            <td><?php echo $user['total_views']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td class="user-actions">
                                <button class="btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View</button>
                                <button class="btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
   



<script src="assets/js/admin_user_management.js"></script>
</body>
</html>