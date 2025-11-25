document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const messageDiv = document.getElementById('message');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Get form data
    const formData = new FormData(this);
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';
    
    try {
        const response = await fetch('signup_process.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        messageDiv.textContent = data.message;
        messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
        
        if (data.success) {
            // Redirect to login after 2 seconds
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign Up';
        }
    } catch (error) {
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.className = 'message error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign Up';
    }
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});