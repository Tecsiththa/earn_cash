document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const messageDiv = document.getElementById('message');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Get form data
    const formData = new FormData(this);
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';
    
    try {
        const response = await fetch('login_process.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        messageDiv.textContent = data.message;
        messageDiv.className = 'message ' + (data.success ? 'success' : 'error');
        
        if (data.success) {
            // Redirect based on role
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Login';
        }
    } catch (error) {
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.className = 'message error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
    }
});