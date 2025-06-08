// Modular Collaborative Calendar Application
// Location: frontend/js/script.js
// 
// This refactored version separates concerns into distinct, reusable components
// using functional programming patterns and an event-driven architecture.

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
    calendar: {
        defaultView: 'dayGridMonth',
        snapDuration: '00:05:00',
        timeInterval: 15 // minutes
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
    
    parseEventDateTime(dateTimeStr) {
        if (!dateTimeStr) return new Date();
        
        let date;
        if (dateTimeStr.includes('T')) {
            date = new Date(dateTimeStr);
        } else if (dateTimeStr.includes(' ')) {
            date = new Date(dateTimeStr.replace(' ', 'T'));
        } else {
            date = new Date(dateTimeStr + 'T09:00:00');
        }
        
        return isNaN(date.getTime()) ? new Date() : date;
    },
    
    generateEventId() {
        return Math.random().toString(36).substr(2, 9);
    }
};

// =============================================================================
// API CLIENT
// =============================================================================

/**
 * Centralized API communication
 */
const APIClient = (() => {
    const makeRequest = async (url, options = {}) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
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
        // User operations
        getUsers() {
            return makeRequest(`${Config.apiEndpoints.api}?action=users`);
        },
        
        createUser(userName) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create_user',
                    userName
                })
            });
        },
        
        // Event operations
        getEvents(userIds = []) {
            const userIdsParam = userIds.length ? `&user_ids=${userIds.join(',')}` : '';
            return makeRequest(`${Config.apiEndpoints.api}?action=events${userIdsParam}`);
        },
        
        createEvent(eventData) {
            return makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify(eventData)
            });
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
        
        // Test endpoint
        testConnection() {
            return makeRequest(`${Config.apiEndpoints.api}?action=test`);
        }
    };
})();

// =============================================================================
// USER MANAGER
// =============================================================================

/**
 * Manages user operations and selection
 */
const UserManager = (() => {
    let currentUser = null;
    let allUsers = [];
    let selectedUserIds = new Set();
    
    const validateUserName = (userName) => {
        return userName && userName.trim().length > 0;
    };
    
    const renderUserCheckboxes = () => {
        const container = document.getElementById('userCheckboxes');
        if (!container) return;
        
        container.innerHTML = '';
        
        allUsers.forEach(user => {
            const checkboxItem = document.createElement('div');
            checkboxItem.className = 'checkbox-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `user-${user.id}`;
            checkbox.value = user.id;
            checkbox.checked = selectedUserIds.has(user.id.toString());
            
            const colorDot = document.createElement('div');
            colorDot.className = 'user-color';
            colorDot.style.backgroundColor = user.color;
            
            const label = document.createElement('label');
            label.htmlFor = `user-${user.id}`;
            label.textContent = user.name;
            label.style.cursor = 'pointer';
            
            checkboxItem.appendChild(checkbox);
            checkboxItem.appendChild(colorDot);
            checkboxItem.appendChild(label);
            
            if (checkbox.checked) {
                checkboxItem.classList.add('checked');
            }
            
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    selectedUserIds.add(user.id.toString());
                    checkboxItem.classList.add('checked');
                } else {
                    selectedUserIds.delete(user.id.toString());
                    checkboxItem.classList.remove('checked');
                }
                
                EventBus.emit('users:selectionChanged', {
                    selectedUserIds: Array.from(selectedUserIds)
                });
            });
            
            container.appendChild(checkboxItem);
        });
    };
    
    const loadUsers = async () => {
        try {
            const users = await APIClient.getUsers();
            allUsers = users;
            renderUserCheckboxes();
            
            EventBus.emit('users:loaded', { users });
        } catch (error) {
            console.error('Error loading users:', error);
            EventBus.emit('users:error', { error });
        }
    };
    
    const setCurrentUser = async (userName) => {
        if (!validateUserName(userName)) {
            currentUser = null;
            EventBus.emit('user:cleared');
            return;
        }
        
        try {
            const userData = await APIClient.createUser(userName.trim());
            currentUser = userData;
            
            // Auto-select current user's calendar
            selectedUserIds.add(userData.id.toString());
            
            EventBus.emit('user:set', { user: userData });
            
            // Refresh users list and reload events
            await loadUsers();
            
        } catch (error) {
            console.error('Error setting current user:', error);
            EventBus.emit('user:error', { error });
        }
    };
    
    const getCurrentUser = () => currentUser;
    const getAllUsers = () => allUsers;
    const getSelectedUserIds = () => Array.from(selectedUserIds);
    
    const canUserEditEvent = (event) => {
        return currentUser && event.extendedProps.userName === currentUser.name;
    };
    
    // Event listeners
    EventBus.on('app:init', loadUsers);
    
    return {
        setCurrentUser,
        getCurrentUser,
        getAllUsers,
        getSelectedUserIds,
        canUserEditEvent,
        loadUsers
    };
})();

