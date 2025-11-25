// View User Details
async function viewUser(userId) {
    try {
        const response = await fetch(`get_user_details.php?user_id=${userId}`);
        
        // Log the raw response for debugging
        const text = await response.text();
        console.log('Raw server response:', text);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Invalid JSON response:', text);
            alert('Server returned invalid JSON. Check console for details.');
            return;
        }
        
        if (result.success) {
            displayUserDetails(result.data);
            document.getElementById('userModal').style.display = 'block';
        } else {
            console.error('API Error:', result.message);
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
        
    } catch (error) {
        console.error('Network Error:', error);
        alert('Failed to connect to the server. Please try again.');
    }
}

// Display User Details in Modal
function displayUserDetails(user) {
    const modalBody = document.getElementById('userModalBody');
    
    // Calculate max value for chart
    const maxValue = Math.max(...user.chart_data.map(d => d.amount), 1);
    
    const chartBars = user.chart_data.map(data => {
        const height = (data.amount / maxValue) * 100;
        return `
            <div class="chart-bar" style="height: ${Math.max(height, 10)}%">
                <span class="bar-value">$${data.amount.toFixed(2)}</span>
                <span class="bar-label">${data.day}</span>
            </div>
        `;
    }).join('');
    
    const recentViews = user.recent_views.length > 0 
        ? user.recent_views.map(view => `
            <li class="activity-item">
                <div>
                    <strong>${view.ad_title || 'Unknown Ad'}</strong>
                    <div class="date">${formatDateTime(view.viewed_at)}</div>
                </div>
                <div class="amount">+$${parseFloat(view.reward_earned).toFixed(2)}</div>
            </li>
        `).join('')
        : '<div class="no-data">No recent activity</div>';
    
    const recentWithdrawals = user.recent_withdrawals.length > 0
        ? user.recent_withdrawals.map(w => `
            <div class="withdrawal-item">
                <div>
                    <strong>$${parseFloat(w.amount).toFixed(2)}</strong>
                    <div class="date">${formatDateTime(w.request_date)}</div>
                </div>
                <span class="status-badge ${w.status}">${capitalizeFirst(w.status)}</span>
            </div>
        `).join('')
        : '<div class="no-data">No withdrawal requests</div>';
    
    modalBody.innerHTML = `
        <div class="user-info-grid">
            <div class="info-card">
                <h4>Username</h4>
                <p>${user.username}</p>
            </div>
            <div class="info-card">
                <h4>Email</h4>
                <p style="font-size: 1rem;">${user.email}</p>
            </div>
            <div class="info-card">
                <h4>User ID</h4>
                <p>#${user.id}</p>
            </div>
            <div class="info-card">
                <h4>Role</h4>
                <p>${capitalizeFirst(user.role)}</p>
            </div>
            <div class="info-card">
                <h4>Member Since</h4>
                <p style="font-size: 1rem;">${formatDate(user.created_at)}</p>
            </div>
            <div class="info-card">
                <h4>Last Login</h4>
                <p style="font-size: 1rem;">${user.last_login ? formatDate(user.last_login) : 'Never'}</p>
            </div>
        </div>
        
        <h3 class="section-title">💰 Financial Overview</h3>
        <div class="stats-grid">
            <div class="stat-box">
                <h5>Current Balance</h5>
                <p>$${parseFloat(user.balance).toFixed(2)}</p>
            </div>
            <div class="stat-box">
                <h5>Total Earned</h5>
                <p>$${parseFloat(user.total_earned).toFixed(2)}</p>
            </div>
            <div class="stat-box">
                <h5>Today's Earnings</h5>
                <p>$${parseFloat(user.today_earned).toFixed(2)}</p>
            </div>
            <div class="stat-box">
                <h5>Ads Today</h5>
                <p>${user.ads_today}/10</p>
            </div>
        </div>
        
        <h3 class="section-title">📊 Activity Statistics</h3>
        <div class="stats-grid">
            <div class="stat-box">
                <h5>Total Ad Views</h5>
                <p>${user.total_views}</p>
            </div>
            <div class="stat-box">
                <h5>Active Days</h5>
                <p>${user.active_days}</p>
            </div>
            <div class="stat-box">
                <h5>Withdrawal Requests</h5>
                <p>${user.withdrawal_stats.total_requests}</p>
            </div>
            <div class="stat-box">
                <h5>Completed Withdrawals</h5>
                <p>$${parseFloat(user.withdrawal_stats.completed_amount).toFixed(2)}</p>
            </div>
        </div>
        
        <h3 class="section-title">📈 Earnings Chart (Last 7 Days)</h3>
        <div class="chart-container">
            <div class="chart-bars">
                ${chartBars}
            </div>
        </div>
        
        <h3 class="section-title">🎬 Recent Ad Views</h3>
        <ul class="activity-list">
            ${recentViews}
        </ul>
        
        <h3 class="section-title">💸 Recent Withdrawals</h3>
        <div>
            ${recentWithdrawals}
        </div>
    `;
}

// Close User Modal
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
}

// Delete User
async function deleteUser(userId, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"?\n\nThis action cannot be undone and will delete:\n- All ad views\n- All earnings records\n- All withdrawal requests\n- The user account`)) {
        return;
    }
    
    // Double confirmation
    if (!confirm('Are you absolutely sure? This action is PERMANENT!')) {
        return;
    }
    
    try {
        const response = await fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while deleting the user');
    }
}

// Search functionality
document.getElementById('searchUsers').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    const dateStr = date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
    const timeStr = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    return `${dateStr} at ${timeStr}`;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}