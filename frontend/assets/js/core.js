// Core Utilities - Shared across all pages - FIXED VERSION
// Location: frontend/assets/js/core.js
// 
// This file contains all shared utilities and should be loaded first

// =============================================================================
// GLOBAL NAMESPACE
// =============================================================================

window.IceTimeApp = window.IceTimeApp || {};

// =============================================================================
// EVENT BUS (MISSING IN ORIGINAL) - NOW IMPLEMENTED
// =============================================================================

window.IceTimeApp.EventBus = (() => {
    const events = {};
    
    return {
        on(event, callback) {
            if (!events[event]) {
                events[event] = [];
            }
            events[event].push(callback);
        },
        
        off(event, callback) {
            if (!events[event]) {
                return;
            }
            events[event] = events[event].filter(cb => cb !== callback);
        },
        
        emit(event, data) {
            if (!events[event]) {
                return;
            }
            events[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        },
        
        once(event, callback) {
            const onceCallback = (data) => {
                callback(data);
                this.off(event, onceCallback);
            };
            this.on(event, onceCallback);
        }
    };
})();

// =============================================================================
// CONFIGURATION (GLOBAL)
// =============================================================================

window.IceTimeApp.Config = {
    apiEndpoints: {
        api: '../../backend/api.php',
        sse: '../../backend/workers/sse.php'
    },
    calendar: {
        defaultView: 'dayGridMonth',
        snapDuration: '00:05:00',
        timeInterval: 15
    },
    sse: {
        maxReconnectAttempts: 10,
        baseReconnectDelay: 1000,
        maxReconnectDelay: 30000
    },
    import: {
        maxFileSize: 5 * 1024 * 1024, // 5MB
        maxEvents: 20,
        allowedFormats: ['.json', '.csv', '.ics', '.ical', '.txt']
    }
};

// =============================================================================
// UTILITY FUNCTIONS (GLOBAL)
// =============================================================================

window.IceTimeApp.Utils = {
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    formatDateTimeForAPI(dateTimeStr) {
        if (!dateTimeStr) return '';
        
        const parts = dateTimeStr.split(' ');
        if (parts.length === 2) {
            return `${parts[0]} ${parts[1]}:00`;
        }
        
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) return '';
        
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    
    formatDateTime(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    getFileExtension(filename) {
        return filename.toLowerCase().substring(filename.lastIndexOf('.'));
    },
    
    validateEventData(event) {
        return {
            id: event.id || 0,
            title: event.title || 'Untitled Event',
            start: event.start || null,
            end: event.end || null,
            extendedProps: event.extendedProps || {},
            backgroundColor: event.backgroundColor || '#3498db',
            borderColor: event.borderColor || '#3498db'
        };
    },
    
    generateEventId() {
        return Math.random().toString(36).substr(2, 9);
    },
    
    generateId(prefix = 'component') {
        return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    showNotification(message, type = 'info', duration = 5000) {
        // Try to use global notification system if available
        if (window.showNotification) {
            return window.showNotification(message, type, duration);
        }
        
        // Fallback to console
        const logMethod = type === 'error' ? 'error' : 'log';
        console[logMethod](`[${type.toUpperCase()}] ${message}`);
    }
};

// =============================================================================
// API CLIENT (GLOBAL)
// =============================================================================

window.IceTimeApp.APIClient = (() => {
    const makeRequest = async (url, options = {}) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include',
                ...options
            });
            
            if (response.status === 401) {
                window.IceTimeApp.EventBus.emit('auth:unauthorized');
                throw new Error('Authentication required');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    };
    
    return {
        // Authentication operations
        checkAuth() {
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?action=check_auth`);
        },
        
        login(email, password, rememberMe = false) {
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'login',
                    email,
                    password,
                    rememberMe
                })
            });
        },
        
        register(name, email, password) {
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'register',
                    name,
                    email,
                    password
                })
            });
        },
        
        logout() {
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({ action: 'logout' })
            });
        },
        
        // User operations
        getUsers() {
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?action=users`);
        },
        
        getUsersWithStats() {
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?action=users_with_stats`);
        },
        
        // Event operations
        getEvents(userIds = []) {
            const userIdsParam = userIds.length ? `&user_ids=${userIds.join(',')}` : '';
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?action=events${userIdsParam}`);
        },
        
        createEvent(eventData) {
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({ ...eventData, action: 'create_event' })
            });
        },
        
        updateEvent(eventData) {
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'PUT',
                body: JSON.stringify(eventData)
            });
        },
        
        deleteEvent(eventId) {
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?id=${eventId}`, {
                method: 'DELETE'
            });
        },
        
        // Import operations
        validateImportFile(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'validate_import_file');
            
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: formData,
                headers: {} // Remove Content-Type to let browser set it for FormData
            });
        },
        
        previewImportFile(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'preview_import');
            
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: formData,
                headers: {}
            });
        },
        
        importEvents(file) {
            const formData = new FormData();
            formData.append('import_file', file);
            formData.append('action', 'import_events');
            
            return makeRequest(window.IceTimeApp.Config.apiEndpoints.api, {
                method: 'POST',
                body: formData,
                headers: {}
            });
        },
        
        // Test endpoint
        testConnection() {
            return makeRequest(`${window.IceTimeApp.Config.apiEndpoints.api}?action=test`);
        }
    };
})();

// =============================================================================
// AUTHENTICATION GUARD (GLOBAL)
// =============================================================================

window.IceTimeApp.AuthGuard = (() => {
    let currentUser = null;
    
    const checkAuthentication = async () => {
        try {
            const response = await window.IceTimeApp.APIClient.checkAuth();
            
            if (response.authenticated) {
                currentUser = response.user;
                window.IceTimeApp.EventBus.emit('auth:authenticated', { user: response.user });
                return true;
            } else {
                redirectToLogin();
                return false;
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            redirectToLogin();
            return false;
        }
    };
    
    const redirectToLogin = () => {
        window.location.href = './login.php';
    };
    
    const getCurrentUser = () => currentUser;
    
    const logout = async () => {
        try {
            await window.IceTimeApp.APIClient.logout();
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            redirectToLogin();
        }
    };
    
    // Event listeners
    window.IceTimeApp.EventBus.on('auth:unauthorized', redirectToLogin);
    
    return {
        checkAuthentication,
        getCurrentUser,
        logout
    };
})();

// =============================================================================
// UI MANAGER (GLOBAL)
// =============================================================================

window.IceTimeApp.UIManager = (() => {
    const updateConnectionStatus = (message, className = '') => {
        const statusEl = document.getElementById('connectionStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
        }
    };
    
    const updateUserStatus = (message, className = '') => {
        const statusEl = document.getElementById('userStatus');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status ${className}`;
        }
    };
    
    const showError = (message) => {
        let errorEl = document.getElementById('errorDisplay');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.id = 'errorDisplay';
            errorEl.className = 'alert alert-danger';
            errorEl.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 15px;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 8px;
                color: #721c24;
            `;
            document.body.appendChild(errorEl);
        }
        
        errorEl.innerHTML = `
            <strong>Error:</strong> ${message}
            <button type="button" class="close" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
        `;
        
        setTimeout(() => errorEl.remove(), 5000);
        errorEl.querySelector('.close').addEventListener('click', () => errorEl.remove());
    };
    
    const showSuccess = (message) => {
        let successEl = document.getElementById('successDisplay');
        if (!successEl) {
            successEl = document.createElement('div');
            successEl.id = 'successDisplay';
            successEl.className = 'alert alert-success';
            successEl.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 15px;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                color: #155724;
            `;
            document.body.appendChild(successEl);
        }
        
        successEl.innerHTML = `
            <strong>Success:</strong> ${message}
            <button type="button" class="close" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
        `;
        
        setTimeout(() => successEl.remove(), 3000);
        successEl.querySelector('.close').addEventListener('click', () => successEl.remove());
    };
    
    const setupAuthenticatedUI = (user) => {
        const userNameInput = document.getElementById('userName');
        if (userNameInput) {
            userNameInput.value = user.name;
            userNameInput.disabled = true;
            userNameInput.style.backgroundColor = '#f7fafc';
            userNameInput.style.color = '#2d3748';
        }
        
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
                    window.IceTimeApp.AuthGuard.logout();
                }
            });
            
            userSection.appendChild(logoutBtn);
        }
    };
    
    const setButtonLoading = (button, isLoading) => {
        if (!button) return;
        
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
            }
            button.textContent = 'Processing...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            if (button.dataset.originalText) {
                button.textContent = button.dataset.originalText;
            }
        }
    };
    
    // Event listeners
    window.IceTimeApp.EventBus.on('connection:status', ({ status, message }) => {
        updateConnectionStatus(message, status);
    });
    
    window.IceTimeApp.EventBus.on('auth:authenticated', ({ user }) => {
        setupAuthenticatedUI(user);
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showError,
        showSuccess,
        setupAuthenticatedUI,
        setButtonLoading
    };
})();