// =============================================================================
// DATE TIME MANAGER
// =============================================================================

/**
 * Manages datetime picker functionality
 */
const DateTimeManager = (() => {
    const initializeDateTimePickers = () => {
        const options = {
            format: 'Y-m-d H:i',
            formatTime: 'H:i',
            formatDate: 'Y-m-d',
            step: Config.calendar.timeInterval,
            minDate: false,
            maxDate: false,
            defaultTime: '09:00',
            timepicker: true,
            datepicker: true,
            weeks: false,
            theme: 'default',
            lang: 'en',
            yearStart: new Date().getFullYear() - 5,
            yearEnd: new Date().getFullYear() + 5,
            todayButton: true,
            closeOnDateSelect: false,
            closeOnTimeSelect: true,
            closeOnWithoutClick: true,
            timepickerScrollbar: true,
            onSelectDate: function(ct, $i) {
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000);
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', { minDate: ct });
                    }
                }
            },
            onSelectTime: function(ct, $i) {
                if ($i.attr('id') === 'eventStart') {
                    const endPicker = $('#eventEnd');
                    if (!endPicker.val()) {
                        const endTime = new Date(ct.getTime() + 60 * 60 * 1000);
                        endPicker.datetimepicker('setOptions', {
                            value: endTime,
                            minDate: ct
                        });
                    } else {
                        endPicker.datetimepicker('setOptions', { minDate: ct });
                    }
                }
            }
        };
        
        $('#eventStart, #eventEnd').datetimepicker(options);
        
        $('#eventStart').on('change', function() {
            const startDate = $(this).datetimepicker('getValue');
            if (startDate) {
                $('#eventEnd').datetimepicker('setOptions', { minDate: startDate });
                
                const endDate = $('#eventEnd').datetimepicker('getValue');
                if (!endDate || endDate <= startDate) {
                    const newEndDate = new Date(startDate.getTime() + 60 * 60 * 1000);
                    $('#eventEnd').datetimepicker('setOptions', { value: newEndDate });
                }
            }
        });
    };
    
    const setDateTimeValues = (startDate, endDate) => {
        if (startDate) {
            const start = Utils.parseEventDateTime(startDate);
            $('#eventStart').datetimepicker('setOptions', { value: start });
        }
        
        if (endDate) {
            const end = Utils.parseEventDateTime(endDate);
            $('#eventEnd').datetimepicker('setOptions', { value: end });
        }
    };
    
    const clearDateTimeValues = () => {
        $('#eventStart, #eventEnd').val('');
    };
    
    const getDateTimeValues = () => {
        return {
            start: $('#eventStart').val(),
            end: $('#eventEnd').val()
        };
    };
    
    const setDefaultDateTime = () => {
        const now = new Date();
        const roundedMinutes = Math.ceil(now.getMinutes() / Config.calendar.timeInterval) * Config.calendar.timeInterval;
        now.setMinutes(roundedMinutes, 0, 0);
        
        const endTime = new Date(now.getTime() + 60 * 60 * 1000);
        
        $('#eventStart').datetimepicker('setOptions', { value: now });
        $('#eventEnd').datetimepicker('setOptions', { value: endTime });
    };
    
    return {
        initializeDateTimePickers,
        setDateTimeValues,
        clearDateTimeValues,
        getDateTimeValues,
        setDefaultDateTime
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
        // Simple notification system - could be enhanced
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // You could implement a toast notification system here
        if (type === 'error') {
            alert(message);
        }
    };
    
    // Event listeners
    EventBus.on('connection:status', ({ status, message }) => {
        updateConnectionStatus(message, status);
    });
    
    EventBus.on('user:set', ({ user }) => {
        updateUserStatus(`Set as: ${user.name}`, 'user-set');
    });
    
    EventBus.on('user:cleared', () => {
        updateUserStatus('');
    });
    
    EventBus.on('user:error', ({ error }) => {
        updateUserStatus('Error setting user', 'disconnected');
        showNotification(`Error setting user: ${error.message}`, 'error');
    });
    
    EventBus.on('drag:start', ({ message }) => {
        showDragFeedback(message);
    });
    
    EventBus.on('drag:stop', () => {
        hideDragFeedback();
    });
    
    return {
        updateConnectionStatus,
        updateUserStatus,
        showDragFeedback,
        hideDragFeedback,
        showNotification
    };
})();

