// ============================================
// MANAGE ADVERTISEMENTS - JAVASCRIPT
// ============================================

// Open Add Modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Advertisement';
    document.getElementById('adForm').reset();
    document.getElementById('ad_id').value = '';
    document.getElementById('reward').value = '0.05';
    document.getElementById('duration').value = '30';
    document.getElementById('minimum_watch_time').value = '30';
    document.getElementById('is_active').value = '1';
    document.getElementById('adModal').style.display = 'block';
}

// Edit Advertisement Function
function editAd(ad) {
    console.log('Editing ad:', ad); // Debug
    
    // Set modal title to Edit mode
    document.getElementById('modalTitle').textContent = 'Edit Advertisement';
    
    // Populate form fields with existing data
    document.getElementById('ad_id').value = ad.id;
    document.getElementById('title').value = ad.title;
    document.getElementById('description').value = ad.description;
    document.getElementById('url').value = ad.url;
    document.getElementById('video_url').value = ad.video_url;
    document.getElementById('image_url').value = ad.image_url || '';
    document.getElementById('reward').value = parseFloat(ad.reward);
    document.getElementById('duration').value = parseInt(ad.duration);
    document.getElementById('minimum_watch_time').value = parseInt(ad.minimum_watch_time);
    document.getElementById('is_active').value = ad.is_active ? '1' : '0';
    
    // Show the modal
    document.getElementById('adModal').style.display = 'block';
}

// Close Modal
function closeModal() {
    document.getElementById('adModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('adModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('adModal');
        if (modal && modal.style.display === 'block') {
            closeModal();
        }
    }
});

// Form submission (handles both add and edit)
document.addEventListener('DOMContentLoaded', function() {
    const adForm = document.getElementById('adForm');
    
    if (adForm) {
        adForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = {
                ad_id: document.getElementById('ad_id').value,
                title: document.getElementById('title').value.trim(),
                description: document.getElementById('description').value.trim(),
                url: document.getElementById('url').value.trim(),
                video_url: document.getElementById('video_url').value.trim(),
                image_url: document.getElementById('image_url').value.trim(),
                reward: parseFloat(document.getElementById('reward').value),
                duration: parseInt(document.getElementById('duration').value),
                minimum_watch_time: parseInt(document.getElementById('minimum_watch_time').value),
                is_active: parseInt(document.getElementById('is_active').value)
            };
            
            console.log('Form data:', formData); // Debug
            
            // Validate
            if (!formData.title) {
                alert('Please enter a title');
                return;
            }
            
            if (!formData.description) {
                alert('Please enter a description');
                return;
            }
            
            if (!formData.video_url) {
                alert('Please enter a video URL');
                return;
            }
            
            if (!isValidURL(formData.video_url)) {
                alert('Please enter a valid video URL');
                return;
            }
            
            if (!formData.url) {
                alert('Please enter a landing page URL');
                return;
            }
            
            if (!isValidURL(formData.url)) {
                alert('Please enter a valid landing page URL');
                return;
            }
            
            if (formData.image_url && !isValidURL(formData.image_url)) {
                alert('Please enter a valid image URL or leave it empty');
                return;
            }
            
            if (formData.reward <= 0) {
                alert('Reward amount must be greater than 0');
                return;
            }
            
            if (formData.duration <= 0) {
                alert('Duration must be greater than 0');
                return;
            }
            
            if (formData.minimum_watch_time <= 0) {
                alert('Minimum watch time must be greater than 0');
                return;
            }
            
            if (formData.minimum_watch_time > formData.duration) {
                alert('Minimum watch time cannot be greater than duration');
                return;
            }
            
            // Disable submit button
            const submitBtn = e.target.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = formData.ad_id ? 'Updating...' : 'Saving...';
            
            try {
                const response = await fetch('manage_ads_save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const text = await response.text();
                console.log('Raw server response:', text);
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    alert('Server returned invalid JSON. Check console for details.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }

                if (result.success) {
                    alert(result.message);
                    closeModal();
                    location.reload(); // Reload to show updated data
                } else {
                    alert('Error: ' + result.message);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while saving the advertisement');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

// Toggle Ad Status
async function toggleAdStatus(adId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this advertisement?`)) {
        return;
    }
    
    try {
        const response = await fetch('toggle_ad_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ad_id: adId,
                is_active: newStatus ? 1 : 0
            })
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
        alert('An error occurred while updating the advertisement status');
    }
}

// Delete Advertisement
async function deleteAd(adId, adTitle) {
    if (!confirm(`Are you sure you want to delete "${adTitle}"?\n\nThis action cannot be undone and will also delete all view records for this ad.`)) {
        return;
    }
    
    // Double confirmation for safety
    if (!confirm('Are you absolutely sure? This will permanently delete the advertisement.')) {
        return;
    }
    
    try {
        const response = await fetch('delete_ad.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ad_id: adId
            })
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
        alert('An error occurred while deleting the advertisement');
    }
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchAds');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            
            let visibleCount = 0;
            tableRows.forEach(row => {
                const title = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
                const id = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                
                if (title.includes(searchTerm) || id.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            console.log(`Search: "${searchTerm}" - ${visibleCount} results found`);
        });
    }
});

// Validate URLs in real-time
document.addEventListener('DOMContentLoaded', function() {
    const videoUrlInput = document.getElementById('video_url');
    const imageUrlInput = document.getElementById('image_url');
    const urlInput = document.getElementById('url');
    
    if (videoUrlInput) {
        videoUrlInput.addEventListener('blur', function() {
            if (this.value && !isValidURL(this.value)) {
                this.style.borderColor = '#e74c3c';
                alert('Please enter a valid video URL');
                this.focus();
            } else if (this.value) {
                this.style.borderColor = '#27ae60';
            }
        });
    }
    
    if (imageUrlInput) {
        imageUrlInput.addEventListener('blur', function() {
            if (this.value && !isValidURL(this.value)) {
                this.style.borderColor = '#e74c3c';
                alert('Please enter a valid image URL');
                this.focus();
            } else if (this.value) {
                this.style.borderColor = '#27ae60';
            }
        });
    }
    
    if (urlInput) {
        urlInput.addEventListener('blur', function() {
            if (this.value && !isValidURL(this.value)) {
                this.style.borderColor = '#e74c3c';
                alert('Please enter a valid URL');
                this.focus();
            } else if (this.value) {
                this.style.borderColor = '#27ae60';
            }
        });
    }
});

// Helper function to validate URL
function isValidURL(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// Validate minimum watch time vs duration
document.addEventListener('DOMContentLoaded', function() {
    const minTimeInput = document.getElementById('minimum_watch_time');
    const durationInput = document.getElementById('duration');
    
    if (minTimeInput && durationInput) {
        minTimeInput.addEventListener('input', function() {
            const duration = parseInt(durationInput.value);
            const minTime = parseInt(this.value);
            
            if (minTime > duration) {
                this.setCustomValidity('Minimum watch time cannot be greater than duration');
                this.style.borderColor = '#e74c3c';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '#ecf0f1';
            }
        });
        
        durationInput.addEventListener('input', function() {
            const minTime = parseInt(minTimeInput.value);
            const duration = parseInt(this.value);
            
            if (minTime > duration) {
                minTimeInput.setCustomValidity('Minimum watch time cannot be greater than duration');
                minTimeInput.style.borderColor = '#e74c3c';
            } else {
                minTimeInput.setCustomValidity('');
                minTimeInput.style.borderColor = '#ecf0f1';
            }
        });
    }
});

// Debug: Log when script is loaded
console.log('manage_ads.js loaded successfully');