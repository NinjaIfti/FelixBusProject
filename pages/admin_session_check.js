/**
 * Admin Session Check Script
 * Periodically checks if the current session is still valid
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize session checking
    initSessionCheck();
});

function initSessionCheck() {
    // Check session every minute
    setInterval(checkSessionValidity, 60000); // 60000 ms = 1 minute
    
    // Also check when the tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            checkSessionValidity();
        }
    });
}

function checkSessionValidity() {
    fetch('check_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'check_session'
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.valid) {
            showSessionExpiredModal(data.message);
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
    });
}

function showSessionExpiredModal(message) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('sessionExpiredModal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'sessionExpiredModal';
        modal.className = 'fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50';
        
        const modalContent = document.createElement('div');
        modalContent.className = 'bg-white rounded-lg p-8 max-w-md w-full';
        
        modalContent.innerHTML = `
            <h2 class="text-2xl font-bold text-red-600 mb-4">Session Expired</h2>
            <p class="text-gray-700 mb-6" id="sessionExpiredMessage">Your session has expired or is no longer valid.</p>
            <div class="text-center">
                <button id="sessionLoginBtn" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                    Log In Again
                </button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        document.getElementById('sessionLoginBtn').addEventListener('click', function() {
            window.location.href = 'login.php';
        });
    }
    
    // Update message and show modal
    document.getElementById('sessionExpiredMessage').textContent = message || 'Your session has expired or is no longer valid.';
    modal.style.display = 'flex';
    
    // Disable interaction with rest of page
    document.body.style.overflow = 'hidden';
} 