// =============================================================================
// MODAL MANAGER
// =============================================================================

/**
 * Manages modal dialogs for event creation/editing
 */
const ModalManager = (() => {
    let currentEvent = null;
    
    const openModal = (eventData = {}) => {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        if (!modal) return;
        
        // Set form values
        document.getElementById('eventTitle').value = eventData.title || '';
        
        // Set datetime values
        if (eventData.start || eventData.end) {
            DateTimeManager.setDateTimeValues(eventData.start, eventData.end);
        } else {
            DateTimeManager.setDefaultDateTime();
        }
        
        // Update modal state
        if (eventData.id) {
            modalTitle.textContent = 'Edit Event';
            deleteBtn.style.display = 'inline-block';
            currentEvent = eventData;
        } else {
            modalTitle.textContent = 'Add Event';
            deleteBtn.style.display = 'none';
            currentEvent = null;
        }
        
        modal.style.display = 'block';
        document.getElementById('eventTitle').focus();
        
        EventBus.emit('modal:opened', { event: eventData });
    };
    
    const closeModal = () => {
        const modal = document.getElementById('eventModal');
        if (modal) {
            modal.style.display = 'none';
        }
        
        currentEvent = null;
        DateTimeManager.clearDateTimeValues();
        
        EventBus.emit('modal:closed');
    };
    
    const getCurrentEvent = () => currentEvent;
    
    const validateForm = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start } = DateTimeManager.getDateTimeValues();
        
        if (!title) {
            UIManager.showNotification('Please enter an event title', 'error');
            return false;
        }
        
        if (!start) {
            UIManager.showNotification('Please select a start date and time', 'error');
            return false;
        }
        
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            UIManager.showNotification('Please enter your name first!', 'error');
            return false;
        }
        
        return true;
    };
    
    const getFormData = () => {
        const title = document.getElementById('eventTitle').value.trim();
        const { start, end } = DateTimeManager.getDateTimeValues();
        const currentUser = UserManager.getCurrentUser();
        
        return {
            title,
            start: Utils.formatDateTimeForAPI(start),
            end: Utils.formatDateTimeForAPI(end || start),
            userName: currentUser.name
        };
    };
    
    const setupEventListeners = () => {
        const modal = document.getElementById('eventModal');
        const closeBtn = modal?.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const eventForm = document.getElementById('eventForm');
        const deleteBtn = document.getElementById('deleteEventBtn');
        
        // Close modal events
        [closeBtn, cancelBtn].forEach(btn => {
            btn?.addEventListener('click', closeModal);
        });
        
        // Close on outside click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Form submission
        eventForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            if (validateForm()) {
                EventBus.emit('event:save', {
                    eventData: getFormData(),
                    isEdit: !!currentEvent,
                    eventId: currentEvent?.id
                });
            }
        });
        
        // Delete event
        deleteBtn?.addEventListener('click', () => {
            if (currentEvent && confirm('Are you sure you want to delete this event?')) {
                EventBus.emit('event:delete', { eventId: currentEvent.id });
            }
        });
    };
    
    // Event listeners
    EventBus.on('calendar:dateSelect', ({ start, end }) => {
        const currentUser = UserManager.getCurrentUser();
        if (!currentUser) {
            UIManager.showNotification('Please enter your name first!', 'error');
            return;
        }
        
        openModal({ start, end });
    });
    
    EventBus.on('calendar:eventClick', ({ event }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            UIManager.showNotification(
                `This event belongs to ${event.extendedProps.userName}. You can only edit your own events.`,
                'error'
            );
            return;
        }
        
        openModal({
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        });
    });
    
    EventBus.on('event:saved', closeModal);
    EventBus.on('event:deleted', closeModal);
    
    return {
        openModal,
        closeModal,
        getCurrentEvent,
        setupEventListeners
    };
})();

