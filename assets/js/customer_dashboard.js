// ============================================
// CUSTOMER DASHBOARD JAVASCRIPT
// ============================================

// ============================================
// AUTO-REFRESH STATS EVERY 30 SECONDS
// ============================================
setInterval(function() {
    refreshDashboard();
}, 30000);

// ============================================
// REFRESH DASHBOARD DATA
// ============================================
function refreshDashboard() {
    fetch('get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update today's earnings
                document.getElementById('todayEarnings').textContent = '$' + parseFloat(data.today_earnings).toFixed(2);
                
                // Update balance
                document.getElementById('userBalance').textContent = '$' + parseFloat(data.balance).toFixed(2);
                
                // Update total earned
                document.getElementById('totalEarned').textContent = '$' + parseFloat(data.total_earned).toFixed(2);
                
                // Update ads remaining
                document.getElementById('adsRemaining').textContent = data.ads_remaining + '/10';
            }
        })
        .catch(error => console.error('Error refreshing dashboard:', error));
}

// ============================================
// CHANGE CHART PERIOD
// ============================================
function changePeriod(days) {
    const chartBars = document.getElementById('chartBars');
    chartBars.style.opacity = '0.5';
    
    fetch('get_chart_data.php?days=' + days)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateChart(data.chart_data);
            }
            chartBars.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error loading chart:', error);
            chartBars.style.opacity = '1';
        });
}

// ============================================
// UPDATE CHART WITH NEW DATA
// ============================================
function updateChart(chartData) {
    const chartBars = document.getElementById('chartBars');
    
    // Find max value for scaling
    const maxValue = Math.max(...chartData.map(d => d.amount));
    const scaledMax = maxValue > 0 ? maxValue : 1;
    
    // Clear existing bars
    chartBars.innerHTML = '';
    
    // Create new bars
    chartData.forEach(data => {
        const height = (data.amount / scaledMax) * 100;
        const adjustedHeight = Math.max(height, 5); // Minimum 5%
        
        const barDiv = document.createElement('div');
        barDiv.className = 'chart-bar';
        barDiv.style.height = adjustedHeight + '%';
        
        const valueSpan = document.createElement('span');
        valueSpan.className = 'bar-value';
        valueSpan.textContent = '$' + parseFloat(data.amount).toFixed(2);
        
        const labelSpan = document.createElement('span');
        labelSpan.className = 'bar-label';
        labelSpan.textContent = data.day;
        
        barDiv.appendChild(valueSpan);
        barDiv.appendChild(labelSpan);
        chartBars.appendChild(barDiv);
    });
}

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
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
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
// ANIMATE ELEMENTS ON SCROLL
// ============================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeIn 0.6s ease forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all cards and stat cards
document.querySelectorAll('.stat-card, .card').forEach(el => {
    el.style.opacity = '0';
    observer.observe(el);
});

// ============================================
// CONFIRM LOGOUT
// ============================================
document.querySelector('.logout-btn')?.addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to logout?')) {
        e.preventDefault();
    }
});

// ============================================
// ADD HOVER EFFECTS TO STAT CARDS
// ============================================
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// ============================================
// HANDLE NETWORK STATUS
// ============================================
window.addEventListener('online', () => {
    showNotification('Connection restored', 'success');
    refreshDashboard();
});

window.addEventListener('offline', () => {
    showNotification('No internet connection', 'error');
});

// ============================================
// INITIALIZE ON PAGE LOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customer Dashboard Loaded Successfully');
    
    // Add click animation to all buttons
    document.querySelectorAll('.btn, .btn-watch, .btn-withdraw').forEach(btn => {
        btn.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// ============================================
// PREVENT MULTIPLE SUBMISSIONS
// ============================================
let isSubmitting = false;

function handleFormSubmit(event) {
    if (isSubmitting) {
        event.preventDefault();
        return false;
    }
    isSubmitting = true;
    setTimeout(() => {
        isSubmitting = false;
    }, 3000);
    return true;
}

// ============================================
// UPDATE ACTIVITY TIME DYNAMICALLY
// ============================================
function updateTimeAgo() {
    document.querySelectorAll('.activity-time').forEach(el => {
        // Would need original timestamp stored in data attribute
        // For now, this maintains the static display
    });
}

setInterval(updateTimeAgo, 60000); // Update every minute