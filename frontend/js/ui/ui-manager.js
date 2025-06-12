/**
 * Manages UI updates and visual feedback with authentication
 * Location: frontend/js/ui/ui-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { AuthGuard } from '../auth/auth-guard.js';

export const UIManager = (() => {
    const updateConnectionStatus = (message, className = '') => {
        const statusEl = document.getElementById('connectionStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
            statusEl.style.backgroundColor = '';
            statusEl.style.color = '';
        }
    };
    
    const updateUserStatus = (message, className = '') => {
        const statusEl = document.getElementById('userStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
        }
    };
    
    const showDragFeedback = (message) => {
        const statusEl = document.getElementById('connectionStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = 'status';
            statusEl.style.backgroundColor = '#f39c12';
            statusEl.style.color = 'white';
        }
    };
    
    const hideDragFeedback = () => {
        setTimeout(() => {
            updateConnectionStatus('Connected', 'connected');
        }, 1000);
    };
    
    const showNotification = (message, type = 'info') => {
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        if (type === 'error') {
            alert(message);
        }
    };
    
    const setupAuthenticatedUI = (user) => {
        // Update header to show authenticated user
        const userNameInput = document.getElementById('userName');
        if (userNameInput) {
            userNameInput.value = user.name;
            userNameInput.disabled = true;
            userNameInput.style.backgroundColor = '#f7fafc';
            userNameInput.style.color = '#2d3748';
        }
        
        // Add logout button
        addLogoutButton();
        
        updateUserStatus(`Logged in as: ${user.name}`, 'user-set');
    };
    
    const addLogoutButton = () => {
        const userSection = document.querySelector('.user-section');
        if (userSection && !document.getElementById('logoutBtn')) {
            const logoutBtn = document.createElement('button');
            logoutBtn.id = 'logoutBtn';
            logoutBtn.className = 'btn btn-small btn-outline';
            logoutBtn.textContent = 'Logout';
            logoutBtn.style.marginLeft = '8px';
            
            logoutBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to logout?')) {
                    AuthGuard.logout();
                }
            });
            
            userSection.appendChild(logoutBtn);
        }
    };
    
    // Event listeners
    EventBus.on('connection:status', ({ status, message }) => {
        updateConnectionStatus(message, status);
    });
    
    EventBus.on('auth:authenticated', ({ user }) => {
        setupAuthenticatedUI(user);
    });
    
    EventBus.on('user:set', ({ user }) => {
        updateUserStatus(`Logged in as: ${user.name}`, 'user-set');
    });
    
    EventBus.on('user:error', ({ error }) => {
        updateUserStatus('Authentication error', 'disconnected');
        showNotification(`Authentication error: ${error.message}`, 'error');
    });
    
    EventBus.on('users:error', ({ error }) => {
        updateUserStatus('Authentication error', 'disconnected');
        showNotification(`Authentication error: ${error.message}`, 'error');
    });
    
    EventBus.on('drag:start', ({ message }) => {
        showDragFeedback(message);
    });
    
    EventBus.on('drag:stop', () => {
        hideDragFeedback();
    });
    
    // Listen for notification requests from other modules
    EventBus.on('ui:showNotification', ({ message, type }) => {
        showNotification(message, type);
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showDragFeedback,
        hideDragFeedback,
        showNotification,
        setupAuthenticatedUI
    };
})();