// =============================================================================
// EVENT MANAGER
// =============================================================================

/**
 * Manages calendar event CRUD operations
 */
const EventManager = (() => {
    const processedEventIds = new Set();
    
    const saveEvent = async (eventData, isEdit = false, eventId = null) => {
        try {
            let result;
            
            if (isEdit && eventId) {
                result = await APIClient.updateEvent({ ...eventData, id: eventId });
            } else {
                result = await APIClient.createEvent(eventData);
            }
            
            EventBus.emit('event:saved', { event: result, isEdit });
            return result;
            
        } catch (error) {
            console.error('Error saving event:', error);
            EventBus.emit('event:saveError', { error });
            throw error;
        }
    };
    
    const deleteEvent = async (eventId) => {
        try {
            await APIClient.deleteEvent(eventId);
            EventBus.emit('event:deleted', { eventId });
            
        } catch (error) {
            console.error('Error deleting event:', error);
            EventBus.emit('event:deleteError', { error });
            throw error;
        }
    };
    
    const loadEvents = async () => {
        const selectedUserIds = UserManager.getSelectedUserIds();
        
        if (selectedUserIds.length === 0) {
            EventBus.emit('events:loaded', { events: [] });
            return;
        }
        
        try {
            const events = await APIClient.getEvents(selectedUserIds);
            EventBus.emit('events:loaded', { events });
            
        } catch (error) {
            console.error('Error loading events:', error);
            EventBus.emit('events:loadError', { error });
        }
    };
    
    const handleEventMove = Utils.debounce(async (eventData) => {
        try {
            await APIClient.updateEvent(eventData);
            console.log('Event move saved successfully');
        } catch (error) {
            console.error('Error saving moved event:', error);
            EventBus.emit('event:moveError', { error });
        }
    }, 100);
    
    const handleEventResize = Utils.debounce(async (eventData) => {
        try {
            await APIClient.updateEvent(eventData);
            console.log('Event resize saved successfully');
        } catch (error) {
            console.error('Error saving resized event:', error);
            EventBus.emit('event:resizeError', { error });
        }
    }, 100);
    
    const preventDuplicateProcessing = (eventId, operation) => {
        const key = `${operation}-${eventId}`;
        
        if (processedEventIds.has(key)) {
            return true; // Is duplicate
        }
        
        processedEventIds.add(key);
        
        // Clean up after 5 minutes
        setTimeout(() => processedEventIds.delete(key), 300000);
        
        return false; // Not duplicate
    };
    
    // Event listeners
    EventBus.on('event:save', async ({ eventData, isEdit, eventId }) => {
        try {
            await saveEvent(eventData, isEdit, eventId);
        } catch (error) {
            UIManager.showNotification(`Error saving event: ${error.message}`, 'error');
        }
    });
    
    EventBus.on('event:delete', async ({ eventId }) => {
        try {
            await deleteEvent(eventId);
        } catch (error) {
            UIManager.showNotification(`Error deleting event: ${error.message}`, 'error');
        }
    });
    
    EventBus.on('users:selectionChanged', loadEvents);
    EventBus.on('user:set', loadEvents);
    
    EventBus.on('calendar:eventDrop', ({ event, revert }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            revert();
            UIManager.showNotification('You can only move your own events!', 'error');
            return;
        }
        
        const eventData = {
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        };
        
        handleEventMove(eventData);
    });
    
    EventBus.on('calendar:eventResize', ({ event, revert }) => {
        const canEdit = UserManager.canUserEditEvent(event);
        if (!canEdit) {
            revert();
            UIManager.showNotification('You can only resize your own events!', 'error');
            return;
        }
        
        const eventData = {
            id: event.id,
            title: event.title,
            start: event.startStr,
            end: event.endStr || event.startStr
        };
        
        handleEventResize(eventData);
    });
    
    return {
        loadEvents,
        preventDuplicateProcessing
    };
})();

