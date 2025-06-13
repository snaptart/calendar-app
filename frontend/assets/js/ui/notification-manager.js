/**
 * Shared Notification Manager for standardized user feedback
 * Location: frontend/js/ui/notification-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';

export const NotificationManager = (() => {
    let notificationContainer = null;
    let loadingOverlay = null;
    let currentNotifications = new Map();
    let notificationId = 0;
    
    /**
     * Initialize notification system
     */
    const init = () => {
        createNotificationContainer();
        createLoadingOverlay();
        setupEventListeners();
    };
    
    /**
     * Create notification container if it doesn't exist
     */
    const createNotificationContainer = () => {
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            notificationContainer.className = 'notification-container';
            notificationContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                pointer-events: none;
            `;
            document.body.appendChild(notificationContainer);
        }
    };
    
    /**
     * Create loading overlay if it doesn't exist
     */
    const createLoadingOverlay = () => {
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                flex-direction: column;
            `;
            
            loadingOverlay.innerHTML = `
                <div class="loading-spinner" style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-bottom: 20px;
                "></div>
                <div class="loading-text" style="
                    color: white;
                    font-size: 16px;
                    text-align: center;
                ">Loading...</div>
            `;
            
            document.body.appendChild(loadingOverlay);
            
            // Add CSS animation for spinner
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    };
    
    /**
     * Set up event listeners for global notifications
     */
    const setupEventListeners = () => {
        EventBus.on('notification:success', ({ message, duration }) => {
            showSuccess(message, duration);
        });
        
        EventBus.on('notification:error', ({ message, duration }) => {
            showError(message, duration);
        });
        
        EventBus.on('notification:warning', ({ message, duration }) => {
            showWarning(message, duration);
        });
        
        EventBus.on('notification:info', ({ message, duration }) => {
            showInfo(message, duration);
        });
        
        EventBus.on('loading:show', ({ message }) => {
            showLoadingOverlay(message);
        });
        
        EventBus.on('loading:hide', () => {
            hideLoadingOverlay();
        });
    };
    
    /**
     * Show success notification
     */
    const showSuccess = (message, duration = 5000) => {
        return showNotification(message, 'success', duration);
    };
    
    /**
     * Show error notification
     */
    const showError = (message, duration = 8000) => {
        return showNotification(message, 'error', duration);
    };
    
    /**
     * Show warning notification
     */
    const showWarning = (message, duration = 6000) => {
        return showNotification(message, 'warning', duration);
    };
    
    /**
     * Show info notification
     */
    const showInfo = (message, duration = 4000) => {
        return showNotification(message, 'info', duration);
    };
    
    /**
     * Show notification with custom type
     */
    const showNotification = (message, type = 'info', duration = 5000) => {
        const id = ++notificationId;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${getNotificationColor(type)};
            color: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            pointer-events: auto;
            cursor: pointer;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            position: relative;
            max-width: 100%;
            word-wrap: break-word;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex: 1; margin-right: 10px;">
                    <div style="font-weight: bold; margin-bottom: 5px;">
                        ${getNotificationIcon(type)} ${getNotificationTitle(type)}
                    </div>
                    <div>${message}</div>
                </div>
                <button style="
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    line-height: 1;
                    opacity: 0.7;
                " onclick="this.parentElement.parentElement.click()">×</button>
            </div>
        `;
        
        // Add click to dismiss
        notification.addEventListener('click', () => {
            dismissNotification(id);
        });
        
        notificationContainer.appendChild(notification);
        currentNotifications.set(id, notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                dismissNotification(id);
            }, duration);
        }
        
        EventBus.emit('notification:shown', {
            id,
            message,
            type,
            duration
        });
        
        return id;
    };
    
    /**
     * Dismiss notification by ID
     */
    const dismissNotification = (id) => {
        const notification = currentNotifications.get(id);
        if (notification) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                currentNotifications.delete(id);
            }, 300);
            
            EventBus.emit('notification:dismissed', { id });
        }
    };
    
    /**
     * Dismiss all notifications
     */
    const dismissAll = () => {
        currentNotifications.forEach((notification, id) => {
            dismissNotification(id);
        });
    };
    
    /**
     * Show loading overlay
     */
    const showLoadingOverlay = (message = 'Loading...') => {
        if (loadingOverlay) {
            const textElement = loadingOverlay.querySelector('.loading-text');
            if (textElement) {
                textElement.textContent = message;
            }
            loadingOverlay.style.display = 'flex';
            
            EventBus.emit('loading:shown', { message });
        }
    };
    
    /**
     * Hide loading overlay
     */
    const hideLoadingOverlay = () => {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
            
            EventBus.emit('loading:hidden');
        }
    };
    
    /**
     * Update connection status indicator
     */
    const updateConnectionStatus = (status, message = '') => {
        let statusElement = document.getElementById('connection-status');
        
        if (!statusElement) {
            statusElement = document.createElement('div');
            statusElement.id = 'connection-status';
            statusElement.style.cssText = `
                position: fixed;
                top: 10px;
                left: 20px;
                padding: 8px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
                z-index: 9998;
                transition: all 0.3s ease;
            `;
            document.body.appendChild(statusElement);
        }
        
        const statusConfig = {
            connected: {
                color: '#28a745',
                icon: '🟢',
                text: 'Connected'
            },
            connecting: {
                color: '#ffc107',
                icon: '🟡',
                text: 'Connecting...'
            },
            disconnected: {
                color: '#dc3545',
                icon: '🔴',
                text: 'Disconnected'
            },
            error: {
                color: '#dc3545',
                icon: '❌',
                text: 'Error'
            }
        };
        
        const config = statusConfig[status] || statusConfig.disconnected;
        statusElement.style.background = config.color;
        statusElement.style.color = 'white';
        statusElement.textContent = `${config.icon} ${message || config.text}`;
        
        EventBus.emit('connection:status-updated', { status, message });
    };
    
    /**
     * Show progress notification
     */
    const showProgress = (message, current = 0, total = 100) => {
        const id = ++notificationId;
        
        const notification = document.createElement('div');
        notification.className = 'notification notification-progress';
        notification.style.cssText = `
            background: #007bff;
            color: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            pointer-events: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            position: relative;
            max-width: 100%;
        `;
        
        const percentage = Math.round((current / total) * 100);
        
        notification.innerHTML = `
            <div style="margin-bottom: 10px;">
                <div style="font-weight: bold;">⏳ ${message}</div>
                <div style="font-size: 12px; opacity: 0.9;">${current} of ${total} (${percentage}%)</div>
            </div>
            <div style="background: rgba(255,255,255,0.3); border-radius: 10px; height: 6px; overflow: hidden;">
                <div style="background: white; height: 100%; width: ${percentage}%; transition: width 0.3s ease;"></div>
            </div>
        `;
        
        notificationContainer.appendChild(notification);
        currentNotifications.set(id, notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        return id;
    };
    
    /**
     * Update progress notification
     */
    const updateProgress = (id, current, total, message = null) => {
        const notification = currentNotifications.get(id);
        if (notification) {
            const percentage = Math.round((current / total) * 100);
            
            const messageDiv = notification.querySelector('div div');
            const statusDiv = notification.querySelector('div div:nth-child(2)');
            const progressBar = notification.querySelector('div div div');
            
            if (message && messageDiv) {
                messageDiv.textContent = `⏳ ${message}`;
            }
            
            if (statusDiv) {
                statusDiv.textContent = `${current} of ${total} (${percentage}%)`;
            }
            
            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
            }
            
            // Auto-dismiss when complete
            if (current >= total) {
                setTimeout(() => {
                    dismissNotification(id);
                }, 2000);
            }
        }
    };
    
    /**
     * Get notification color based on type
     */
    const getNotificationColor = (type) => {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#007bff'
        };
        return colors[type] || colors.info;
    };
    
    /**
     * Get notification icon based on type
     */
    const getNotificationIcon = (type) => {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    };
    
    /**
     * Get notification title based on type
     */
    const getNotificationTitle = (type) => {
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };
        return titles[type] || titles.info;
    };
    
    /**
     * Show confirmation dialog
     */
    const showConfirmation = (message, title = 'Confirmation') => {
        return new Promise((resolve) => {
            const id = ++notificationId;
            
            const notification = document.createElement('div');
            notification.className = 'notification notification-confirmation';
            notification.style.cssText = `
                background: #6c757d;
                color: white;
                padding: 20px;
                margin-bottom: 10px;
                border-radius: 5px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                pointer-events: auto;
                transform: translateX(100%);
                transition: transform 0.3s ease-in-out;
                position: relative;
                max-width: 100%;
            `;
            
            notification.innerHTML = `
                <div style="margin-bottom: 15px;">
                    <div style="font-weight: bold; margin-bottom: 10px;">❓ ${title}</div>
                    <div>${message}</div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button class="confirm-yes" style="
                        background: #28a745;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                    ">Yes</button>
                    <button class="confirm-no" style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                    ">No</button>
                </div>
            `;
            
            notification.querySelector('.confirm-yes').addEventListener('click', () => {
                dismissNotification(id);
                resolve(true);
            });
            
            notification.querySelector('.confirm-no').addEventListener('click', () => {
                dismissNotification(id);
                resolve(false);
            });
            
            notificationContainer.appendChild(notification);
            currentNotifications.set(id, notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
        });
    };
    
    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    return {
        init,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showNotification,
        dismissNotification,
        dismissAll,
        showLoadingOverlay,
        hideLoadingOverlay,
        updateConnectionStatus,
        showProgress,
        updateProgress,
        showConfirmation
    };
})();