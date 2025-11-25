// Add your advertisement video URLs here
const adPlaylist = [
    "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
    "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4",
    "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4",
    
];

let watchTime = 0;
let watchInterval = null;
let minimumWatchTime = 30;
let hasClaimedReward = false;
let adCompleted = false;

const video = document.getElementById('adVideo');
const progressFill = document.getElementById('progressFill');
const claimBtn = document.getElementById('claimBtn');
const nextAdBtn = document.getElementById('nextAdBtn');
const messageBox = document.getElementById('messageBox');

if (video && adData) {
    minimumWatchTime = adData.minimum_watch_time || 30;
    
    // Start tracking watch time when video plays
    video.addEventListener('play', function() {
        if (!hasClaimedReward && !adCompleted) {
            startWatchTimer();
        }
    });

    // Pause timer when video pauses
    video.addEventListener('pause', function() {
        stopWatchTimer();
    });

    // Handle video end
    video.addEventListener('ended', function() {
        stopWatchTimer();
        if (watchTime >= minimumWatchTime && !hasClaimedReward) {
            adCompleted = true;
            enableClaimButton();
        }
    });

    // Prevent skipping
    video.addEventListener('seeked', function() {
        if (video.currentTime > watchTime) {
            video.currentTime = watchTime;
            showMessage('Please watch the entire ad without skipping.', 'error');
        }
    });
}

function startWatchTimer() {
    if (watchInterval) return;
    
    watchInterval = setInterval(() => {
        if (!video.paused) {
            watchTime++;
            updateProgress();
            
            if (watchTime >= minimumWatchTime && !adCompleted) {
                adCompleted = true;
                enableClaimButton();
            }
        }
    }, 1000);
}

function stopWatchTimer() {
    if (watchInterval) {
        clearInterval(watchInterval);
        watchInterval = null;
    }
}

function updateProgress() {
    const progress = Math.min((watchTime / minimumWatchTime) * 100, 100);
    progressFill.style.width = progress + '%';
}

function enableClaimButton() {
    claimBtn.disabled = false;
    claimBtn.classList.add('active');
    showMessage('Great! You can now claim your reward.', 'success');
}

function showMessage(text, type) {
    messageBox.textContent = text;
    messageBox.className = 'message ' + type;
    messageBox.style.display = 'block';
    
    setTimeout(() => {
        messageBox.style.display = 'none';
    }, 5000);
}

// Claim reward button
claimBtn.addEventListener('click', async function() {
    if (hasClaimedReward || !adCompleted) return;
    
    this.disabled = true;
    this.textContent = 'Claiming...';
    
    try {
        const formData = new FormData();
        formData.append('ad_id', adData.id);
        formData.append('watch_time', watchTime);
        
        const response = await fetch('claim_reward.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            hasClaimedReward = true;
            
            // Update UI
            document.getElementById('todayEarnings').textContent = '$' + parseFloat(data.today_earnings).toFixed(2);
            document.getElementById('adsToday').textContent = data.ads_today + '/10';
            document.getElementById('totalRewards').textContent = '$' + parseFloat(data.total_rewards).toFixed(2);
            
            showMessage('Success! You\'ve claimed your reward. Click "Next Ad" to continue.', 'success');
            
            this.textContent = 'Claimed ✓';
            this.style.background = '#4caf50';
            this.style.borderColor = '#4caf50';
            this.style.color = 'white';
            
        } else {
            showMessage('Error: ' + data.message, 'error');
            this.disabled = false;
            this.textContent = 'Claim Rewards';
        }
    } catch (error) {
        showMessage('An error occurred. Please try again.', 'error');
        this.disabled = false;
        this.textContent = 'Claim Rewards';
    }
});

// Next ad button
nextAdBtn.addEventListener('click', function() {
    if (!hasClaimedReward && adCompleted) {
        showMessage('Please claim your reward before moving to the next ad.', 'info');
        return;
    }
    
    // Reload page to get next ad
    window.location.reload();
});

// Prevent page unload if ad is playing
window.addEventListener('beforeunload', function(e) {
    if (video && !video.paused && !hasClaimedReward) {
        e.preventDefault();
        e.returnValue = 'You haven\'t claimed your reward yet. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Initialize
if (!adData) {
    showMessage('No ads available at the moment. Please check back later.', 'info');
    claimBtn.disabled = true;
    nextAdBtn.textContent = 'Back to Dashboard';
}