// =============================================================================
// CALENDAR MANAGER
// =============================================================================

/**
 * Manages FullCalendar initialization and interactions
 */
const CalendarManager = (() => {
    let calendar = null;
    
    const initializeCalendar = () => {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: Config.calendar.defaultView,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            
            // Enable editing
            editable: true,
            selectable: true,
            selectMirror: true,
            snapDuration: Config.calendar.snapDuration,
            eventStartEditable: true,
            eventDurationEditable: true,
            eventResizableFromStart: true,
            
            // Visual settings
            dayMaxEvents: true,
            weekends: true,
            height: 'auto',
            
            events: [],
            
            // Event handlers
            select: (info) => {
                EventBus.emit('calendar:dateSelect', {
                    start: info.startStr,
                    end: info.endStr
                });
                calendar.unselect();
            },
            
            eventClick: (info) => {
                EventBus.emit('calendar:eventClick', { event: info.event });
            },
            
            eventDrop: (info) => {
                EventBus.emit('calendar:eventDrop', {
                    event: info.event,
                    revert: info.revert
                });
            },
            
            eventResize: (info) => {
                EventBus.emit('calendar:eventResize', {
                    event: info.event,
                    revert: info.revert
                });
            },
            
            eventAllow: (dropInfo, draggedEvent) => {
                return UserManager.canUserEditEvent(draggedEvent);
            },
            
            eventMouseEnter: (info) => {
                const canEdit = UserManager.canUserEditEvent(info.event);
                info.el.style.cursor = canEdit ? 'move' : 'not-allowed';
                if (canEdit) info.el.style.opacity = '0.8';
            },
            
            eventMouseLeave: (info) => {
                info.el.style.cursor = '';
                info.el.style.opacity = '';
            },
            
            eventDragStart: (info) => {
                info.el.style.opacity = '0.5';
                EventBus.emit('drag:start', { message: 'Moving event...' });
            },
            
            eventDragStop: (info) => {
                info.el.style.opacity = '';
                EventBus.emit('drag:stop');
            },
            
            eventResizeStart: (info) => {
                info.el.style.opacity = '0.5';
                EventBus.emit('drag:start', { message: 'Resizing event...' });
            },
            
            eventResizeStop: (info) => {
                info.el.style.opacity = '';
                EventBus.emit('drag:stop');
            }
        });
        
        calendar.render();
    };
    
    const addEvent = (eventData) => {
        const selectedUserIds = UserManager.getSelectedUserIds();
        if (selectedUserIds.includes(eventData.extendedProps.userId.toString())) {
            calendar?.addEvent(eventData);
        }
    };
    
    const updateEvent = (eventData) => {
        const event = calendar?.getEventById(eventData.id);
        if (event) {
            event.setProp('title', eventData.title);
            event.setStart(eventData.start);
            event.setEnd(eventData.end);
        }
    };
    
    const removeEvent = (eventId) => {
        const event = calendar?.getEventById(eventId);
        event?.remove();
    };
    
    const clearAllEvents = () => {
        calendar?.removeAllEvents();
    };
    
    const loadEvents = (events) => {
        clearAllEvents();
        events.forEach(event => calendar?.addEvent(event));
    };
    
    // Event listeners
    EventBus.on('events:loaded', ({ events }) => {
        loadEvents(events);
    });
    
    EventBus.on('sse:eventCreate', ({ eventData }) => {
        addEvent(eventData);
    });
    
    EventBus.on('sse:eventUpdate', ({ eventData }) => {
        updateEvent(eventData);
    });
    
    EventBus.on('sse:eventDelete', ({ eventId }) => {
        removeEvent(eventId);
    });
    
    return {
        initializeCalendar,
        addEvent,
        updateEvent,
        removeEvent,
        clearAllEvents,
        loadEvents
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
        const handleSSEEvent = (eventType, handler) => {
            eventSource.addEventListener(eventType, (e) => {
                try {
                    const eventData = JSON.parse(e.data);
                    const eventId = `${eventType}-${eventData.id || Date.now()}`;
                    
                    if (!EventManager.preventDuplicateProcessing(eventId, eventType)) {
                        handler(eventData);
                        lastEventId = parseInt(e.lastEventId) || lastEventId;
                    }
                } catch (error) {
                    console.error(`Error handling SSE ${eventType} event:`, error);
                }
            });
        };
        
        handleSSEEvent('create', (eventData) => {
            console.log('SSE: Creating event', eventData.id);
            EventBus.emit('sse:eventCreate', { eventData });
        });
        
        handleSSEEvent('update', (eventData) => {
            console.log('SSE: Updating event', eventData.id);
            EventBus.emit('sse:eventUpdate', { eventData });
        });
        
        handleSSEEvent('delete', (eventData) => {
            console.log('SSE: Deleting event', eventData.id);
            EventBus.emit('sse:eventDelete', { eventId: eventData.id });
        });
        
        eventSource.addEventListener('user_created', (e) => {
            console.log('SSE: User created, refreshing users list');
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
    
    // Event listeners
    EventBus.on('users:refresh', () => {
        UserManager.loadUsers();
    });
    
    return {
        connect,
        disconnect,
        getConnectionStatus
    };
})();

// =============================================================================
// APPLICATION CONTROLLER
// =============================================================================

/**
 * Main application controller that coordinates all components
 */
const CollaborativeCalendarApp = (() => {
    const setupUIEventListeners = () => {
        // User name input
        const userNameInput = document.getElementById('userName');
        if (userNameInput) {
            const handleUserNameChange = (e) => {
                UserManager.setCurrentUser(e.target.value.trim());
            };
            
            userNameInput.addEventListener('change', handleUserNameChange);
            userNameInput.addEventListener('blur', handleUserNameChange);
            userNameInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    handleUserNameChange(e);
                }
            });
        }
        
        // Add event button
        const addEventBtn = document.getElementById('addEventBtn');
        addEventBtn?.addEventListener('click', () => {
            EventBus.emit('calendar:dateSelect', {});
        });
        
        // Refresh users button
        const refreshUsersBtn = document.getElementById('refreshUsers');
        refreshUsersBtn?.addEventListener('click', () => {
            UserManager.loadUsers();
        });
    };
    
    const init = () => {
        console.log('Initializing Collaborative Calendar with modular architecture...');
        
        // Initialize components in order
        DateTimeManager.initializeDateTimePickers();
        CalendarManager.initializeCalendar();
        ModalManager.setupEventListeners();
        setupUIEventListeners();
        
        // Start SSE connection
        SSEManager.connect();
        
        // Emit app initialization event
        EventBus.emit('app:init');
        
        console.log('Collaborative Calendar initialized successfully');
    };
    
    const destroy = () => {
        SSEManager.disconnect();
        console.log('Collaborative Calendar destroyed');
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
    CollaborativeCalendarApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    CollaborativeCalendarApp.destroy();
});