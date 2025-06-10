// Users Page JavaScript - Ice Time Management
// Location: frontend/assets/js/users-page.js
// 
// Page-specific users management functionality (requires core.js to be loaded first)

(function() {
    'use strict';
    
    // Check if core utilities are available
    if (!window.IceTimeApp) {
        console.error('Core utilities not loaded. Please ensure core.js is loaded first.');
        return;
    }
    
    const { EventBus, Config, Utils, APIClient, AuthGuard, UIManager, SSEManager } = window.IceTimeApp;
    
    // =============================================================================
    // USERS DATA MANAGER
    // =============================================================================
    
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
                        ...validateUserData(user),
                        event_count: 0,
                        upcoming_events: 0,
                        past_events: 0,
                        status: getUserStatus(user)
                    }));
                }
                
                // Validate and clean user data
                if (!Array.isArray(users)) {
                    throw new Error('Invalid users data format received from server');
                }
                
                usersData = users.map(user => {
                    const validatedUser = validateUserData(user);
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
        
        const validateUserData = (user) => {
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
        };
        
        const getUserStatus = (user) => {
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
        };
        
        const getTimeAgo = (dateString) => {
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
        };
        
        const formatDateShort = (dateString) => {
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
            getLastFetchTime,
            validateUserData,
            getUserStatus,
            getTimeAgo,
            formatDateShort
        };
    })();
    
    // =============================================================================
    // DATATABLES MANAGER
    // =============================================================================
    
    const DataTablesManager = (() => {
        let dataTable = null;
        
        const initializeDataTable = () => {
            if (dataTable) {
                dataTable.destroy();
                dataTable = null;
            }
            
            console.log('Initializing Users DataTable...');
            
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
            
            console.log('Users DataTable initialized successfully');
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
                    const validatedUser = UsersDataManager.validateUserData(user);
                    
                    const row = [
                        createColorIndicator(validatedUser.color),
                        validatedUser.name,
                        validatedUser.email || 'No email',
                        createEventCountBadge(validatedUser.event_count),
                        UsersDataManager.formatDateShort(validatedUser.created_at),
                        UsersDataManager.getTimeAgo(validatedUser.last_login),
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
    // APPLICATION CONTROLLER
    // =============================================================================
    
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
            
            EventBus.on('users:loading', () => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.remove('hidden');
            });
            
            EventBus.on('users:loaded', () => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.add('hidden');
            });
            
            EventBus.on('users:error', ({ error }) => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.add('hidden');
                UIManager.showError(error.message || 'Failed to load users data');
            });
        };
        
        const init = async () => {
            console.log('Initializing Users Management Page...');
            
            // Check authentication first
            const isAuthenticated = await AuthGuard.checkAuthentication();
            
            if (!isAuthenticated) {
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
    // INITIALIZATION
    // =============================================================================
    
    // Export to global scope
    window.IceTimeApp.UsersApp = UsersApp;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            UsersApp.init();
        });
    } else {
        UsersApp.init();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        UsersApp.destroy();
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            console.log('Page loaded from cache, checking auth...');
            AuthGuard.checkAuthentication();
        }
    });
    
})();