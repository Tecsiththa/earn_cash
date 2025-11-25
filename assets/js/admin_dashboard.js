// ============================================
// ADMIN DASHBOARD JAVASCRIPT
// ============================================

// ============================================
// AUTO-REFRESH DASHBOARD EVERY 30 SECONDS
// ============================================
setInterval(function() {
    refreshDashboardStats();
}, 30000);

// ============================================
// REFRESH DASHBOARD STATISTICS
// ============================================
function refreshDashboardStats() {
    fetch('get_admin_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalUsers').textContent = formatNumber(data.total_users);
                document.getElementById('totalAds').textContent = data.total_ads;
                document.getElementById('totalViews').textContent = formatNumber(data.total_views);
                document.getElementById('pendingWithdrawals').textContent = data.pending_withdrawals;
                
                // Update notification badge
                const badge = document.querySelector('.badge');
                if (data.pending_withdrawals > 0) {
                    if (badge) {
                        badge.textContent = data.pending_withdrawals;
                    }
                }
            }
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

// ============================================
// HANDLE WITHDRAWAL APPROVAL/REJECTION
// ============================================
function handleWithdrawal(withdrawalId, action) {
    if (!confirm(`Are you sure you want to ${action} this withdrawal?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('withdrawal_id', withdrawalId);
    formData.append('action', action);
    
    fetch('process_withdrawal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Reload page after 1 second
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

// ============================================
// CHANGE REVENUE CHART PERIOD
// ============================================
function changeRevenuePeriod(days) {
    const chart = document.getElementById('revenueChart');
    chart.style.opacity = '0.5';
    
    fetch('get_revenue_data.php?days=' + days)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRevenueChart(data.revenue_data);
            }
            chart.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error loading chart:', error);
            chart.style.opacity = '1';
        });
}

// ============================================
// UPDATE REVENUE CHART
// ============================================
function updateRevenueChart(revenueData) {
    const chart = document.getElementById('revenueChart');
    
    // Find max value for scaling
    const maxValue = Math.max(...revenueData.map(d => d.amount));
    const scaledMax = maxValue > 0 ? maxValue : 1;
    
    // Clear existing bars
    chart.innerHTML = '';
    
    // Create new bars
    revenueData.forEach(data => {
        const height = (data.amount / scaledMax) * 100;
        const adjustedHeight = Math.max(height, 5);
        
        const barDiv = document.createElement('div');
        barDiv.className = 'bar';
        barDiv.style.height = adjustedHeight + '%';
        
        const span = document.createElement('span');
        span.textContent = data.day;
        
        barDiv.appendChild(span);
        chart.appendChild(barDiv);
    });
}

// ============================================
// SEARCH FUNCTIONALITY
// ============================================
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    if (searchTerm.length < 2) return;
    
    // Search in users table
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// ============================================
// SHOW NOTIFICATION
// ============================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// FORMAT NUMBER WITH COMMAS
// ============================================
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// ============================================
// ADD ANIMATION STYLES
// ============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

// ============================================
// ANIMATE ELEMENTS ON LOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Dashboard Loaded');
    
    // Animate stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.animation = 'fadeIn 0.6s ease forwards';
        }, index * 100);
    });
    
    // Animate cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.animation = 'fadeIn 0.6s ease forwards';
        }, (statCards.length * 100) + (index * 100));
    });
    
    // Add hover effects to stat cards
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add hover effects to buttons
    document.querySelectorAll('.btn-approve, .btn-reject').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// ============================================
// CONFIRM LOGOUT
// ============================================
document.querySelector('.nav-item.logout')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
});

// ============================================
// HIGHLIGHT ACTIVE NAV ITEM
// ============================================
const currentPage = window.location.pathname.split('/').pop();
document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && href.includes(currentPage)) {
        item.classList.add('active');
    } else if (item.classList.contains('active') && !href.includes(currentPage)) {
        item.classList.remove('active');
    }
});

// ============================================
// TABLE ROW CLICK TO VIEW DETAILS
// ============================================
document.querySelectorAll('.data-table tbody tr').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function(e) {
        // Don't trigger if clicking button
        if (e.target.tagName === 'BUTTON') return;
        
        this.style.background = '#f8f9fa';
        setTimeout(() => {
            this.style.background = '';
        }, 300);
    });
});

// ============================================
// REAL-TIME CLOCK
// ============================================
function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit'
    });
    
    // You can add this to header if needed
    // document.querySelector('.clock')?.textContent = time;
}

setInterval(updateClock, 1000);

// ============================================
// KEYBOARD SHORTCUTS
// ============================================
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search focus
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
    }
});

// ============================================
// EXPORT DATA FUNCTIONALITY
// ============================================
function exportTableToCSV(tableClass, filename) {
    const table = document.querySelector(`.${tableClass}`);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => col.textContent.trim());
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// ============================================
// HANDLE NETWORK STATUS
// ============================================
window.addEventListener('online', () => {
    showNotification('Connection restored', 'success');
    refreshDashboardStats();
});

window.addEventListener('offline', () => {
    showNotification('No internet connection', 'error');
});