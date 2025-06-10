// Events Page JavaScript - Ice Time Management
// Location: frontend/assets/js/events-page.js
// 
// Page-specific events management functionality (requires core.js to be loaded first)

(function() {
    'use strict';
    
    // Check if core utilities are available
    if (!window.IceTimeApp) {
        console.error('Core utilities not loaded. Please ensure core.js is loaded first.');
        return;
    }
    
    const { EventBus, Config, Utils, APIClient, AuthGuard, UIManager, SSEManager } = window.IceTimeApp;
    
    // =============================================================================
    // EVENTS DATA MANAGER
    // =============================================================================
    
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
                
                const events = await APIClient.getEvents();
                console.log('Successfully loaded events:', events);
                
                if (!Array.isArray(events)) {
                    throw new Error('Invalid events data format received from server');
                }
                
                eventsData = events.map(event => {
                    const validatedEvent = Utils.validateEventData(event);
                    return {
                        ...validatedEvent,
                        status: getEventStatus(validatedEvent.start, validatedEvent.end),
                        duration: calculateDuration(validatedEvent.start, validatedEvent.end),
                        relativeTime: getRelativeTime(validatedEvent.start)
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
            
            if (currentFilters.view === 'my') {
                filtered = filtered.filter(event => 
                    event.extendedProps.userId === currentUser.id
                );
            } else if (currentFilters.view === 'others') {
                filtered = filtered.filter(event => 
                    event.extendedProps.userId !== currentUser.id
                );
            }
            
            if (currentFilters.status) {
                filtered = filtered.filter(event => event.status === currentFilters.status);
            }
            
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
        
        // Helper functions
        const getEventStatus = (startDateTime, endDateTime) => {
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
        };
        
        const calculateDuration = (startDateTime, endDateTime) => {
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
        };
        
        const getRelativeTime = (dateTimeString) => {
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
                    return Utils.formatDateTime(dateTimeString).split(' ')[0];
                }
            } else { // Past
                if (diffMinutes < 60) {
                    return diffMinutes <= 1 ? 'Just ended' : `${diffMinutes} min ago`;
                } else if (diffHours < 24) {
                    return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
                } else if (diffDays < 7) {
                    return diffDays === 1 ? 'Yesterday' : `${diffDays} days ago`;
                } else {
                    return Utils.formatDateTime(dateTimeString).split(' ')[0];
                }
            }
        };
        
        return {
            loadEventsData,
            loadUsersData,
            getFilteredEvents,
            updateFilters,
            calculateSummaryStats,
            refreshData,
            getEventsData: () => eventsData,
            getUsersData: () => usersData,
            getCurrentFilters: () => currentFilters,
            getLastFetchTime: () => lastFetchTime
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
            
            console.log('Initializing Events DataTable...');
            
            dataTable = $('#eventsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[1, 'asc']], // Sort by Start Date
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv',
                        text: '📄 Export CSV',
                        filename: 'events_export_' + new Date().toISOString().split('T')[0]
                    },
                    {
                        extend: 'excel', 
                        text: '📊 Export Excel',
                        filename: 'events_export_' + new Date().toISOString().split('T')[0]
                    },
                    {
                        extend: 'pdf',
                        text: '📑 Export PDF',
                        filename: 'events_export_' + new Date().toISOString().split('T')[0]
                    },
                    {
                        extend: 'print',
                        text: '🖨️ Print'
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
                    emptyTable: "No events found in the system"
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
            
            dataTable.clear();
            
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
                        createStatusBadge(event.status || 'unknown'),
                        createActionsCell(validatedEvent, currentUser)
                    ];
                    
                    dataTable.row.add(row);
                } catch (error) {
                    console.error(`Error processing event ${index}:`, error, event);
                }
            });
            
            dataTable.draw();
            
            console.log(`Successfully loaded ${events.length} events into DataTable`);
            
            const stats = EventsDataManager.calculateSummaryStats();
            updateEventsSummary(stats);
        };
        
        const createTitleCell = (title) => {
            const truncated = truncateText(title, 40);
            if (truncated !== title) {
                return `<span title="${title}">${truncated}</span>`;
            }
            return title;
        };
        
        const createDateTimeCell = (dateTime) => {
            if (!dateTime) return 'Not set';
            
            const formatted = Utils.formatDateTime(dateTime);
            const dateOnly = formatted.split(' ')[0];
            const timeOnly = formatted.split(' ')[1];
            
            return `
                <div class="datetime-cell">
                    <div class="datetime-main">${dateOnly}</div>
                    <div class="datetime-time">${timeOnly}</div>
                </div>
            `;
        };
        
        const createDurationCell = (start, end) => {
            return EventsDataManager.calculateDuration ? 
                EventsDataManager.calculateDuration(start, end) : 'Unknown';
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
        
        const updateEventsSummary = (stats) => {
            const elements = {
                totalEventsCount: stats.total || 0,
                upcomingEventsCount: stats.upcoming || 0,
                ongoingEventsCount: stats.ongoing || 0,
                myEventsCount: stats.myEvents || 0
            };
            
            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) element.textContent = value;
            });
        };
        
        const truncateText = (text, maxLength = 50) => {
            if (!text || text.length <= maxLength) {
                return text || '';
            }
            return text.substring(0, maxLength) + '...';
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
    
    const FilterManager = (() => {
        const initializeFilters = () => {
            populateUserFilter();
            setupFilterEventListeners();
        };
        
        const populateUserFilter = async () => {
            try {
                const users = await EventsDataManager.loadUsersData();
                const userFilter = document.getElementById('eventUserFilter');
                
                if (userFilter) {
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
            const viewFilter = document.getElementById('eventViewFilter');
            if (viewFilter) {
                viewFilter.addEventListener('change', (e) => {
                    EventsDataManager.updateFilters({ view: e.target.value });
                });
            }
            
            const statusFilter = document.getElementById('eventStatusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', (e) => {
                    EventsDataManager.updateFilters({ status: e.target.value });
                });
            }
            
            const userFilter = document.getElementById('eventUserFilter');
            if (userFilter) {
                userFilter.addEventListener('change', (e) => {
                    EventsDataManager.updateFilters({ user: e.target.value });
                });
            }
        };
        
        const resetFilters = () => {
            const filters = ['eventViewFilter', 'eventStatusFilter', 'eventUserFilter'];
            const values = ['all', '', ''];
            
            filters.forEach((filterId, index) => {
                const element = document.getElementById(filterId);
                if (element) element.value = values[index];
            });
            
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
    
    const EventModalManager = (() => {
        let currentEvent = null;
        let editMode = false;
        
        const openModal = (eventId, mode = 'view') => {
            const event = EventsDataManager.getEventsData().find(e => e.id == eventId);
            if (!event) {
                UIManager.showError('Event not found');
                return;
            }
            
            currentEvent = event;
            editMode = mode === 'edit';
            
            const modal = document.getElementById('eventModal');
            const modalTitle = document.getElementById('modalTitle');
            const eventDetails = document.getElementById('eventDetails');
            const eventForm = document.getElementById('eventForm');
            
            if (!modal) return;
            
            modalTitle.textContent = editMode ? 'Edit Event' : 'Event Details';
            
            if (editMode) {
                eventDetails.style.display = 'none';
                eventForm.style.display = 'block';
                populateEditForm(event);
            } else {
                eventDetails.style.display = 'block';
                eventForm.style.display = 'none';
                populateEventDetails(event);
            }
            
            modal.style.display = 'block';
        };
        
        const populateEventDetails = (event) => {
            const currentUser = AuthGuard.getCurrentUser();
            const canEdit = currentUser && event.extendedProps.userId === currentUser.id;
            
            const details = document.getElementById('eventDetails');
            const status = event.status || 'unknown';
            const duration = event.duration || 'Unknown';
            
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
                        <span>${DataTablesManager.createStatusBadge ? DataTablesManager.createStatusBadge(status) : status}</span>
                    </div>
                </div>
                <div class="event-actions">
                    ${canEdit ? `
                        <button class="btn btn-primary" onclick="window.IceTimeApp.EventsPage.switchToEditMode()">✏️ Edit Event</button>
                        <button class="btn btn-danger" onclick="window.IceTimeApp.EventsPage.deleteCurrentEvent()">🗑️ Delete Event</button>
                    ` : ''}
                    <button class="btn btn-outline" onclick="window.IceTimeApp.EventsPage.closeModal()">Close</button>
                </div>
            `;
        };
        
        const populateEditForm = (event) => {
            document.getElementById('eventTitle').value = event.title;
            
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
            if (!currentEvent) return;
            
            try {
                const title = document.getElementById('eventTitle').value.trim();
                const start = document.getElementById('eventStart').value;
                const end = document.getElementById('eventEnd').value;
                
                if (!title || !start || !end) {
                    UIManager.showError('All fields are required');
                    return;
                }
                
                const eventData = {
                    id: currentEvent.id,
                    title: title,
                    start: start + ':00',
                    end: end + ':00'
                };
                
                await APIClient.updateEvent(eventData);
                EventBus.emit('event:saved', { message: 'Event updated successfully' });
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
            const modal = document.getElementById('eventModal');
            const closeBtn = modal?.querySelector('.close');
            const cancelBtn = document.getElementById('cancelEditBtn');
            
            [closeBtn, cancelBtn].forEach(btn => {
                btn?.addEventListener('click', closeModal);
            });
            
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            
            const editForm = document.getElementById('editEventForm');
            editForm?.addEventListener('submit', (e) => {
                e.preventDefault();
                saveEvent();
            });
            
            const deleteBtn = document.getElementById('deleteEventBtn');
            deleteBtn?.addEventListener('click', deleteCurrentEvent);
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
    // APPLICATION CONTROLLER
    // =============================================================================
    
    const EventsApp = (() => {
        const setupEventListeners = () => {
            const refreshBtn = document.getElementById('refreshEventsBtn');
            refreshBtn?.addEventListener('click', async () => {
                try {
                    console.log('Manual refresh triggered');
                    await EventsDataManager.refreshData();
                    DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
                    
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
            
            const addEventBtn = document.getElementById('addEventBtn');
            addEventBtn?.addEventListener('click', () => {
                window.location.href = './calendar.php';
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
                    await EventsDataManager.refreshData();
                    DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
                } catch (error) {
                    console.error('Error refreshing events:', error);
                    UIManager.showError('Failed to refresh events data');
                }
            });
            
            EventBus.on('event:saved', ({ message }) => {
                UIManager.showSuccess(message || 'Event saved successfully');
            });
            
            EventBus.on('event:deleted', ({ message }) => {
                UIManager.showSuccess(message || 'Event deleted successfully');
            });
            
            EventBus.on('events:loading', () => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.remove('hidden');
            });
            
            EventBus.on('events:loaded', () => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.add('hidden');
            });
            
            EventBus.on('events:error', ({ error }) => {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) overlay.classList.add('hidden');
                UIManager.showError(error.message || 'Failed to load events data');
            });
        };
        
        const init = async () => {
            console.log('Initializing Events Management Page...');
            
            const isAuthenticated = await AuthGuard.checkAuthentication();
            
            if (!isAuthenticated) {
                return;
            }
            
            DataTablesManager.initializeDataTable();
            FilterManager.initializeFilters();
            EventModalManager.setupEventListeners();
            setupEventListeners();
            
            try {
                console.log('Loading initial events data...');
                await EventsDataManager.loadEventsData();
                DataTablesManager.loadData(EventsDataManager.getFilteredEvents());
                console.log('Initial data loaded successfully');
            } catch (error) {
                console.error('Error loading initial data:', error);
                UIManager.showError('Failed to load events data. Please refresh the page or contact support.');
            }
            
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
            destroy,
            switchToEditMode: EventModalManager.switchToEditMode,
            closeModal: EventModalManager.closeModal,
            deleteCurrentEvent: EventModalManager.deleteCurrentEvent
        };
    })();
    
    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    
    // Export to global scope for access from modal onclick handlers
    window.IceTimeApp.EventsPage = EventsApp;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            EventsApp.init();
        });
    } else {
        EventsApp.init();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        EventsApp.destroy();
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            console.log('Page loaded from cache, checking auth...');
            AuthGuard.checkAuthentication();
        }
    });
    
})();