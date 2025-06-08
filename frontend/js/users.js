// Users Management Page with DataTables
// Location: frontend/js/users.js
// 
// Displays all system users in a DataTable with real-time updates

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
        api: '../../backend/api.php',
        sse: '../../backend/workers/sse.php'
    },
    sse: {
        maxReconnectAttempts: 10,
        baseReconnectDelay: 1000,
        maxReconnectDelay: 30000
    }
};

/**
 * Utility functions
 */
const Utils = {
    formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00') {
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
        if (!dateString || dateString === '0000-00-00 00:00:00') {
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
        if (!dateString || dateString === '0000-00-00 00:00:00') {
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
        const now = new Date();
        const lastLogin = new Date(user.last_login);
        
        if (!user.last_login || user.last_login === '0000-00-00 00:00:00') {
            return 'new';
        }
        
        const diffDays = Math.floor((now - lastLogin) / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 1) {
            return 'active';
        } else {
            return 'inactive';
        }
    }
};

// =============================================================================
// API CLIENT WITH AUTHENTICATION
// =============================================================================

/**
 * Centralized API communication with authentication
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
            
            return await response.json();
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
        
        // User operations with event counts
        getUsersWithStats() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users_with_stats`);
        },
        
        // Fallback to regular users endpoint
        getUsers() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users`);
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
 * Manages UI updates and visual feedback
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
    
    EventBus.on('users:error', () => {
        hideLoadingOverlay();
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showLoadingOverlay,
        hideLoadingOverlay,
        setupAuthenticatedUI
    };
})();

// =============================================================================
// USERS DATA MANAGER
// =============================================================================

/**
 * Manages user data and statistics
 */
const UsersDataManager = (() => {
    let usersData = [];
    let eventsData = [];
    
    const loadUsersData = async () => {
        try {
            EventBus.emit('users:loading');
            
            // Load users and events data
            const [users, events] = await Promise.all([
                APIClient.getUsers(),
                fetchEventsData()
            ]);
            
            usersData = users;
            eventsData = events;
            
            // Calculate statistics for each user
            const usersWithStats = usersData.map(user => {
                const userEvents = eventsData.filter(event => event.user_id === user.id);
                
                return {
                    ...user,
                    event_count: userEvents.length,
                    status: Utils.getUserStatus(user)
                };
            });
            
            EventBus.emit('users:loaded', { users: usersWithStats });
            return usersWithStats;
            
        } catch (error) {
            console.error('Error loading users data:', error);
            EventBus.emit('users:error', { error });
            throw error;
        }
    };
    
    const fetchEventsData = async () => {
        try {
            // Get all events for statistics
            const response = await fetch(`${Config.apiEndpoints.api}?action=events`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const events = await response.json();
            return events.map(event => ({
                id: event.id,
                user_id: event.extendedProps.userId,
                title: event.title,
                start: event.start,
                end: event.end
            }));
            
        } catch (error) {
            console.warn('Could not load events data for statistics:', error);
            return []; // Return empty array if events can't be loaded
        }
    };
    
    const refreshData = () => {
        return loadUsersData();
    };
    
    const getUsersData = () => usersData;
    
    return {
        loadUsersData,
        refreshData,
        getUsersData
    };
})();

// =============================================================================
// DATATABLES MANAGER
// =============================================================================

/**
 * Manages DataTables initialization and operations
 */
const DataTablesManager = (() => {
    let dataTable = null;
    
    const initializeDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
        }
        
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
                // Add tooltips or additional formatting here if needed
                console.log('DataTable redrawn');
            }
        });
        
        console.log('DataTable initialized');
    };
    
    const loadData = (users) => {
        if (!dataTable) {
            console.error('DataTable not initialized');
            return;
        }
        
        // Clear existing data
        dataTable.clear();
        
        // Add new data
        users.forEach(user => {
            const row = [
                createColorIndicator(user.color),
                user.name || 'Unknown',
                user.email || 'No email',
                createEventCountBadge(user.event_count),
                Utils.formatDateShort(user.created_at),
                Utils.getTimeAgo(user.last_login),
                createStatusBadge(user.status)
            ];
            
            dataTable.row.add(row);
        });
        
        // Redraw table
        dataTable.draw();
        
        console.log(`Loaded ${users.length} users into DataTable`);
    };
    
    const createColorIndicator = (color) => {
        return `<div class="user-color-indicator" style="background-color: ${color || '#3788d8'}"></div>`;
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
            'inactive': { class: 'inactive', text: 'Inactive' },
            'new': { class: 'new', text: 'New' }
        };
        
        const statusInfo = statusMap[status] || statusMap['inactive'];
        return `<span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
    };
    
    const refreshTable = () => {
        if (dataTable) {
            dataTable.ajax.reload(null, false); // false = don't reset paging
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
 * Main application controller that coordinates all components
 */
const UsersApp = (() => {
    const setupEventListeners = () => {
        // Refresh button
        const refreshBtn = document.getElementById('refreshUsersBtn');
        refreshBtn?.addEventListener('click', async () => {
            try {
                const users = await UsersDataManager.refreshData();
                DataTablesManager.loadData(users);
            } catch (error) {
                console.error('Error refreshing data:', error);
                alert('Error refreshing data. Please try again.');
            }
        });
        
        // Event listeners
        EventBus.on('users:loaded', ({ users }) => {
            DataTablesManager.loadData(users);
        });
        
        EventBus.on('users:refresh', async () => {
            try {
                const users = await UsersDataManager.refreshData();
                DataTablesManager.loadData(users);
            } catch (error) {
                console.error('Error refreshing users:', error);
            }
        });
    };
    
    const init = async () => {
        console.log('Initializing Users Management Page...');
        
        // Check authentication first
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            // User is not authenticated, AuthGuard will handle redirect
            return;
        }
        
        // Initialize components
        DataTablesManager.initializeDataTable();
        setupEventListeners();
        
        // Load initial data
        try {
            const users = await UsersDataManager.loadUsersData();
            DataTablesManager.loadData(users);
        } catch (error) {
            console.error('Error loading initial data:', error);
            alert('Error loading users data. Please refresh the page.');
        }
        
        // Start SSE connection
        SSEManager.connect();
        
        console.log('Users Management Page initialized successfully');
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
        AuthGuard.checkAuthentication();
    }
});