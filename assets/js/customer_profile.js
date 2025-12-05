// ============================================
// USER PROFILE - JAVASCRIPT
// ============================================

// Edit Profile Modal
function openEditProfileModal() {
    document.getElementById('editProfileModal').classList.add('show');
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').classList.remove('show');
    document.getElementById('editProfileForm').reset();
    hideMessage('profileMessage');
}

// Change Password Modal
function openChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.add('show');
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').classList.remove('show');
    document.getElementById('changePasswordForm').reset();
    hideMessage('passwordMessage');
}

// Change Email Modal
function openChangeEmailModal() {
    document.getElementById('changeEmailModal').classList.add('show');
}

function closeChangeEmailModal() {
    document.getElementById('changeEmailModal').classList.remove('show');
    document.getElementById('changeEmailForm').reset();
    hideMessage('emailMessage');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = ['editProfileModal', 'changePasswordModal', 'changeEmailModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => modal.classList.remove('show'));
    }
});

// Show Message
function showMessage(elementId, message, type) {
    const messageBox = document.getElementById(elementId);
    messageBox.textContent = message;
    messageBox.className = `message ${type}`;
    messageBox.style.display = 'block';
    
    // Auto-hide success messages after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            hideMessage(elementId);
        }, 3000);
    }
}

// Hide Message
function hideMessage(elementId) {
    const messageBox = document.getElementById(elementId);
    messageBox.style.display = 'none';
}

// ============================================
// EDIT PROFILE FORM SUBMISSION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const editProfileForm = document.getElementById('editProfileForm');
    
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('edit_username').value.trim();
            
            // Validate
            if (!username) {
                showMessage('profileMessage', 'Please enter a username', 'error');
                return;
            }
            
            if (username.length < 3) {
                showMessage('profileMessage', 'Username must be at least 3 characters', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = e.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username: username })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('profileMessage', result.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('profileMessage', result.message, 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showMessage('profileMessage', 'An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// ============================================
// CHANGE PASSWORD FORM SUBMISSION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const changePasswordForm = document.getElementById('changePasswordForm');
    
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validate
            if (!currentPassword || !newPassword || !confirmPassword) {
                showMessage('passwordMessage', 'All fields are required', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                showMessage('passwordMessage', 'New password must be at least 6 characters', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showMessage('passwordMessage', 'New passwords do not match', 'error');
                return;
            }
            
            if (currentPassword === newPassword) {
                showMessage('passwordMessage', 'New password must be different from current password', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = e.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Changing...';
            
            try {
                const response = await fetch('change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('passwordMessage', result.message, 'success');
                    changePasswordForm.reset();
                    setTimeout(() => {
                        closeChangePasswordModal();
                    }, 2000);
                } else {
                    showMessage('passwordMessage', result.message, 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showMessage('passwordMessage', 'An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// ============================================
// CHANGE EMAIL FORM SUBMISSION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const changeEmailForm = document.getElementById('changeEmailForm');
    
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newEmail = document.getElementById('new_email').value.trim();
            const password = document.getElementById('password_confirm').value;
            
            // Validate
            if (!newEmail || !password) {
                showMessage('emailMessage', 'All fields are required', 'error');
                return;
            }
            
            if (!isValidEmail(newEmail)) {
                showMessage('emailMessage', 'Please enter a valid email address', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = e.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Changing...';
            
            try {
                const response = await fetch('change_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        new_email: newEmail,
                        password: password
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('emailMessage', result.message, 'success');
                    changeEmailForm.reset();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('emailMessage', result.message, 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showMessage('emailMessage', 'An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// Helper function to validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Real-time password match validation
document.addEventListener('DOMContentLoaded', function() {
    const confirmPassword = document.getElementById('confirm_password');
    const newPassword = document.getElementById('new_password');
    
    if (confirmPassword && newPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#27ae60';
            }
        });
    }
});

console.log('user_profile.js loaded successfully');