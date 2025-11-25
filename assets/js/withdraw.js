// Get DOM elements
const withdrawalForm = document.getElementById('withdrawalForm');
const amountInput = document.getElementById('amount');
const paymentMethodSelect = document.getElementById('paymentMethod');
const paymentDetailsContainer = document.getElementById('paymentDetailsContainer');
const amountError = document.getElementById('amountError');
const messageBox = document.getElementById('messageBox');
const submitBtn = document.getElementById('submitBtn');

// Payment method templates
const paymentTemplates = {
    paypal: `
        <div class="payment-details">
            <div class="form-group">
                <label for="paypal_email">PayPal Email Address *</label>
                <input type="email" id="paypal_email" name="paypal_email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label for="confirm_paypal_email">Confirm PayPal Email *</label>
                <input type="email" id="confirm_paypal_email" name="confirm_paypal_email" placeholder="your@email.com" required>
            </div>
        </div>
    `,
    bank_transfer: `
        <div class="payment-details">
            <div class="form-group">
                <label for="account_holder_name">Account Holder Name *</label>
                <input type="text" id="account_holder_name" name="account_holder_name" placeholder
                <input type="text" id="account_holder_name" name="account_holder_name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <label for="bank_name">Bank Name *</label>
                <input type="text" id="bank_name" name="bank_name" placeholder="Bank Name" required>
            </div>
            <div class="form-group">
                <label for="account_number">Account Number *</label>
                <input type="text" id="account_number" name="account_number" placeholder="Account Number" required>
            </div>
            <div class="form-group">
                <label for="routing_number">Routing Number / SWIFT Code *</label>
                <input type="text" id="routing_number" name="routing_number" placeholder="Routing/SWIFT Number" required>
            </div>
        </div>
    
    `
};

// Validate amount in real-time
amountInput.addEventListener('input', function() {
    validateAmount();
});

amountInput.addEventListener('blur', function() {
    validateAmount();
});

function validateAmount() {
    const amount = parseFloat(amountInput.value);
    amountError.classList.remove('show');
    amountInput.style.borderColor = '#e0e0e0';

    if (isNaN(amount) || amount <= 0) {
        amountError.textContent = 'Please enter a valid amount';
        amountError.classList.add('show');
        amountInput.style.borderColor = '#e74c3c';
        return false;
    }

    if (amount < 1.00) {
        amountError.textContent = 'Minimum withdrawal amount is $1.00';
        amountError.classList.add('show');
        amountInput.style.borderColor = '#e74c3c';
        return false;
    }

    if (amount > availableBalance) {
        amountError.textContent = `Maximum withdrawal amount is $${availableBalance.toFixed(2)}`;
        amountError.classList.add('show');
        amountInput.style.borderColor = '#e74c3c';
        return false;
    }

    amountInput.style.borderColor = '#4caf50';
    return true;
}

// Handle payment method change
paymentMethodSelect.addEventListener('change', function() {
    const selectedMethod = this.value;
    
    if (selectedMethod && paymentTemplates[selectedMethod]) {
        paymentDetailsContainer.innerHTML = paymentTemplates[selectedMethod];
    } else {
        paymentDetailsContainer.innerHTML = '';
    }
});

// Show message
function showMessage(message, type) {
    messageBox.textContent = message;
    messageBox.className = `message ${type}`;
    messageBox.style.display = 'block';
    
    // Scroll to message
    messageBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-hide after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            messageBox.style.display = 'none';
        }, 5000);
    }
}

// Form submission
withdrawalForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validate amount
    if (!validateAmount()) {
        showMessage('Please enter a valid withdrawal amount', 'error');
        return;
    }
    
    // Get form data
    const formData = new FormData(withdrawalForm);
    const amount = parseFloat(formData.get('amount'));
    const paymentMethod = formData.get('payment_method');
    
    // Validate payment method
    if (!paymentMethod) {
        showMessage('Please select a payment method', 'error');
        return;
    }
    
    // Build payment details object
    let paymentDetails = {};
    
    if (paymentMethod === 'paypal') {
        const email = formData.get('paypal_email');
        const confirmEmail = formData.get('confirm_paypal_email');
        
        if (email !== confirmEmail) {
            showMessage('PayPal email addresses do not match', 'error');
            return;
        }
        paymentDetails = { email: email };
        
    } else if (paymentMethod === 'bank_transfer') {
        paymentDetails = {
            account_holder_name: formData.get('account_holder_name'),
            bank_name: formData.get('bank_name'),
            account_number: formData.get('account_number'),
            routing_number: formData.get('routing_number')
        };
        
    } else if (paymentMethod === 'mobile_money') {
        paymentDetails = {
            provider: formData.get('mobile_provider'),
            mobile_number: formData.get('mobile_number'),
            account_name: formData.get('account_name')
        };
        
    } else if (paymentMethod === 'cryptocurrency') {
        const wallet = formData.get('wallet_address');
        const confirmWallet = formData.get('confirm_wallet_address');
        
        if (wallet !== confirmWallet) {
            showMessage('Wallet addresses do not match', 'error');
            return;
        }
        
        paymentDetails = {
            crypto_type: formData.get('crypto_type'),
            wallet_address: wallet
        };
    }
    
    // Check terms agreement
    if (!document.getElementById('agreeTerms').checked) {
        showMessage('Please agree to the terms and conditions', 'error');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Processing...';
    
    try {
        // Send request to server
        const response = await fetch('process_withdrawal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                amount: amount,
                payment_method: paymentMethod,
                payment_details: paymentDetails
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            
            // Reset form
            withdrawalForm.reset();
            paymentDetailsContainer.innerHTML = '';
            
            // Update available balance display
            const newBalance = availableBalance - amount;
            document.getElementById('availableBalance').textContent = `$${newBalance.toFixed(2)}`;
            
            // Reload page after 2 seconds to show updated history
            setTimeout(() => {
                window.location.reload();
            }, 2000);
            
        } else {
            showMessage(result.message || 'Failed to process withdrawal request', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showMessage('An error occurred. Please try again later.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="btn-icon">💸</span> Submit Withdrawal Request';
    }
});

// Format amount input on blur
amountInput.addEventListener('blur', function() {
    if (this.value) {
        const amount = parseFloat(this.value);
        if (!isNaN(amount)) {
            this.value = amount.toFixed(2);
        }
    }
});