// =============================================================================
// SSE MANAGER (GLOBAL)
// =============================================================================

window.IceTimeApp.SSEManager = (() => {
    let eventSource = null;
    let lastEventId = 0;
    let reconnectAttempts = 0;
    let isConnected = false;
    
    const connect = () => {
        if (eventSource) {
            eventSource.close();
            isConnected = false;
        }
        
        window.IceTimeApp.EventBus.emit('connection:status', {
            status: 'connecting',
            message: 'Connecting...'
        });
        
        console.log('Attempting SSE connection with lastEventId:', lastEventId);
        
        eventSource = new EventSource(`${window.IceTimeApp.Config.apiEndpoints.sse}?lastEventId=${lastEventId}`);
        
        eventSource.onopen = () => {
            window.IceTimeApp.EventBus.emit('connection:status', {
                status: 'connected',
                message: 'Connected'
            });
            isConnected = true;
            reconnectAttempts = 0;
            console.log('SSE connection established');
        };
        
        eventSource.onerror = (e) => {
            console.log('SSE connection error:', e);
            window.IceTimeApp.EventBus.emit('connection:status', {
                status: 'disconnected',
                message: 'Disconnected'
            });
            isConnected = false;
            eventSource.close();
            
            // Exponential backoff for reconnection
            reconnectAttempts++;
            if (reconnectAttempts <= window.IceTimeApp.Config.sse.maxReconnectAttempts) {
                const delay = Math.min(
                    window.IceTimeApp.Config.sse.baseReconnectDelay * Math.pow(2, reconnectAttempts),
                    window.IceTimeApp.Config.sse.maxReconnectDelay
                );
                
                console.log(`SSE reconnecting in ${delay}ms (attempt ${reconnectAttempts})`);
                setTimeout(connect, delay);
            } else {
                console.log('Max reconnection attempts reached');
                window.IceTimeApp.EventBus.emit('connection:status', {
                    status: 'failed',
                    message: 'Connection failed'
                });
            }
        };
        
        setupEventListeners();
    };
    
    const setupEventListeners = () => {
        const handleSSEEvent = (eventType, handler) => {
            eventSource.addEventListener(eventType, (e) => {
                try {
                    const eventData = JSON.parse(e.data);
                    handler(eventData);
                    lastEventId = parseInt(e.lastEventId) || lastEventId;
                } catch (error) {
                    console.error(`Error handling SSE ${eventType} event:`, error);
                }
            });
        };
        
        handleSSEEvent('create', (eventData) => {
            console.log('SSE: Creating event', eventData.id);
            window.IceTimeApp.EventBus.emit('sse:eventCreate', { eventData });
        });
        
        handleSSEEvent('update', (eventData) => {
            console.log('SSE: Updating event', eventData.id);
            window.IceTimeApp.EventBus.emit('sse:eventUpdate', { eventData });
        });
        
        handleSSEEvent('delete', (eventData) => {
            console.log('SSE: Deleting event', eventData.id);
            window.IceTimeApp.EventBus.emit('sse:eventDelete', { eventId: eventData.id });
        });
        
        eventSource.addEventListener('user_created', (e) => {
            console.log('SSE: User created, refreshing users list');
            window.IceTimeApp.EventBus.emit('users:refresh');
            lastEventId = parseInt(e.lastEventId) || lastEventId;
        });
        
        eventSource.addEventListener('heartbeat', (e) => {
            lastEventId = parseInt(e.lastEventId) || lastEventId;
        });
        
        eventSource.addEventListener('reconnect', (e) => {
            console.log('SSE: Server requested reconnect');
            lastEventId = parseInt(e.lastEventId) || lastEventId;
            connect();
        });
        
        eventSource.addEventListener('timeout', () => {
            console.log('SSE: Connection timeout, reconnecting');
            connect();
        });
    };
    
    const disconnect = () => {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
            isConnected = false;
        }
    };
    
    const getConnectionStatus = () => ({
        isConnected,
        reconnectAttempts,
        lastEventId
    });
    
    return {
        connect,
        disconnect,
        getConnectionStatus
    };
})();

// =============================================================================
// INITIALIZATION
// =============================================================================

console.log('Ice Time Management System - Core utilities loaded');
console.log('Available modules:', Object.keys(window.IceTimeApp));

// Make EventBus available globally for backward compatibility
if (!window.EventBus) {
    window.EventBus = window.IceTimeApp.EventBus;
}