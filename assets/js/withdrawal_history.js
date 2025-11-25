// Filter withdrawals
function filterWithdrawals(status) {
    window.location.href = `withdrawal_history.php?filter=${status}`;
}

// View withdrawal details
async function viewDetails(withdrawalId) {
    try {
        const response = await fetch(`get_withdrawal_details.php?id=${withdrawalId}`);
        const result = await response.json();
        
        if (result.success) {
            const withdrawal = result.data;
            const paymentDetails = JSON.parse(withdrawal.payment_details);
            
            // Build modal content
            let modalContent = `
                <div class="detail-section">
                    <h4>Request Information</h4>
                    <div class="detail-row">
                        <span class="detail-label">Request ID:</span>
                        <span class="detail-value">#${String(withdrawal.id).padStart(6, '0')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount:</span>
                        <span class="detail-value" style="color: #4caf50; font-weight: bold;">$${parseFloat(withdrawal.amount).toFixed(2)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge ${withdrawal.status}">
                                ${getStatusIcon(withdrawal.status)} ${capitalizeFirst(withdrawal.status)}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Request Date:</span>
                        <span class="detail-value">${formatDateTime(withdrawal.request_date)}</span>
                    </div>
                    ${withdrawal.processed_date ? `
                    <div class="detail-row">
                        <span class="detail-label">Processed Date:</span>
                        <span class="detail-value">${formatDateTime(withdrawal.processed_date)}</span>
                    </div>
                    ` : ''}
                    ${withdrawal.transaction_id ? `
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value">${withdrawal.transaction_id}</span>
                    </div>
                    ` : ''}
                </div>

                <div class="detail-section">
                    <h4>Payment Method</h4>
                    <div class="detail-row">
                        <span class="detail-label">Method:</span>
                        <span class="detail-value">${getPaymentMethodIcon(withdrawal.payment_method)} ${capitalizeWords(withdrawal.payment_method.replace('_', ' '))}</span>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Payment Details</h4>
                    <div class="payment-details-list">
                        ${formatPaymentDetails(withdrawal.payment_method, paymentDetails)}
                    </div>
                </div>

                ${withdrawal.admin_notes ? `
                <div class="detail-section">
                    <h4>Admin Notes</h4>
                    <div class="admin-notes">
                        <p>${withdrawal.admin_notes}</p>
                    </div>
                </div>
                ` : ''}
            `;
            
            // Display modal
            document.getElementById('modalBody').innerHTML = modalContent;
            document.getElementById('detailsModal').classList.add('show');
            
        } else {
            alert('Failed to load withdrawal details');
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while loading details');
    }
}

// Close modal
function closeModal() {
    document.getElementById('detailsModal').classList.remove('show');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Helper function: Get status icon
function getStatusIcon(status) {
    const icons = {
        'pending': '⏳',
        'approved': '👍',
        'completed': '✅',
        'rejected': '❌'
    };
    return icons[status] || '📋';
}

// Helper function: Get payment method icon
function getPaymentMethodIcon(method) {
    const icons = {
        'paypal': '💳',
        'bank_transfer': '🏦',
        'mobile_money': '📱',
        'cryptocurrency': '₿'
    };
    return icons[method] || '💰';
}

// Helper function: Capitalize first letter
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Helper function: Capitalize words
function capitalizeWords(str) {
    return str.split(' ').map(word => capitalizeFirst(word)).join(' ');
}

// Helper function: Format date and time
function formatDateTime(datetime) {
    const date = new Date(datetime);
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

// Helper function: Format payment details based on method
function formatPaymentDetails(method, details) {
    let html = '';
    
    if (method === 'paypal') {
        html = `<p><strong>PayPal Email:</strong> ${details.email}</p>`;
        
    } else if (method === 'bank_transfer') {
        html = `
            <p><strong>Account Holder:</strong> ${details.account_holder_name}</p>
            <p><strong>Bank Name:</strong> ${details.bank_name}</p>
            <p><strong>Account Number:</strong> ${details.account_number}</p>
            <p><strong>Routing/SWIFT:</strong> ${details.routing_number}</p>
        `;
        
    } else if (method === 'mobile_money') {
        html = `
            <p><strong>Provider:</strong> ${capitalizeWords(details.provider)}</p>
            <p><strong>Mobile Number:</strong> ${details.mobile_number}</p>
            <p><strong>Account Name:</strong> ${details.account_name}</p>
        `;
        
    } else if (method === 'cryptocurrency') {
        html = `
            <p><strong>Cryptocurrency:</strong> ${capitalizeWords(details.crypto_type)}</p>
            <p><strong>Wallet Address:</strong> <code style="word-break: break-all;">${details.wallet_address}</code></p>
        `;
    }
    
    return html;
}

// Add smooth scroll behavior
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

// Auto-refresh page every 30 seconds if there are pending withdrawals
if (document.querySelector('.status-badge.pending')) {
    setTimeout(() => {
        window.location.reload();
    }, 30000);
}