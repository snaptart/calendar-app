/**
 * Events Management Page - Refactored to use modular components
 * Location: frontend/js/events.js
 * 
 * This file has been refactored to eliminate code duplication by using
 * the existing modular components instead of recreating them.
 */

// Import modular components
import { EventBus } from './core/event-bus.js';
import { Config } from './core/config.js';
import { Utils } from './core/utils.js';
import { APIClient } from './core/api-client.js';
import { AuthGuard } from './auth/auth-guard.js';
import { UserManager } from './auth/user-manager.js';
import { UIManager } from './ui/ui-manager.js';
import { ModalManager } from './ui/modal-manager.js';
import { NotificationManager } from './ui/notification-manager.js';
import { TableManager } from './ui/table-manager.js';
import { ExportManager } from './features/export-manager.js';
import { SSEManager } from './realtime/sse-manager.js';

// =============================================================================
// PAGE-SPECIFIC COMPONENTS
// =============================================================================

// Export functionality is now handled by the shared ExportManager

/**
 * Events data management with filtering and caching
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
            
            // Use the modular APIClient
            const events = await APIClient.getEvents();
            console.log('Successfully loaded events:', events);
            
            if (!Array.isArray(events)) {
                throw new Error('Invalid events data format received from server');
            }
            
            eventsData = events.map(event => ({
                ...event,
                status: Utils.getEventStatus(event.start, event.end),
                duration: Utils.calculateDuration(event.start, event.end),
                relativeTime: Utils.getRelativeTime(event.start)
            }));
            
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
        
        // Apply filters
        if (currentFilters.view === 'my') {
            filtered = filtered.filter(event => 
                event.userId === currentUser.id
            );
        } else if (currentFilters.view === 'others') {
            filtered = filtered.filter(event => 
                event.userId !== currentUser.id
            );
        }
        
        if (currentFilters.status) {
            filtered = filtered.filter(event => 
                event.status === currentFilters.status
            );
        }
        
        if (currentFilters.user) {
            filtered = filtered.filter(event => 
                event.userId === parseInt(currentFilters.user)
            );
        }
        
        return filtered;
    };
    
    const updateFilters = (filters) => {
        currentFilters = { ...currentFilters, ...filters };
        EventBus.emit('events:filters-changed', { filters: currentFilters });
    };
    
    const refreshData = async () => {
        await loadEventsData();
        await loadUsersData();
    };
    
    return {
        loadEventsData,
        loadUsersData,
        getFilteredEvents,
        updateFilters,
        refreshData,
        getEventsData: () => [...eventsData],
        getUsersData: () => [...usersData],
        getCurrentFilters: () => ({ ...currentFilters })
    };
})();

/**
 * DataTables management for events
 */
const DataTablesManager = (() => {
    let dataTable = null;
    
    const initializeDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
        }
        
        dataTable = $('#eventsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: Config.apiEndpoints.api,
                type: 'GET',
                data: function(d) {
                    d.action = 'events_datatable';  // Use correct server-side endpoint
                    d.draw = d.draw;
                    d.start = d.start;
                    d.length = d.length;
                    d.search = d.search.value;
                    
                    // Add filters
                    const filters = EventsDataManager.getCurrentFilters();
                    d.filters = JSON.stringify(filters);
                }
            },
            columns: [
                { data: 'title', title: 'Title' },
                { 
                    data: 'start', 
                    title: 'Start',
                    render: (data) => Utils.formatDateTime(data)
                },
                { 
                    data: 'end', 
                    title: 'End',
                    render: (data) => Utils.formatDateTime(data)
                },
                { 
                    data: null, 
                    title: 'Duration',
                    render: (data) => Utils.calculateDuration(data.start, data.end)
                },
                { data: 'owner_name', title: 'Owner' },
                { 
                    data: null, 
                    title: 'Status',
                    render: (data) => {
                        const status = Utils.getEventStatus(data.start, data.end);
                        const badgeClass = status === 'upcoming' ? 'badge-primary' : 
                                         status === 'ongoing' ? 'badge-success' : 'badge-secondary';
                        return `<span class="badge ${badgeClass}">${status}</span>`;
                    }
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: (data) => `
                        <button class="btn btn-sm btn-outline-primary" onclick="EventModalManager.openModal('${data.id}', 'view')">
                            👁️ View
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="EventModalManager.openModal('${data.id}', 'edit')">
                            ✏️ Edit
                        </button>
                    `
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                'excel', 'pdf', 'print',
                {
                    text: 'CSV Export',
                    action: () => ExportManager.exportToCSV()
                }
            ],
            pageLength: 25,
            order: [[1, 'desc']],
            responsive: true
        });
        
        return dataTable;
    };
    
    const refreshTable = () => {
        if (dataTable) {
            dataTable.ajax.reload();
        }
    };
    
    const getDataTable = () => dataTable;
    
    return {
        initializeDataTable,
        refreshTable,
        getDataTable
    };
})();

