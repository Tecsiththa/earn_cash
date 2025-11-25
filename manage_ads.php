<?php
// ============================================
// MANAGE ADVERTISEMENTS PAGE
// ============================================
require_once 'config.php';
requireAdmin();

$conn = getDBConnection();

// Get all advertisements with statistics
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.title,
        a.description,
        a.url,
        a.video_url,
        a.image_url,
        a.reward,
        a.duration,
        a.minimum_watch_time,
        a.is_active,
        a.created_at,
        COUNT(DISTINCT av.id) as total_views,
        COALESCE(SUM(av.reward_earned), 0) as total_paid,
        COUNT(DISTINCT av.user_id) as unique_viewers
    FROM advertisements a
    LEFT JOIN ad_views av ON a.id = av.ad_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$stmt->execute();
$advertisements = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Advertisements - EarnCash Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <style>
        .ad-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn-toggle {
            background: #f39c12;
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
        .btn-toggle:hover { background: #d68910; }
        
        .btn-add-new {
            background: #27ae60;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
        }
        .btn-add-new:hover {
            background: #229954;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover {
            opacity: 0.8;
        }
        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .ad-thumbnail {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .ad-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo">
            <h2>EarnCash Admin</h2>
        </div>
        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="manage_users.php" class="nav-item">
                <span class="icon">👥</span>
                <span>Users</span>
            </a>
            <a href="manage_ads.php" class="nav-item active">
                <span class="icon">📺</span>
                <span>Advertisements</span>
            </a>
            <a href="manage_withdrawals.php" class="nav-item">
                <span class="icon">💰</span>
                <span>Withdrawals</span>
            </a>
            <a href="logout.php" class="nav-item logout">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Manage Advertisements</h1>
                <p>Create and manage video advertisements</p>
            </div>
            <div class="header-right">
                <button class="btn-add-new" onclick="openAddModal()">
                    ➕ Add New Advertisement
                </button>
            </div>
        </header>

        <!-- STATISTICS CARDS -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-icon">📺</div>
                <div class="stat-details">
                    <h3>Total Ads</h3>
                    <p class="stat-number"><?php echo $advertisements->num_rows; ?></p>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-details">
                    <h3>Active Ads</h3>
                    <p class="stat-number">
                        <?php 
                        $advertisements->data_seek(0);
                        $active_count = 0;
                        while ($ad = $advertisements->fetch_assoc()) {
                            if ($ad['is_active']) $active_count++;
                        }
                        echo $active_count;
                        $advertisements->data_seek(0);
                        ?>
                    </p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">👁️</div>
                <div class="stat-details">
                    <h3>Total Views</h3>
                    <p class="stat-number">
                        <?php 
                        $advertisements->data_seek(0);
                        $total_views = 0;
                        while ($ad = $advertisements->fetch_assoc()) {
                            $total_views += $ad['total_views'];
                        }
                        echo number_format($total_views);
                        $advertisements->data_seek(0);
                        ?>
                    </p>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">💰</div>
                <div class="stat-details">
                    <h3>Total Paid</h3>
                    <p class="stat-number">
                        <?php 
                        $advertisements->data_seek(0);
                        $total_paid = 0;
                        while ($ad = $advertisements->fetch_assoc()) {
                            $total_paid += $ad['total_paid'];
                        }
                        echo '$' . number_format($total_paid, 2);
                        $advertisements->data_seek(0);
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- ADVERTISEMENTS TABLE -->
        <div class="card">
            <div class="card-header">
                <h3>All Advertisements</h3>
                <input type="text" id="searchAds" placeholder="Search ads..." 
                    style="padding: 0.5rem; border: 2px solid #ecf0f1; border-radius: 8px;">
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thumbnail</th>
                            <th>Title</th>
                            <th>Reward</th>
                            <th>Duration</th>
                            <th>Views</th>
                            <th>Unique Users</th>
                            <th>Total Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ad = $advertisements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $ad['id']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($ad['image_url'] ?? 'https://via.placeholder.com/80x60'); ?>" 
                                     alt="Thumbnail" class="ad-thumbnail">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($ad['title']); ?></strong><br>
                                <small style="color: #999;"><?php echo htmlspecialchars(substr($ad['description'], 0, 50)) . '...'; ?></small>
                            </td>
                            <td>$<?php echo number_format($ad['reward'], 2); ?></td>
                            <td><?php echo $ad['duration']; ?>s</td>
                            <td><?php echo number_format($ad['total_views']); ?></td>
                            <td><?php echo number_format($ad['unique_viewers']); ?></td>
                            <td>$<?php echo number_format($ad['total_paid'], 2); ?></td>
                            <td>
                                <span class="<?php echo $ad['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $ad['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="ad-actions">
                                <button class="btn-edit" onclick="editAd(<?php echo htmlspecialchars(json_encode($ad), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <button class="btn-toggle" onclick="toggleAdStatus(<?php echo $ad['id']; ?>, <?php echo $ad['is_active'] ? 'false' : 'true'; ?>)">
                                    <?php echo $ad['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button class="btn-delete" onclick="deleteAd(<?php echo $ad['id']; ?>, '<?php echo htmlspecialchars(addslashes($ad['title'])); ?>')">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD/EDIT MODAL -->
    <div id="adModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Advertisement</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="adForm">
                    <input type="hidden" id="ad_id" name="ad_id">
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">Video URL *</label>
                        <input type="url" id="video_url" name="video_url" required 
                            placeholder="http://example.com/video.mp4">
                    </div>
                    
                    <div class="form-group">
                        <label for="image_url">Thumbnail Image URL</label>
                        <input type="url" id="image_url" name="image_url" 
                            placeholder="http://example.com/thumbnail.jpg">
                    </div>
                    
                    <div class="form-group">
                        <label for="url">Landing Page URL *</label>
                        <input type="url" id="url" name="url" required 
                            placeholder="http://example.com">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reward">Reward Amount ($) *</label>
                            <input type="number" id="reward" name="reward" step="0.01" 
                                min="0.01" value="0.05" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">Duration (seconds) *</label>
                            <input type="number" id="duration" name="duration" 
                                min="1" value="30" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="minimum_watch_time">Min Watch Time (seconds) *</label>
                            <input type="number" id="minimum_watch_time" name="minimum_watch_time" 
                                min="1" value="30" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="is_active">Status *</label>
                            <select id="is_active" name="is_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Save Advertisement</button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/manage_ads.js"></script>
</body>
</html>