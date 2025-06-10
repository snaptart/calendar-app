// Calendar Page JavaScript - Ice Time Management
// Location: frontend/assets/js/calendar.js
// 
// Page-specific calendar functionality (requires core.js to be loaded first)

(function() {
    'use strict';
    
    // Check if core utilities are available
    if (!window.IceTimeApp) {
        console.error('Core utilities not loaded. Please ensure core.js is loaded first.');
        return;
    }
    
    const { EventBus, Config, Utils, APIClient, AuthGuard, UIManager, SSEManager } = window.IceTimeApp;
    
    // =============================================================================
    // USER MANAGER - Calendar Specific
    // =============================================================================
    
    const UserManager = (() => {
        let allUsers = [];
        let selectedUserIds = new Set();
        
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
                
                // Auto-select current user's calendar
                const currentUser = AuthGuard.getCurrentUser();
                if (currentUser) {
                    selectedUserIds.add(currentUser.id.toString());
                }
                
                renderUserCheckboxes();
                EventBus.emit('users:loaded', { users });
            } catch (error) {
                console.error('Error loading users:', error);
                EventBus.emit('users:error', { error });
            }
        };
        
        const getCurrentUser = () => AuthGuard.getCurrentUser();
        const getAllUsers = () => allUsers;
        const getSelectedUserIds = () => Array.from(selectedUserIds);
        
        const canUserEditEvent = (event) => {
            const currentUser = getCurrentUser();
            return currentUser && event.extendedProps.userId === currentUser.id;
        };
        
        return {
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
                const start = Utils.parseEventDateTime ? Utils.parseEventDateTime(startDate) : new Date(startDate);
                $('#eventStart').datetimepicker('setOptions', { value: start });
            }
            
            if (endDate) {
                const end = Utils.parseEventDateTime ? Utils.parseEventDateTime(endDate) : new Date(endDate);
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
    // MODAL MANAGER
    // =============================================================================
    
    const ModalManager = (() => {
        let currentEvent = null;
        
        const openModal = (eventData = {}) => {
            const modal = document.getElementById('eventModal');
            const modalTitle = document.getElementById('modalTitle');
            const deleteBtn = document.getElementById('deleteEventBtn');
            
            if (!modal) return;
            
            const currentUser = UserManager.getCurrentUser();
            if (!currentUser) {
                UIManager.showError('Please login to create events');
                return;
            }
            
            document.getElementById('eventTitle').value = eventData.title || '';
            
            if (eventData.start || eventData.end) {
                DateTimeManager.setDateTimeValues(eventData.start, eventData.end);
            } else {
                DateTimeManager.setDefaultDateTime();
            }
            
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
                UIManager.showError('Please enter an event title');
                return false;
            }
            
            if (!start) {
                UIManager.showError('Please select a start date and time');
                return false;
            }
            
            const currentUser = UserManager.getCurrentUser();
            if (!currentUser) {
                UIManager.showError('Please login to create events');
                return false;
            }
            
            return true;
        };
        
        const getFormData = () => {
            const title = document.getElementById('eventTitle').value.trim();
            const { start, end } = DateTimeManager.getDateTimeValues();
            
            return {
                title,
                start: Utils.formatDateTimeForAPI(start),
                end: Utils.formatDateTimeForAPI(end || start)
            };
        };
        
        const setupEventListeners = () => {
            const modal = document.getElementById('eventModal');
            const closeBtn = modal?.querySelector('.close');
            const cancelBtn = document.getElementById('cancelBtn');
            const eventForm = document.getElementById('eventForm');
            const deleteBtn = document.getElementById('deleteEventBtn');
            
            [closeBtn, cancelBtn].forEach(btn => {
                btn?.addEventListener('click', closeModal);
            });
            
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            
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
            
            deleteBtn?.addEventListener('click', () => {
                if (currentEvent && confirm('Are you sure you want to delete this event?')) {
                    EventBus.emit('event:delete', { eventId: currentEvent.id });
                }
            });
        };
        
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
    
    const EventManager = (() => {
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
        
        return {
            saveEvent,
            deleteEvent,
            loadEvents,
            handleEventMove,
            handleEventResize
        };
    })();
    
    // =============================================================================
    // CALENDAR MANAGER
    // =============================================================================
    
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
                
                editable: true,
                selectable: true,
                selectMirror: true,
                snapDuration: Config.calendar.snapDuration,
                eventStartEditable: true,
                eventDurationEditable: true,
                eventResizableFromStart: true,
                
                dayMaxEvents: true,
                weekends: true,
                height: 'auto',
                
                events: [],
                
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
    // APPLICATION CONTROLLER
    // =============================================================================
    
    const CalendarApp = (() => {
        const setupUIEventListeners = () => {
            const addEventBtn = document.getElementById('addEventBtn');
            addEventBtn?.addEventListener('click', () => {
                const currentUser = UserManager.getCurrentUser();
                if (!currentUser) {
                    UIManager.showError('Please login to create events');
                    return;
                }
                EventBus.emit('calendar:dateSelect', {});
            });
            
            const refreshUsersBtn = document.getElementById('refreshUsers');
            refreshUsersBtn?.addEventListener('click', () => {
                UserManager.loadUsers();
            });
        };
        
        const setupEventListeners = () => {
            // Modal events
            EventBus.on('calendar:dateSelect', ({ start, end }) => {
                const currentUser = UserManager.getCurrentUser();
                if (!currentUser) {
                    UIManager.showError('Please login to create events');
                    return;
                }
                ModalManager.openModal({ start, end });
            });
            
            EventBus.on('calendar:eventClick', ({ event }) => {
                const canEdit = UserManager.canUserEditEvent(event);
                if (!canEdit) {
                    UIManager.showError(
                        `This event belongs to ${event.extendedProps.userName}. You can only edit your own events.`
                    );
                    return;
                }
                
                ModalManager.openModal({
                    id: event.id,
                    title: event.title,
                    start: event.startStr,
                    end: event.endStr || event.startStr
                });
            });
            
            // Event CRUD operations
            EventBus.on('event:save', async ({ eventData, isEdit, eventId }) => {
                try {
                    await EventManager.saveEvent(eventData, isEdit, eventId);
                } catch (error) {
                    UIManager.showError(`Error saving event: ${error.message}`);
                }
            });
            
            EventBus.on('event:delete', async ({ eventId }) => {
                try {
                    await EventManager.deleteEvent(eventId);
                } catch (error) {
                    UIManager.showError(`Error deleting event: ${error.message}`);
                }
            });
            
            EventBus.on('event:saved', () => {
                ModalManager.closeModal();
                EventManager.loadEvents();
                UIManager.showSuccess('Event saved successfully');
            });
            
            EventBus.on('event:deleted', () => {
                ModalManager.closeModal();
                EventManager.loadEvents();
                UIManager.showSuccess('Event deleted successfully');
            });
            
            // User selection changes
            EventBus.on('users:selectionChanged', () => {
                EventManager.loadEvents();
            });
            
            EventBus.on('users:loaded', () => {
                EventManager.loadEvents();
            });
            
            // Calendar interactions
            EventBus.on('calendar:eventDrop', ({ event, revert }) => {
                const canEdit = UserManager.canUserEditEvent(event);
                if (!canEdit) {
                    revert();
                    UIManager.showError('You can only move your own events!');
                    return;
                }
                
                const eventData = {
                    id: event.id,
                    title: event.title,
                    start: event.startStr,
                    end: event.endStr || event.startStr
                };
                
                EventManager.handleEventMove(eventData);
            });
            
            EventBus.on('calendar:eventResize', ({ event, revert }) => {
                const canEdit = UserManager.canUserEditEvent(event);
                if (!canEdit) {
                    revert();
                    UIManager.showError('You can only resize your own events!');
                    return;
                }
                
                const eventData = {
                    id: event.id,
                    title: event.title,
                    start: event.startStr,
                    end: event.endStr || event.startStr
                };
                
                EventManager.handleEventResize(eventData);
            });
            
            // Calendar data events
            EventBus.on('events:loaded', ({ events }) => {
                CalendarManager.loadEvents(events);
            });
            
            // SSE events
            EventBus.on('sse:eventCreate', ({ eventData }) => {
                CalendarManager.addEvent(eventData);
            });
            
            EventBus.on('sse:eventUpdate', ({ eventData }) => {
                CalendarManager.updateEvent(eventData);
            });
            
            EventBus.on('sse:eventDelete', ({ eventId }) => {
                CalendarManager.removeEvent(eventId);
            });
            
            EventBus.on('users:refresh', () => {
                UserManager.loadUsers();
            });
            
            // Drag feedback
            EventBus.on('drag:start', ({ message }) => {
                UIManager.updateConnectionStatus(message, 'drag-feedback');
            });
            
            EventBus.on('drag:stop', () => {
                setTimeout(() => {
                    UIManager.updateConnectionStatus('Connected', 'connected');
                }, 1000);
            });
        };
        
        const init = async () => {
            console.log('Initializing Calendar Page...');
            
            const isAuthenticated = await AuthGuard.checkAuthentication();
            
            if (!isAuthenticated) {
                return;
            }
            
            DateTimeManager.initializeDateTimePickers();
            CalendarManager.initializeCalendar();
            ModalManager.setupEventListeners();
            setupUIEventListeners();
            setupEventListeners();
            
            SSEManager.connect();
            
            await UserManager.loadUsers();
            
            console.log('Calendar Page initialized successfully');
        };
        
        const destroy = () => {
            SSEManager.disconnect();
            console.log('Calendar Page destroyed');
        };
        
        return {
            init,
            destroy
        };
    })();
    
    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    
    // Export to global scope for access from other scripts
    window.IceTimeApp.CalendarApp = CalendarApp;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            CalendarApp.init();
        });
    } else {
        CalendarApp.init();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        CalendarApp.destroy();
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            console.log('Page loaded from cache, checking auth...');
            AuthGuard.checkAuthentication();
        }
    });
    
})();