/**
 * Filter management for events
 */
const FilterManager = (() => {
    const initializeFilters = () => {
        // View filter
        $('#eventViewFilter').on('change', function() {
            EventsDataManager.updateFilters({ view: this.value });
            DataTablesManager.refreshTable();
        });
        
        // Status filter
        $('#eventStatusFilter').on('change', function() {
            EventsDataManager.updateFilters({ status: this.value });
            DataTablesManager.refreshTable();
        });
        
        // User filter
        $('#eventUserFilter').on('change', function() {
            EventsDataManager.updateFilters({ user: this.value });
            DataTablesManager.refreshTable();
        });
        
        // Populate user filter when users are loaded
        EventBus.on('users:loaded', ({ users }) => {
            const userSelect = $('#eventUserFilter');
            userSelect.empty().append('<option value="">All Users</option>');
            
            users.forEach(user => {
                userSelect.append(`<option value="${user.id}">${user.name || user.email}</option>`);
            });
        });
    };
    
    const getSelectedUsers = () => {
        return $('#eventUserFilter').val() ? [parseInt($('#eventUserFilter').val())] : [];
    };
    
    return {
        initializeFilters,
        getSelectedUsers
    };
})();

/**
 * Event modal management
 */
const EventModalManager = (() => {
    let currentEventId = null;
    let currentMode = 'view';
    
    const openModal = async (eventId, mode = 'view') => {
        currentEventId = eventId;
        currentMode = mode;
        
        try {
            if (eventId && mode !== 'create') {
                // Load event data using APIClient
                const events = await APIClient.getEvents();
                const event = events.find(e => e.id == eventId);
                
                if (event) {
                    showEventModal(event, mode);
                } else {
                    UIManager.showError('Event not found');
                }
            } else {
                showEventModal(null, mode);
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            UIManager.showError('Failed to load event data');
        }
    };
    
    const showEventModal = (event, mode) => {
        const modal = ModalManager.create({
            title: mode === 'create' ? 'Create Event' : mode === 'edit' ? 'Edit Event' : 'View Event',
            size: 'large',
            body: generateModalContent(event, mode),
            footer: generateModalFooter(mode)
        });
        
        modal.show();
    };
    
    const generateModalContent = (event, mode) => {
        const isReadonly = mode === 'view';
        const title = event?.title || '';
        const start = event?.start || '';
        const end = event?.end || '';
        const description = event?.description || '';
        
        return `
            <form id="eventForm">
                <div class="form-group">
                    <label for="eventTitle">Title</label>
                    <input type="text" class="form-control" id="eventTitle" value="${title}" ${isReadonly ? 'readonly' : ''} required>
                </div>
                <div class="form-group">
                    <label for="eventStart">Start Date/Time</label>
                    <input type="datetime-local" class="form-control" id="eventStart" value="${start}" ${isReadonly ? 'readonly' : ''} required>
                </div>
                <div class="form-group">
                    <label for="eventEnd">End Date/Time</label>
                    <input type="datetime-local" class="form-control" id="eventEnd" value="${end}" ${isReadonly ? 'readonly' : ''} required>
                </div>
                <div class="form-group">
                    <label for="eventDescription">Description</label>
                    <textarea class="form-control" id="eventDescription" rows="3" ${isReadonly ? 'readonly' : ''}>${description}</textarea>
                </div>
                ${event ? `
                    <div class="form-group">
                        <label>Status</label>
                        <span class="badge badge-${Utils.getEventStatus(event.start, event.end)}">${Utils.getEventStatus(event.start, event.end)}</span>
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <span>${Utils.calculateDuration(event.start, event.end)}</span>
                    </div>
                ` : ''}
            </form>
        `;
    };
    
    const generateModalFooter = (mode) => {
        if (mode === 'view') {
            return `
                <button class="btn btn-primary" onclick="EventModalManager.switchToEditMode()">✏️ Edit Event</button>
                <button class="btn btn-danger" onclick="EventModalManager.deleteCurrentEvent()">🗑️ Delete Event</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Close</button>
            `;
        } else if (mode === 'edit') {
            return `
                <button class="btn btn-success" onclick="EventModalManager.saveEvent()">💾 Save Changes</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Cancel</button>
            `;
        } else {
            return `
                <button class="btn btn-success" onclick="EventModalManager.createEvent()">➕ Create Event</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Cancel</button>
            `;
        }
    };
    
    const switchToEditMode = () => {
        if (currentEventId) {
            openModal(currentEventId, 'edit');
        }
    };
    
    const saveEvent = async () => {
        try {
            const formData = getFormData();
            if (!formData) return;
            
            const eventData = {
                ...formData,
                id: currentEventId,
                action: 'update_event'
            };
            
            await APIClient.updateEvent(eventData);
            ModalManager.closeActive();
            DataTablesManager.refreshTable();
            UIManager.showSuccess('Event updated successfully');
            
        } catch (error) {
            console.error('Error saving event:', error);
            UIManager.showError('Failed to save event: ' + error.message);
        }
    };
    
    const createEvent = async () => {
        try {
            const formData = getFormData();
            if (!formData) return;
            
            const eventData = {
                ...formData,
                action: 'create_event'
            };
            
            await APIClient.createEvent(eventData);
            ModalManager.closeActive();
            DataTablesManager.refreshTable();
            UIManager.showSuccess('Event created successfully');
            
        } catch (error) {
            console.error('Error creating event:', error);
            UIManager.showError('Failed to create event: ' + error.message);
        }
    };
    
    const deleteCurrentEvent = async () => {
        if (!currentEventId) return;
        
        if (!confirm('Are you sure you want to delete this event?')) return;
        
        try {
            await APIClient.deleteEvent(currentEventId);
            ModalManager.closeActive();
            DataTablesManager.refreshTable();
            UIManager.showSuccess('Event deleted successfully');
            
        } catch (error) {
            console.error('Error deleting event:', error);
            UIManager.showError('Failed to delete event: ' + error.message);
        }
    };
    
    const getFormData = () => {
        const title = $('#eventTitle').val().trim();
        const start = $('#eventStart').val();
        const end = $('#eventEnd').val();
        const description = $('#eventDescription').val().trim();
        
        if (!title || !start || !end) {
            UIManager.showError('Please fill in all required fields');
            return null;
        }
        
        if (new Date(start) >= new Date(end)) {
            UIManager.showError('End time must be after start time');
            return null;
        }
        
        return {
            title,
            start: Utils.formatDateTimeForAPI(start),
            end: Utils.formatDateTimeForAPI(end),
            description
        };
    };
    
    // Make functions available globally for onclick handlers
    window.EventModalManager = {
        openModal,
        switchToEditMode,
        saveEvent,
        createEvent,
        deleteCurrentEvent
    };
    
    return {
        openModal,
        switchToEditMode,
        saveEvent,
        createEvent,
        deleteCurrentEvent
    };
})();

/**
 * Main events application controller
 */
const EventsApp = (() => {
    const setupEventListeners = () => {
        // Data refresh events
        EventBus.on('events:refresh', () => {
            console.log('Event: events:refresh triggered');
            DataTablesManager.refreshTable();
        });
        
        // UI events
        EventBus.on('events:loading', () => {
            UIManager.showLoadingOverlay();
        });
        
        EventBus.on('events:loaded', () => {
            UIManager.hideLoadingOverlay();
        });
        
        EventBus.on('events:error', ({ error }) => {
            UIManager.hideLoadingOverlay();
            UIManager.showError(error.message || 'Failed to load events data');
        });
        
        // Create event button
        $('#addEventBtn').on('click', () => {
            EventModalManager.openModal(null, 'create');
        });
        
        // Refresh events button
        $('#refreshEventsBtn').on('click', () => {
            DataTablesManager.refreshTable();
        });
    };
    
    const init = async () => {
        console.log('Initializing Events Management Page...');
        
        // Check authentication first using modular AuthGuard
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            return; // AuthGuard will handle redirect
        }
        
        // Initialize components
        DataTablesManager.initializeDataTable();
        FilterManager.initializeFilters();
        EventModalManager; // Initialize the modal manager
        setupEventListeners();
        
        // Load initial data
        await EventsDataManager.loadUsersData();
        
        // Start SSE connection using modular SSEManager
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
        console.log('Page loaded from cache, checking auth...');
        AuthGuard.checkAuthentication();
    }
});