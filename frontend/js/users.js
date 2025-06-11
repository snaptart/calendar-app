// Enhanced Users Management Page with Better Data Handling
// Location: frontend/js/users.js
// 
// Improved version with better error handling and data validation

// =============================================================================
// CORE UTILITIES AND EVENT BUS
// =============================================================================

/**
 * Simple Event Bus for component communication
 */
const EventBus = (() => {
    const events = {};
    
    return {
        on(event, callback) {
            if (!events[event]) events[event] = [];
            events[event].push(callback);
        },
        
        off(event, callback) {
            if (!events[event]) return;
            events[event] = events[event].filter(cb => cb !== callback);
        },
        
        emit(event, data) {
            if (!events[event]) return;
            events[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    };
})();

/**
 * Configuration object for API endpoints
 */
const Config = {
    apiEndpoints: {
        api: 'backend/api.php',
        sse: 'backend/workers/sse.php'
    },
    sse: {
        maxReconnectAttempts: 10,
        baseReconnectDelay: 1000,
        maxReconnectDelay: 30000
    }
};

/**
 * Enhanced utility functions
 */
const Utils = {
    formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === null) {
            return 'Never';
        }
        
        const date = new Date(dateString);
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
    
    formatDateShort(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === null) {
            return 'Never';
        }
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'Invalid';
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    getTimeAgo(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === null) {
            return 'Never';
        }
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'Unknown';
        }
        
        const now = new Date();
        const diffMs = now - date;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            if (diffHours === 0) {
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                return diffMinutes <= 1 ? 'Just now' : `${diffMinutes} min ago`;
            }
            return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
        } else if (diffDays === 1) {
            return 'Yesterday';
        } else if (diffDays < 7) {
            return `${diffDays} days ago`;
        } else if (diffDays < 30) {
            const weeks = Math.floor(diffDays / 7);
            return weeks === 1 ? '1 week ago' : `${weeks} weeks ago`;
        } else if (diffDays < 365) {
            const months = Math.floor(diffDays / 30);
            return months === 1 ? '1 month ago' : `${months} months ago`;
        } else {
            const years = Math.floor(diffDays / 365);
            return years === 1 ? '1 year ago' : `${years} years ago`;
        }
    },
    
    getUserStatus(user) {
        // Check if user has status field from backend
        if (user.status) {
            return user.status;
        }
        
        // Fallback calculation
        const now = new Date();
        
        // Check if user has password (registered user vs guest)
        if (!user.has_password && user.has_password !== undefined) {
            return 'guest';
        }
        
        const lastLogin = user.last_login;
        if (!lastLogin || lastLogin === '0000-00-00 00:00:00' || lastLogin === null) {
            return 'new';
        }
        
        const lastLoginDate = new Date(lastLogin);
        const diffDays = Math.floor((now - lastLoginDate) / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 1) {
            return 'active';
        } else if (diffDays <= 7) {
            return 'recent';
        } else {
            return 'inactive';
        }
    },
    
    validateUserData(user) {
        // Ensure required fields exist with defaults
        return {
            id: user.id || 0,
            name: user.name || 'Unknown User',
            email: user.email || null,
            color: user.color || '#3498db',
            created_at: user.created_at || null,
            last_login: user.last_login || null,
            event_count: parseInt(user.event_count) || 0,
            upcoming_events: parseInt(user.upcoming_events) || 0,
            past_events: parseInt(user.past_events) || 0,
            status: user.status || 'unknown',
            has_password: user.has_password || false
        };
    }
};

// =============================================================================
// API CLIENT WITH ENHANCED ERROR HANDLING
// =============================================================================

/**
 * Enhanced API communication with better error handling
 */
const APIClient = (() => {
    const makeRequest = async (url, options = {}) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include', // Include cookies for session
                ...options
            });
            
            if (response.status === 401) {
                // Unauthorized - redirect to login
                EventBus.emit('auth:unauthorized');
                throw new Error('Authentication required');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('API Response:', data); // Debug logging
            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    };
    
    return {
        // Authentication operations
        checkAuth() {
            return makeRequest(`${Config.apiEndpoints.api}?action=check_auth`);
        },
        
        // User operations with enhanced stats
        getUsersWithStats() {
            console.log('Fetching users with stats...');
            return makeRequest(`${Config.apiEndpoints.api}?action=users_with_stats`);
        },
        
        // Fallback to regular users endpoint
        getUsers() {
            console.log('Fetching basic users...');
            return makeRequest(`${Config.apiEndpoints.api}?action=users`);
        },
        
        // Get user activity summary
        getUserActivity(days = 30) {
            return makeRequest(`${Config.apiEndpoints.api}?action=user_activity&days=${days}`);
        }
    };
})();

