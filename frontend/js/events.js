// Enhanced Events Management Page with Advanced Filtering and Real-time Updates
// Location: frontend/js/events.js
// 
// Comprehensive events table with real-time updates, filtering, and management capabilities

// Export functionality for the events table
const ExportManager = {
    exportToCSV() {
        const table = $('#eventsTable').DataTable();
        const data = table.data();
        let csv = 'Title,Start Date/Time,End Date/Time,Duration,Owner,Status\n';
        
        data.each(function(row) {
            csv += `"${row.title}","${row.start}","${row.end}","${row.duration}","${row.owner}","${row.status}"\n`;
        });
        
        this.downloadFile(csv, 'events.csv', 'text/csv');
    },
    
    exportToExcel() {
        // Use DataTables export functionality
        $('#eventsTable').DataTable().button('excel').trigger();
    },
    
    exportToPDF() {
        // Use DataTables export functionality
        $('#eventsTable').DataTable().button('pdf').trigger();
    },
    
    printTable() {
        // Use DataTables print functionality
        $('#eventsTable').DataTable().button('print').trigger();
    },
    
    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type: type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
};

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
 * Enhanced utility functions for events
 */
const Utils = {
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
    
    formatDateOnly(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid';
        }
        
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatTimeOnly(dateTimeString) {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00' || dateTimeString === null) {
            return 'Not set';
        }
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) {
            return 'Invalid';
        }
        
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    calculateDuration(startDateTime, endDateTime) {
        if (!startDateTime || !endDateTime) {
            return 'Unknown';
        }
        
        const start = new Date(startDateTime);
        const end = new Date(endDateTime);
        
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            return 'Invalid';
        }
        
        const diffMs = end - start;
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        if (diffHours === 0) {
            return `${diffMinutes}m`;
        } else if (diffMinutes === 0) {
            return `${diffHours}h`;
        } else {
            return `${diffHours}h ${diffMinutes}m`;
        }
    },
    
    getEventStatus(startDateTime, endDateTime) {
        const now = new Date();
        const start = new Date(startDateTime);
        const end = new Date(endDateTime);
        
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            return 'unknown';
        }
        
        if (now < start) {
            return 'upcoming';
        } else if (now >= start && now <= end) {
            return 'ongoing';
        } else {
            return 'past';
        }
    },
    
    getRelativeTime(dateTimeString) {
        if (!dateTimeString) return 'Unknown';
        
        const date = new Date(dateTimeString);
        const now = new Date();
        const diffMs = date - now;
        const absDiffMs = Math.abs(diffMs);
        
        const diffMinutes = Math.floor(absDiffMs / (1000 * 60));
        const diffHours = Math.floor(absDiffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(absDiffMs / (1000 * 60 * 60 * 24));
        
        if (diffMs > 0) { // Future
            if (diffMinutes < 60) {
                return diffMinutes <= 1 ? 'Starting soon' : `In ${diffMinutes} min`;
            } else if (diffHours < 24) {
                return diffHours === 1 ? 'In 1 hour' : `In ${diffHours} hours`;
            } else if (diffDays < 7) {
                return diffDays === 1 ? 'Tomorrow' : `In ${diffDays} days`;
            } else {
                return Utils.formatDateOnly(dateTimeString);
            }
        } else { // Past
            if (diffMinutes < 60) {
                return diffMinutes <= 1 ? 'Just ended' : `${diffMinutes} min ago`;
            } else if (diffHours < 24) {
                return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
            } else if (diffDays < 7) {
                return diffDays === 1 ? 'Yesterday' : `${diffDays} days ago`;
            } else {
                return Utils.formatDateOnly(dateTimeString);
            }
        }
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
    
    truncateText(text, maxLength = 50) {
        if (!text || text.length <= maxLength) {
            return text || '';
        }
        return text.substring(0, maxLength) + '...';
    }
};

// =============================================================================
// API CLIENT WITH ENHANCED ERROR HANDLING
// =============================================================================

/**
 * Enhanced API communication for events
 */
const APIClient = (() => {
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
                EventBus.emit('auth:unauthorized');
                throw new Error('Authentication required');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
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
        
        // User operations
        getUsers() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users`);
        },
        
        // Event operations
        getAllEvents(userIds = []) {
            const userIdsParam = userIds.length ? `&user_ids=${userIds.join(',')}` : '';
            return makeRequest(`${Config.apiEndpoints.api}?action=events${userIdsParam}`);
        },
        
        getEventsByUser(userId, startDate = null, endDate = null) {
            let url = `${Config.apiEndpoints.api}?action=events_by_user&user_id=${userId}`;
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            return makeRequest(url);
        },
        
        getEventsInRange(startDate, endDate, userIds = []) {
            let url = `${Config.apiEndpoints.api}?action=events_range&start_date=${startDate}&end_date=${endDate}`;
            if (userIds.length) url += `&user_ids=${userIds.join(',')}`;
            return makeRequest(url);
        },
        
        searchEvents(query, userId = null, limit = 20) {
            let url = `${Config.apiEndpoints.api}?action=search_events&query=${encodeURIComponent(query)}&limit=${limit}`;
            if (userId) url += `&user_id=${userId}`;
            return makeRequest(url);
        },
        
        getUpcomingEvents(limit = 10) {
            return makeRequest(`${Config.apiEndpoints.api}?action=upcoming_events&limit=${limit}`);
        },
        
        updateEvent(eventData) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'PUT',
                body: JSON.stringify(eventData)
            });
        },
        
        deleteEvent(eventId) {
            return makeRequest(`${Config.apiEndpoints.api}?id=${eventId}`, {
                method: 'DELETE'
            });
        },
        
        createEvent(eventData) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify(eventData)
            });
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
        window.location.href = '../login.php';
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
 * Enhanced UI management for events page
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
        
        setTimeout(() => {
            errorEl.remove();
        }, 5000);
        
        errorEl.querySelector('.close').addEventListener('click', () => {
            errorEl.remove();
        });
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
        
        setTimeout(() => {
            successEl.remove();
        }, 3000);
        
        successEl.querySelector('.close').addEventListener('click', () => {
            successEl.remove();
        });
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
                    AuthGuard.logout();
                }
            });
            
            userSection.appendChild(logoutBtn);
        }
    };
    
    const updateEventsSummary = (stats) => {
        document.getElementById('totalEventsCount').textContent = stats.total || 0;
        document.getElementById('upcomingEventsCount').textContent = stats.upcoming || 0;
        document.getElementById('ongoingEventsCount').textContent = stats.ongoing || 0;
        document.getElementById('myEventsCount').textContent = stats.myEvents || 0;
    };
    
    // Event listeners
    EventBus.on('connection:status', ({ status, message }) => {
        updateConnectionStatus(message, status);
    });
    
    EventBus.on('auth:authenticated', ({ user }) => {
        setupAuthenticatedUI(user);
    });
    
    EventBus.on('events:loading', () => {
        showLoadingOverlay();
    });
    
    EventBus.on('events:loaded', () => {
        hideLoadingOverlay();
    });
    
    EventBus.on('events:error', ({ error }) => {
        hideLoadingOverlay();
        showError(error.message || 'Failed to load events data');
    });
    
    EventBus.on('event:saved', ({ message }) => {
        showSuccess(message || 'Event saved successfully');
    });
    
    EventBus.on('event:deleted', ({ message }) => {
        showSuccess(message || 'Event deleted successfully');
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showLoadingOverlay,
        hideLoadingOverlay,
        showError,
        showSuccess,
        setupAuthenticatedUI,
        updateEventsSummary
    };
})();

// =============================================================================
// EVENTS DATA MANAGER
// =============================================================================

/**
 * Enhanced events data management with filtering and caching
 */
const EventsDataManager = (() => {
    let eventsData = [];
    let usersData = [];
    let lastFetchTime = 0;
    let currentFilters = {
        view: 'all',
        status: '',
        user: ''
    };
    
    const loadEventsData = async () => {
        try {
            EventBus.emit('events:loading');
            
            console.log('Loading events data...');
            
            // Load all events
            const events = await APIClient.getAllEvents();
            console.log('Successfully loaded events:', events);
            
            // Validate and process events
            if (!Array.isArray(events)) {
                throw new Error('Invalid events data format received from server');
            }
            
            eventsData = events.map(event => {
                const validatedEvent = Utils.validateEventData(event);
                return {
                    ...validatedEvent,
                    status: Utils.getEventStatus(validatedEvent.start, validatedEvent.end),
                    duration: Utils.calculateDuration(validatedEvent.start, validatedEvent.end),
                    relativeTime: Utils.getRelativeTime(validatedEvent.start)
                };
            });
            
            lastFetchTime = Date.now();
            
            EventBus.emit('events:loaded', { events: eventsData });
            return eventsData;
            
        } catch (error) {
            console.error('Error loading events data:', error);
            EventBus.emit('events:error', { error });
            throw error;
        }
    };
    
    const loadUsersData = async () => {
        try {
            const users = await APIClient.getUsers();
            usersData = users;
            EventBus.emit('users:loaded', { users });
            return users;
        } catch (error) {
            console.error('Error loading users data:', error);
            throw error;
        }
    };
    
    const getFilteredEvents = () => {
        const currentUser = AuthGuard.getCurrentUser();
        if (!currentUser) return [];
        
        let filtered = [...eventsData];
        
        // Filter by view (all, my, others)
        if (currentFilters.view === 'my') {
            filtered = filtered.filter(event => 
                event.extendedProps.userId === currentUser.id
            );
        } else if (currentFilters.view === 'others') {
            filtered = filtered.filter(event => 
                event.extendedProps.userId !== currentUser.id
            );
        }
        
        // Filter by status
        if (currentFilters.status) {
            filtered = filtered.filter(event => event.status === currentFilters.status);
        }
        
        // Filter by user
        if (currentFilters.user) {
            const userId = parseInt(currentFilters.user);
            filtered = filtered.filter(event => 
                event.extendedProps.userId === userId
            );
        }
        
        return filtered;
    };
    
    const updateFilters = (newFilters) => {
        currentFilters = { ...currentFilters, ...newFilters };
        EventBus.emit('events:filtered', { events: getFilteredEvents() });
    };
    
    const calculateSummaryStats = () => {
        const currentUser = AuthGuard.getCurrentUser();
        if (!currentUser) return {};
        
        const now = new Date();
        const stats = {
            total: eventsData.length,
            upcoming: 0,
            ongoing: 0,
            past: 0,
            myEvents: 0
        };
        
        eventsData.forEach(event => {
            const start = new Date(event.start);
            const end = new Date(event.end);
            
            if (now < start) {
                stats.upcoming++;
            } else if (now >= start && now <= end) {
                stats.ongoing++;
            } else {
                stats.past++;
            }
            
            if (event.extendedProps.userId === currentUser.id) {
                stats.myEvents++;
            }
        });
        
        return stats;
    };
    
    const refreshData = () => {
        console.log('Refreshing events data...');
        return loadEventsData();
    };
    
    const getEventsData = () => eventsData;
    const getUsersData = () => usersData;
    const getCurrentFilters = () => currentFilters;
    const getLastFetchTime = () => lastFetchTime;
    
    return {
        loadEventsData,
        loadUsersData,
        getFilteredEvents,
        updateFilters,
        calculateSummaryStats,
        refreshData,
        getEventsData,
        getUsersData,
        getCurrentFilters,
        getLastFetchTime
    };
})();

// =============================================================================
// DATATABLES MANAGER
// =============================================================================

/**
 * Enhanced DataTables management for events
 */
const DataTablesManager = (() => {
    let dataTable = null;
    
    const initializeDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        
        console.log('Initializing Events DataTable...');
        
        dataTable = $('#eventsTable').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'asc']], // Sort by Start Date (upcoming first)
            dom: '<"top"<"entries-section"l><"search-section"f>>Brt<"bottom"<"info-section"i><"pagination-section"p>>',
            pagingType: 'simple_numbers',
            buttons: [
                {
                    extend: 'csv',
                    text: '📄 Export CSV',
                    filename: 'events_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // Exclude actions column (6)
                    }
                },
                {
                    extend: 'excel',
                    text: '📊 Export Excel',
                    filename: 'events_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // Exclude actions column (6)
                    }
                },
                {
                    extend: 'pdf',
                    text: '📑 Export PDF',
                    filename: 'events_export_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    },
                    customize: function(doc) {
                        doc.content[1].table.widths = ['25%', '18%', '18%', '12%', '15%', '12%'];
                        doc.styles.tableHeader.fontSize = 10;
                        doc.defaultStyle.fontSize = 9;
                    }
                },
                {
                    extend: 'print',
                    text: '🖨️ Print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ],
            columnDefs: [
                {
                    targets: 0, // Title column
                    width: '25%'
                },
                {
                    targets: [1, 2], // Date columns
                    width: '18%',
                    className: 'text-nowrap'
                },
                {
                    targets: 3, // Duration column
                    width: '12%',
                    className: 'text-center'
                },
                {
                    targets: 4, // Owner column
                    width: '15%'
                },
                {
                    targets: 5, // Status column
                    width: '12%',
                    className: 'text-center'
                },
                {
                    targets: 6, // Actions column
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    width: '120px'
                }
            ],
            language: {
                search: "Search events:",
                lengthMenu: "Show _MENU_ events per page",
                info: "Showing _START_ to _END_ of _TOTAL_ events",
                infoEmpty: "No events found",
                infoFiltered: "(filtered from _MAX_ total events)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                emptyTable: "No events found in the system"
            },
            drawCallback: function() {
                console.log('Events DataTable redrawn');
            }
        });
        
        console.log('Events DataTable initialized successfully');
    };
    
    const loadData = (events) => {
        if (!dataTable) {
            console.error('DataTable not initialized');
            return;
        }
        
        console.log('Loading events data into DataTable:', events);
        
        if (!Array.isArray(events)) {
            console.error('Invalid events data provided to DataTable');
            return;
        }
        
        // Clear existing data
        dataTable.clear();
        
        // Add new data
        events.forEach((event, index) => {
            try {
                const validatedEvent = Utils.validateEventData(event);
                const currentUser = AuthGuard.getCurrentUser();
                
                const row = [
                    createTitleCell(validatedEvent.title),
                    createDateTimeCell(validatedEvent.start),
                    createDateTimeCell(validatedEvent.end),
                    createDurationCell(validatedEvent.start, validatedEvent.end),
                    createOwnerCell(validatedEvent.extendedProps),
                    createStatusBadge(Utils.getEventStatus(validatedEvent.start, validatedEvent.end)),
                    createActionsCell(validatedEvent, currentUser)
                ];
                
                dataTable.row.add(row);
            } catch (error) {
                console.error(`Error processing event ${index}:`, error, event);
            }
        });
        
        // Redraw table
        dataTable.draw();
        
        console.log(`Successfully loaded ${events.length} events into DataTable`);
        
        // Update summary stats
        const stats = EventsDataManager.calculateSummaryStats();
        UIManager.updateEventsSummary(stats);
    };
    
    const createTitleCell = (title) => {
        const truncated = Utils.truncateText(title, 40);
        if (truncated !== title) {
            return `<span title="${title}">${truncated}</span>`;
        }
        return title;
    };
    
    const createDateTimeCell = (dateTime) => {
        if (!dateTime) return 'Not set';
        
        const formatted = Utils.formatDateTime(dateTime);
        const relative = Utils.getRelativeTime(dateTime);
        
        return `
            <div class="datetime-cell">
                <div class="datetime-main">${Utils.formatDateOnly(dateTime)}</div>
                <div class="datetime-time">${Utils.formatTimeOnly(dateTime)}</div>
                <div class="datetime-relative">${relative}</div>
            </div>
        `;
    };
    
    const createDurationCell = (start, end) => {
        return Utils.calculateDuration(start, end);
    };
    
    const createOwnerCell = (extendedProps) => {
        const userName = extendedProps.userName || 'Unknown';
        const userColor = extendedProps.userColor || '#3498db';
        
        return `
            <div class="owner-cell">
                <div class="user-color-indicator" style="background-color: ${userColor}"></div>
                <span class="user-name">${userName}</span>
            </div>
        `;
    };
    
    const createStatusBadge = (status) => {
        const statusMap = {
            'upcoming': { class: 'upcoming', text: 'Upcoming', icon: '🔮' },
            'ongoing': { class: 'ongoing', text: 'Ongoing', icon: '🔴' },
            'past': { class: 'past', text: 'Past', icon: '✅' },
            'unknown': { class: 'unknown', text: 'Unknown', icon: '❓' }
        };
        
        const statusInfo = statusMap[status] || statusMap['unknown'];
        return `<span class="status-badge ${statusInfo.class}">${statusInfo.icon} ${statusInfo.text}</span>`;
    };
    
    const createActionsCell = (event, currentUser) => {
        const canEdit = currentUser && event.extendedProps.userId === currentUser.id;
        
        let actions = `<button class="btn btn-small btn-outline view-event-btn" data-event-id="${event.id}">👁️ View</button>`;
        
        if (canEdit) {
            actions += ` <button class="btn btn-small btn-primary edit-event-btn" data-event-id="${event.id}">✏️ Edit</button>`;
        }
        
        return `<div class="actions-cell">${actions}</div>`;
    };
    
    const refreshTable = () => {
        if (dataTable) {
            EventBus.emit('events:refresh');
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
// FILTER MANAGER
// =============================================================================

/**
 * Manages filtering controls and user selection
 */
const FilterManager = (() => {
    const initializeFilters = () => {
        // Populate user filter dropdown
        populateUserFilter();
        
        // Set up event listeners
        setupFilterEventListeners();
    };
    
    const populateUserFilter = async () => {
        try {
            const users = await EventsDataManager.loadUsersData();
            const userFilter = document.getElementById('eventUserFilter');
            
            if (userFilter) {
                // Clear existing options except "All Users"
                userFilter.innerHTML = '<option value="">All Users</option>';
                
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    userFilter.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error populating user filter:', error);
        }
    };
    
    const setupFilterEventListeners = () => {
        // View filter
        const viewFilter = document.getElementById('eventViewFilter');
        if (viewFilter) {
            viewFilter.addEventListener('change', (e) => {
                EventsDataManager.updateFilters({ view: e.target.value });
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('eventStatusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                EventsDataManager.updateFilters({ status: e.target.value });
            });
        }
        
        // User filter
        const userFilter = document.getElementById('eventUserFilter');
        if (userFilter) {
            userFilter.addEventListener('change', (e) => {
                EventsDataManager.updateFilters({ user: e.target.value });
            });
        }
    };
    
    const resetFilters = () => {
        document.getElementById('eventViewFilter').value = 'all';
        document.getElementById('eventStatusFilter').value = '';
        document.getElementById('eventUserFilter').value = '';
        
        EventsDataManager.updateFilters({
            view: 'all',
            status: '',
            user: ''
        });
    };
    
    return {
        initializeFilters,
        populateUserFilter,
        resetFilters
    };
})();

// =============================================================================
// EVENT MODAL MANAGER
// =============================================================================

/**
 * Manages event viewing and editing modal
 */
const EventModalManager = (() => {
    let currentEvent = null;
    let editMode = false;
    
    const openModal = (eventId, mode = 'view') => {
        if (mode === 'create') {
            currentEvent = null;
            editMode = true;
        } else {
            const event = EventsDataManager.getEventsData().find(e => e.id == eventId);
            if (!event) {
                UIManager.showError('Event not found');
                return;
            }
            
            currentEvent = event;
            editMode = mode === 'edit';
        }
        
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const eventDetails = document.getElementById('eventDetails');
        const eventForm = document.getElementById('eventForm');
        
        if (!modal) return;
        
        // Set modal title
        if (mode === 'create') {
            modalTitle.textContent = 'Create New Event';
        } else {
            modalTitle.textContent = editMode ? 'Edit Event' : 'Event Details';
        }
        
        if (editMode) {
            // Show form, hide details
            eventDetails.style.display = 'none';
            eventForm.style.display = 'block';
            if (mode === 'create') {
                populateCreateForm();
            } else {
                populateEditForm(currentEvent);
            }
        } else {
            // Show details, hide form
            eventDetails.style.display = 'block';
            eventForm.style.display = 'none';
            populateEventDetails(currentEvent);
        }
        
        modal.style.display = 'block';
    };
    
    const populateEventDetails = (event) => {
        const currentUser = AuthGuard.getCurrentUser();
        const canEdit = currentUser && event.extendedProps.userId === currentUser.id;
        
        const details = document.getElementById('eventDetails');
        const status = Utils.getEventStatus(event.start, event.end);
        const duration = Utils.calculateDuration(event.start, event.end);
        
        details.innerHTML = `
            <div class="event-detail-grid">
                <div class="detail-row">
                    <label>Title:</label>
                    <span>${event.title}</span>
                </div>
                <div class="detail-row">
                    <label>Start:</label>
                    <span>${Utils.formatDateTime(event.start)}</span>
                </div>
                <div class="detail-row">
                    <label>End:</label>
                    <span>${Utils.formatDateTime(event.end)}</span>
                </div>
                <div class="detail-row">
                    <label>Duration:</label>
                    <span>${duration}</span>
                </div>
                <div class="detail-row">
                    <label>Owner:</label>
                    <span>
                        <div class="owner-cell">
                            <div class="user-color-indicator" style="background-color: ${event.backgroundColor}"></div>
                            <span class="user-name">${event.extendedProps.userName}</span>
                        </div>
                    </span>
                </div>
                <div class="detail-row">
                    <label>Status:</label>
                    <span>${createStatusBadge(status)}</span>
                </div>
            </div>
            <div class="event-actions">
                ${canEdit ? `
                    <button class="btn btn-primary" onclick="EventModalManager.switchToEditMode()">✏️ Edit Event</button>
                    <button class="btn btn-danger" onclick="EventModalManager.deleteCurrentEvent()">🗑️ Delete Event</button>
                ` : ''}
                <button class="btn btn-outline" onclick="EventModalManager.closeModal()">Close</button>
            </div>
        `;
    };
    
    const populateCreateForm = () => {
        document.getElementById('eventTitle').value = '';
        
        // Set default start time to current time rounded to next hour
        const now = new Date();
        const startTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours() + 1, 0);
        const endTime = new Date(startTime.getTime() + (60 * 60 * 1000)); // 1 hour later
        
        document.getElementById('eventStart').value = formatDateTimeLocal(startTime);
        document.getElementById('eventEnd').value = formatDateTimeLocal(endTime);
    };

    const populateEditForm = (event) => {
        document.getElementById('eventTitle').value = event.title;
        
        // Convert to datetime-local format
        const startDate = new Date(event.start);
        const endDate = new Date(event.end);
        
        document.getElementById('eventStart').value = formatDateTimeLocal(startDate);
        document.getElementById('eventEnd').value = formatDateTimeLocal(endDate);
    };
    
    const formatDateTimeLocal = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    
    const createStatusBadge = (status) => {
        return DataTablesManager.createStatusBadge ? 
            DataTablesManager.createStatusBadge(status) : 
            `<span class="status-badge ${status}">${status}</span>`;
    };
    
    const switchToEditMode = () => {
        if (!currentEvent) return;
        
        editMode = true;
        document.getElementById('modalTitle').textContent = 'Edit Event';
        document.getElementById('eventDetails').style.display = 'none';
        document.getElementById('eventForm').style.display = 'block';
        populateEditForm(currentEvent);
    };
    
    const closeModal = () => {
        const modal = document.getElementById('eventModal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        currentEvent = null;
        editMode = false;
    };
    
    const saveEvent = async () => {
        try {
            const title = document.getElementById('eventTitle').value.trim();
            const start = document.getElementById('eventStart').value;
            const end = document.getElementById('eventEnd').value;
            
            if (!title || !start || !end) {
                UIManager.showError('All fields are required');
                return;
            }
            
            const eventData = {
                title: title,
                start: start + ':00', // Add seconds
                end: end + ':00'
            };
            
            if (currentEvent) {
                // Updating existing event
                eventData.id = currentEvent.id;
                await APIClient.updateEvent(eventData);
                EventBus.emit('event:saved', { message: 'Event updated successfully' });
            } else {
                // Creating new event
                await APIClient.createEvent(eventData);
                EventBus.emit('event:saved', { message: 'Event created successfully' });
            }
            
            closeModal();
            EventsDataManager.refreshData();
            
        } catch (error) {
            console.error('Error saving event:', error);
            UIManager.showError(`Failed to save event: ${error.message}`);
        }
    };
    
    const deleteCurrentEvent = async () => {
        if (!currentEvent) return;
        
        if (!confirm(`Are you sure you want to delete "${currentEvent.title}"?`)) {
            return;
        }
        
        try {
            await APIClient.deleteEvent(currentEvent.id);
            EventBus.emit('event:deleted', { message: 'Event deleted successfully' });
            closeModal();
            EventsDataManager.refreshData();
            
        } catch (error) {
            console.error('Error deleting event:', error);
            UIManager.showError(`Failed to delete event: ${error.message}`);
        }
    };
    
    const setupEventListeners = () => {
        // Modal close events
        const modal = document.getElementById('eventModal');
        const closeBtn = modal?.querySelector('.close');
        const cancelBtn = document.getElementById('cancelEditBtn');
        
        [closeBtn, cancelBtn].forEach(btn => {
            btn?.addEventListener('click', closeModal);
        });
        
        // Close on outside click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Form submission
        const editForm = document.getElementById('editEventForm');
        editForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            saveEvent();
        });
        
        // Delete button
        const deleteBtn = document.getElementById('deleteEventBtn');
        deleteBtn?.addEventListener('click', deleteCurrentEvent);
    };
    
    // Expose methods to global scope for onclick handlers
    window.EventModalManager = {
        switchToEditMode,
        closeModal,
        deleteCurrentEvent
    };
    
    return {
        openModal,
        closeModal,
        switchToEditMode,
        saveEvent,
        deleteCurrentEvent,
        setupEventListeners
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
        // Handle event changes
        ['create', 'update', 'delete'].forEach(eventType => {
            eventSource.addEventListener(eventType, (e) => {
                try {
                    const eventData = JSON.parse(e.data);
                    console.log(`SSE: Event ${eventType}`, eventData);
                    
                    // Refresh events data to get latest
                    EventBus.emit('events:refresh');
                    
                    lastEventId = parseInt(e.lastEventId) || lastEventId;
                } catch (error) {
                    console.error(`Error handling SSE ${eventType} event:`, error);
                }
            });
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
    
    return {
        connect,
        disconnect
    };
})();

// =============================================================================
// APPLICATION CONTROLLER
// =============================================================================

/**
 * Main application controller for events page
 */
const EventsApp = (() => {
    const setupEventListeners = () => {
        // Refresh button
        const refreshBtn = document.getElementById('refreshEventsBtn');
        refreshBtn?.addEventListener('click', async () => {
            try {
                console.log('Manual refresh triggered');
                const events = await EventsDataManager.refreshData();
                DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
                
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
        
        // Add event button
        const addEventBtn = document.getElementById('addEventBtn');
        addEventBtn?.addEventListener('click', () => {
            EventModalManager.openModal(null, 'create');
        });
        
        // Table row action handlers
        $(document).on('click', '.view-event-btn', function() {
            const eventId = $(this).data('event-id');
            EventModalManager.openModal(eventId, 'view');
        });
        
        $(document).on('click', '.edit-event-btn', function() {
            const eventId = $(this).data('event-id');
            EventModalManager.openModal(eventId, 'edit');
        });
        
        // Event listeners
        EventBus.on('events:loaded', ({ events }) => {
            console.log('Event: events:loaded', events);
            DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
        });
        
        EventBus.on('events:filtered', ({ events }) => {
            console.log('Event: events:filtered', events);
            DataTablesManager.loadData(events);
        });
        
        EventBus.on('events:refresh', async () => {
            try {
                console.log('Event: events:refresh triggered');
                const events = await EventsDataManager.refreshData();
                DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
            } catch (error) {
                console.error('Error refreshing events:', error);
                UIManager.showError('Failed to refresh events data');
            }
        });
    };
    
    const init = async () => {
        console.log('Initializing Events Management Page...');
        
        // Check authentication first
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            // User is not authenticated, AuthGuard will handle redirect
            return;
        }
        
        // Initialize components
        DataTablesManager.initializeDataTable();
        FilterManager.initializeFilters();
        EventModalManager.setupEventListeners();
        setupEventListeners();
        
        // Load initial data
        try {
            console.log('Loading initial events data...');
            const events = await EventsDataManager.loadEventsData();
            DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
            console.log('Initial data loaded successfully');
        } catch (error) {
            console.error('Error loading initial data:', error);
            UIManager.showError('Failed to load events data. Please refresh the page or contact support.');
        }
        
        // Start SSE connection
        SSEManager.connect();
        
        console.log('Events Management Page initialized successfully');
    };
    
    const destroy = () => {
        SSEManager.disconnect();
        
        if (DataTablesManager.getDataTable()) {
            DataTablesManager.getDataTable().destroy();
        }
        
        console.log('Events Management Page destroyed');
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
    console.log('DOM loaded, initializing Events app...');
    EventsApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    EventsApp.destroy();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        console.log('Page loaded from cache, checking auth...');
        AuthGuard.checkAuthentication();
    }
});