// =============================================================================
// AUTHENTICATION GUARD
// =============================================================================

/**
 * Handles authentication checks and redirects
 */
const AuthGuard = (() => {
    let currentUser = null;
    
    const checkAuthentication = async () => {
        try {
            const response = await APIClient.checkAuth();
            
            if (response.authenticated) {
                currentUser = response.user;
                EventBus.emit('auth:authenticated', { user: response.user });
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
        window.location.href = './login.html';
    };
    
    const getCurrentUser = () => currentUser;
    
    const logout = async () => {
        try {
            await fetch(Config.apiEndpoints.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            redirectToLogin();
        }
    };
    
    // Event listeners
    EventBus.on('auth:unauthorized', redirectToLogin);
    
    return {
        checkAuthentication,
        getCurrentUser,
        logout
    };
})();

// =============================================================================
// UI MANAGER
// =============================================================================

/**
 * Enhanced UI management with better feedback
 */
const UIManager = (() => {
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
    
    const showLoadingOverlay = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
    };
    
    const hideLoadingOverlay = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
    };
    
    const showError = (message) => {
        // Create or update error display
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
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorEl.remove();
        }, 5000);
        
        // Close button
        errorEl.querySelector('.close').addEventListener('click', () => {
            errorEl.remove();
        });
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
    
    EventBus.on('users:loading', () => {
        showLoadingOverlay();
    });
    
    EventBus.on('users:loaded', () => {
        hideLoadingOverlay();
    });
    
    EventBus.on('users:error', ({ error }) => {
        hideLoadingOverlay();
        showError(error.message || 'Failed to load users data');
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showLoadingOverlay,
        hideLoadingOverlay,
        showError,
        setupAuthenticatedUI
    };
})();

// =============================================================================
// ENHANCED USERS DATA MANAGER
// =============================================================================

/**
 * Enhanced user data management with better validation
 */
const UsersDataManager = (() => {
    let usersData = [];
    let lastFetchTime = 0;
    
    const loadUsersData = async () => {
        try {
            EventBus.emit('users:loading');
            
            console.log('Loading users data...');
            
            // Try to get users with stats first
            let users;
            try {
                users = await APIClient.getUsersWithStats();
                console.log('Successfully loaded users with stats:', users);
            } catch (error) {
                console.warn('Failed to load users with stats, falling back to basic users:', error);
                // Fallback to basic users
                const basicUsers = await APIClient.getUsers();
                
                // Enhance basic users with default stats
                users = basicUsers.map(user => ({
                    ...Utils.validateUserData(user),
                    event_count: 0,
                    upcoming_events: 0,
                    past_events: 0,
                    status: Utils.getUserStatus(user)
                }));
            }
            
            // Validate and clean user data
            if (!Array.isArray(users)) {
                throw new Error('Invalid users data format received from server');
            }
            
            usersData = users.map(user => {
                const validatedUser = Utils.validateUserData(user);
                console.log('Validated user:', validatedUser);
                return validatedUser;
            });
            
            lastFetchTime = Date.now();
            
            EventBus.emit('users:loaded', { users: usersData });
            return usersData;
            
        } catch (error) {
            console.error('Error loading users data:', error);
            EventBus.emit('users:error', { error });
            throw error;
        }
    };
    
    const refreshData = () => {
        console.log('Refreshing users data...');
        return loadUsersData();
    };
    
    const getUsersData = () => usersData;
    
    const getLastFetchTime = () => lastFetchTime;
    
    return {
        loadUsersData,
        refreshData,
        getUsersData,
        getLastFetchTime
    };
})();

// =============================================================================
// ENHANCED DATATABLES MANAGER
// =============================================================================

/**
 * Enhanced DataTables management with better data handling
 */
const DataTablesManager = (() => {
    let dataTable = null;
    
    const initializeDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        
        console.log('Initializing DataTable...');
        
        dataTable = $('#usersTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[4, 'desc']], // Sort by Member Since (newest first)
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    text: '📄 Export CSV',
                    filename: 'users_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6] // Exclude color column
                    }
                },
                {
                    extend: 'excel',
                    text: '📊 Export Excel',
                    filename: 'users_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6] // Exclude color column
                    }
                },
                {
                    extend: 'pdf',
                    text: '📑 Export PDF',
                    filename: 'users_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6] // Exclude color column
                    },
                    customize: function(doc) {
                        doc.content[1].table.widths = ['20%', '25%', '15%', '15%', '15%', '10%'];
                        doc.styles.tableHeader.fontSize = 10;
                        doc.defaultStyle.fontSize = 9;
                    }
                },
                {
                    extend: 'print',
                    text: '🖨️ Print',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6] // Exclude color column
                    }
                }
            ],
            columnDefs: [
                {
                    targets: 0, // Color column
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    width: '60px'
                },
                {
                    targets: 3, // Events Created column
                    className: 'text-center',
                    width: '120px'
                },
                {
                    targets: 6, // Status column
                    className: 'text-center',
                    width: '100px'
                }
            ],
            language: {
                search: "Search users:",
                lengthMenu: "Show _MENU_ users per page",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "No users found",
                infoFiltered: "(filtered from _MAX_ total users)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                emptyTable: "No users found in the system"
            },
            drawCallback: function() {
                console.log('DataTable redrawn');
            }
        });
        
        console.log('DataTable initialized successfully');
    };
    
    const loadData = (users) => {
        if (!dataTable) {
            console.error('DataTable not initialized');
            return;
        }
        
        console.log('Loading data into DataTable:', users);
        
        // Validate users data
        if (!Array.isArray(users)) {
            console.error('Invalid users data provided to DataTable');
            return;
        }
        
        // Clear existing data
        dataTable.clear();
        
        // Add new data
        users.forEach((user, index) => {
            try {
                const validatedUser = Utils.validateUserData(user);
                
                const row = [
                    createColorIndicator(validatedUser.color),
                    validatedUser.name,
                    validatedUser.email || 'No email',
                    createEventCountBadge(validatedUser.event_count),
                    Utils.formatDateShort(validatedUser.created_at),
                    Utils.getTimeAgo(validatedUser.last_login),
                    createStatusBadge(validatedUser.status)
                ];
                
                dataTable.row.add(row);
            } catch (error) {
                console.error(`Error processing user ${index}:`, error, user);
            }
        });
        
        // Redraw table
        dataTable.draw();
        
        console.log(`Successfully loaded ${users.length} users into DataTable`);
    };
    
    const createColorIndicator = (color) => {
        const safeColor = color || '#3498db';
        return `<div class="user-color-indicator" style="background-color: ${safeColor}"></div>`;
    };
    
    const createEventCountBadge = (count) => {
        const numCount = parseInt(count) || 0;
        let badgeClass = 'event-count';
        
        if (numCount === 0) {
            badgeClass += ' zero';
        } else if (numCount >= 10) {
            badgeClass += ' high';
        }
        
        return `<span class="${badgeClass}">${numCount}</span>`;
    };
    
    const createStatusBadge = (status) => {
        const statusMap = {
            'active': { class: 'active', text: 'Active' },
            'recent': { class: 'recent', text: 'Recent' },
            'inactive': { class: 'inactive', text: 'Inactive' },
            'new': { class: 'new', text: 'New' },
            'guest': { class: 'guest', text: 'Guest' },
            'unknown': { class: 'unknown', text: 'Unknown' }
        };
        
        const statusInfo = statusMap[status] || statusMap['unknown'];
        return `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
    };
    
    const refreshTable = () => {
        if (dataTable) {
            // Instead of ajax reload, we'll trigger a data refresh
            EventBus.emit('users:refresh');
        }
    };
    
    const getDataTable = () => dataTable;
    
    return {
        initializeDataTable,
        loadData,
        refreshTable,
        getDataTable
    };
})();

// =============================================================================
// SSE MANAGER
// =============================================================================

/**
 * Manages Server-Sent Events for real-time updates
 */
const SSEManager = (() => {
    let eventSource = null;
    let lastEventId = 0;
    let reconnectAttempts = 0;
    let isConnected = false;
    
    const connect = () => {
        if (eventSource) {
            eventSource.close();
            isConnected = false;
        }
        
        EventBus.emit('connection:status', {
            status: 'connecting',
            message: 'Connecting...'
        });
        
        console.log('Attempting SSE connection with lastEventId:', lastEventId);
        
        eventSource = new EventSource(`${Config.apiEndpoints.sse}?lastEventId=${lastEventId}`);
        
        eventSource.onopen = () => {
            EventBus.emit('connection:status', {
                status: 'connected',
                message: 'Connected'
            });
            isConnected = true;
            reconnectAttempts = 0;
            console.log('SSE connection established');
        };
        
        eventSource.onerror = (e) => {
            console.log('SSE connection error:', e);
            EventBus.emit('connection:status', {
                status: 'disconnected',
                message: 'Disconnected'
            });
            isConnected = false;
            eventSource.close();
            
            // Exponential backoff for reconnection
            reconnectAttempts++;
            if (reconnectAttempts <= Config.sse.maxReconnectAttempts) {
                const delay = Math.min(
                    Config.sse.baseReconnectDelay * Math.pow(2, reconnectAttempts),
                    Config.sse.maxReconnectDelay
                );
                
                console.log(`SSE reconnecting in ${delay}ms (attempt ${reconnectAttempts})`);
                setTimeout(connect, delay);
            } else {
                console.log('Max reconnection attempts reached');
                EventBus.emit('connection:status', {
                    status: 'failed',
                    message: 'Connection failed'
                });
            }
        };
        
        setupEventListeners();
    };
    
    const setupEventListeners = () => {
        eventSource.addEventListener('user_created', (e) => {
            console.log('SSE: User created, refreshing data');
            EventBus.emit('users:refresh');
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
        
        // Listen for event changes that might affect user statistics
        ['create', 'update', 'delete'].forEach(eventType => {
            eventSource.addEventListener(eventType, (e) => {
                console.log(`SSE: Event ${eventType}, may need to refresh user stats`);
                // Debounce refresh to avoid too many updates
                clearTimeout(window.userStatsRefreshTimeout);
                window.userStatsRefreshTimeout = setTimeout(() => {
                    EventBus.emit('users:refresh');
                }, 2000);
                lastEventId = parseInt(e.lastEventId) || lastEventId;
            });
        });
    };
    
    const disconnect = () => {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
            isConnected = false;
        }
    };
    
    return {
        connect,
        disconnect
    };
})();

// =============================================================================
// APPLICATION CONTROLLER
// =============================================================================

/**
 * Enhanced application controller with better error handling
 */
const UsersApp = (() => {
    const setupEventListeners = () => {
        // Refresh button
        const refreshBtn = document.getElementById('refreshUsersBtn');
        refreshBtn?.addEventListener('click', async () => {
            try {
                console.log('Manual refresh triggered');
                const users = await UsersDataManager.refreshData();
                DataTablesManager.loadData(users);
                
                // Show success feedback
                const originalText = refreshBtn.textContent;
                refreshBtn.textContent = '✓ Refreshed';
                refreshBtn.style.background = '#48bb78';
                refreshBtn.style.color = 'white';
                
                setTimeout(() => {
                    refreshBtn.textContent = originalText;
                    refreshBtn.style.background = '';
                    refreshBtn.style.color = '';
                }, 2000);
                
            } catch (error) {
                console.error('Error refreshing data:', error);
                UIManager.showError('Failed to refresh data. Please try again.');
            }
        });
        
        // Event listeners
        EventBus.on('users:loaded', ({ users }) => {
            console.log('Event: users:loaded', users);
            DataTablesManager.loadData(users);
        });
        
        EventBus.on('users:refresh', async () => {
            try {
                console.log('Event: users:refresh triggered');
                const users = await UsersDataManager.refreshData();
                DataTablesManager.loadData(users);
            } catch (error) {
                console.error('Error refreshing users:', error);
                UIManager.showError('Failed to refresh user data');
            }
        });
    };
    
    const init = async () => {
        console.log('Initializing Enhanced Users Management Page...');
        
        // Check authentication first
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            // User is not authenticated, AuthGuard will handle redirect
            return;
        }
        
        // Initialize components
        DataTablesManager.initializeDataTable();
        setupEventListeners();
        
        // Load initial data with enhanced error handling
        try {
            console.log('Loading initial users data...');
            const users = await UsersDataManager.loadUsersData();
            DataTablesManager.loadData(users);
            console.log('Initial data loaded successfully');
        } catch (error) {
            console.error('Error loading initial data:', error);
            UIManager.showError('Failed to load users data. Please refresh the page or contact support.');
        }
        
        // Start SSE connection
        SSEManager.connect();
        
        console.log('Enhanced Users Management Page initialized successfully');
    };
    
    const destroy = () => {
        SSEManager.disconnect();
        
        if (DataTablesManager.getDataTable()) {
            DataTablesManager.getDataTable().destroy();
        }
        
        console.log('Users Management Page destroyed');
    };
    
    return {
        init,
        destroy
    };
})();

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Users app...');
    UsersApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    UsersApp.destroy();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        console.log('Page loaded from cache, checking auth...');
        AuthGuard.checkAuthentication();
    }
});