<?php
/**
 * Calendar Component - Reusable FullCalendar wrapper
 * Location: frontend/components/calendar/Calendar.php
 * 
 * Configurable FullCalendar component that can be used across different pages
 */

class Calendar {
    private $config;
    private $events;
    private $users;
    private $elementId;
    private $modalId;
    private $currentUser;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'view' => 'dayGridMonth',
        'height' => 'auto',
        'editable' => true,
        'selectable' => true,
        'selectMirror' => true,
        'eventStartEditable' => true,
        'eventDurationEditable' => true,
        'eventResizableFromStart' => true,
        'dayMaxEvents' => true,
        'weekends' => true,
        'snapDuration' => '00:05:00',
        'timeInterval' => 15,
        'showUserFilters' => true,
        'showEventModal' => true,
        'apiEndpoints' => [
            'events' => null, // Will use ConfigService default
            'users' => null   // Will use ConfigService default
        ],
        'headerToolbar' => [
            'left' => 'prev,next today',
            'center' => 'title',
            'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        ],
        'permissions' => [
            'canCreate' => true,
            'canEdit' => true,
            'canDelete' => true,
            'editOwnOnly' => true
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->elementId = $config['elementId'] ?? 'calendar';
        $this->modalId = $config['modalId'] ?? 'eventModal';
        $this->events = [];
        $this->users = [];
        $this->currentUser = null;
    }
    
    /**
     * Set calendar data
     */
    public function setData($events = [], $users = [], $currentUser = null) {
        $this->events = $this->processEvents($events);
        $this->users = $this->processUsers($users);
        $this->currentUser = $currentUser;
        return $this;
    }
    
    /**
     * Process events data for FullCalendar
     */
    private function processEvents($events) {
        if (!is_array($events)) {
            return [];
        }
        
        return array_map(function($event) {
            return [
                'id' => $event['id'] ?? null,
                'title' => $event['title'] ?? 'Untitled Event',
                'start' => $event['start'] ?? null,
                'end' => $event['end'] ?? null,
                'backgroundColor' => $event['backgroundColor'] ?? $event['color'] ?? '#3498db',
                'borderColor' => $event['borderColor'] ?? $event['color'] ?? '#3498db',
                'textColor' => $event['textColor'] ?? '#ffffff',
                'extendedProps' => [
                    'userId' => $event['extendedProps']['userId'] ?? $event['user_id'] ?? null,
                    'userName' => $event['extendedProps']['userName'] ?? $event['user_name'] ?? 'Unknown',
                    'userColor' => $event['extendedProps']['userColor'] ?? $event['user_color'] ?? '#3498db',
                    'description' => $event['extendedProps']['description'] ?? $event['description'] ?? '',
                    'canEdit' => $this->canUserEditEvent($event)
                ]
            ];
        }, $events);
    }
    
    /**
     * Process users data
     */
    private function processUsers($users) {
        if (!is_array($users)) {
            return [];
        }
        
        return array_map(function($user) {
            return [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? 'Unknown User',
                'email' => $user['email'] ?? '',
                'color' => $user['color'] ?? '#3498db',
                'active' => $user['active'] ?? true,
                'selected' => $this->isUserSelected($user)
            ];
        }, $users);
    }
    
    /**
     * Check if user can edit event
     */
    private function canUserEditEvent($event) {
        if (!$this->config['permissions']['canEdit']) {
            return false;
        }
        
        if (!$this->config['permissions']['editOwnOnly']) {
            return true;
        }
        
        if (!$this->currentUser) {
            return false;
        }
        
        $eventUserId = $event['extendedProps']['userId'] ?? $event['user_id'] ?? null;
        return $eventUserId && $eventUserId == $this->currentUser['id'];
    }
    
    /**
     * Check if user should be selected by default
     */
    private function isUserSelected($user) {
        // Auto-select current user
        if ($this->currentUser && $user['id'] == $this->currentUser['id']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render the calendar HTML
     */
    public function render() {
        ?>
        <div class="calendar-component">
            <?php if ($this->config['showUserFilters']): ?>
                <?php $this->renderUserFilters(); ?>
            <?php endif; ?>
            
            <div class="calendar-wrapper">
                <div id="<?php echo htmlspecialchars($this->elementId); ?>"></div>
            </div>
            
            <?php if ($this->config['showEventModal']): ?>
                <?php $this->renderEventModal(); ?>
            <?php endif; ?>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateCalendarJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render user filters
     */
    private function renderUserFilters() {
        ?>
        <div class="calendar-controls">
            <div class="user-filters">
                <h3>Show Calendars</h3>
                <div id="<?php echo $this->elementId; ?>UserCheckboxes" class="checkbox-group">
                    <?php foreach ($this->users as $user): ?>
                        <?php 
                        $checked = $user['selected'] ? 'checked' : '';
                        $checkedClass = $user['selected'] ? ' checked' : '';
                        ?>
                        <div class="checkbox-item<?php echo $checkedClass; ?>">
                            <input 
                                type="checkbox" 
                                id="<?php echo $this->elementId; ?>-user-<?php echo $user['id']; ?>" 
                                value="<?php echo $user['id']; ?>"
                                class="calendar-user-checkbox"
                                <?php echo $checked; ?>
                            >
                            <div 
                                class="user-color" 
                                style="background-color: <?php echo htmlspecialchars($user['color']); ?>"
                            ></div>
                            <label 
                                for="<?php echo $this->elementId; ?>-user-<?php echo $user['id']; ?>" 
                                style="cursor: pointer;"
                            >
                                <?php echo htmlspecialchars($user['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button id="<?php echo $this->elementId; ?>RefreshUsers" class="btn btn-small btn-outline">
                    🔄 Refresh Users
                </button>
            </div>
            
            <div class="calendar-actions">
                <?php if ($this->config['permissions']['canCreate']): ?>
                    <button id="<?php echo $this->elementId; ?>AddEvent" class="btn btn-primary">
                        <span class="btn-icon">+</span>
                        Add Event
                    </button>
                <?php endif; ?>
                
                <div class="connection-status">
                    <span id="<?php echo $this->elementId; ?>Status" class="status">Ready</span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render event modal
     */
    private function renderEventModal() {
        ?>
        <div id="<?php echo htmlspecialchars($this->modalId); ?>" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="<?php echo $this->modalId; ?>Title">Event Details</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="<?php echo $this->modalId; ?>Form">
                        <div class="form-group">
                            <label for="<?php echo $this->modalId; ?>EventTitle">Event Title</label>
                            <input 
                                type="text" 
                                id="<?php echo $this->modalId; ?>EventTitle" 
                                placeholder="Enter event title..." 
                                required
                            >
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="<?php echo $this->modalId; ?>EventStart">Start Date & Time</label>
                                <input 
                                    type="text" 
                                    id="<?php echo $this->modalId; ?>EventStart" 
                                    placeholder="Select start date & time..." 
                                    required 
                                    readonly
                                >
                            </div>
                            <div class="form-group">
                                <label for="<?php echo $this->modalId; ?>EventEnd">End Date & Time</label>
                                <input 
                                    type="text" 
                                    id="<?php echo $this->modalId; ?>EventEnd" 
                                    placeholder="Select end date & time..." 
                                    required 
                                    readonly
                                >
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Event</button>
                            <?php if ($this->config['permissions']['canDelete']): ?>
                                <button 
                                    type="button" 
                                    id="<?php echo $this->modalId; ?>DeleteBtn" 
                                    class="btn btn-danger" 
                                    style="display: none;"
                                >
                                    Delete Event
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline modal-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Generate FullCalendar JavaScript
     */
    private function generateCalendarJs() {
        $calendarId = $this->elementId;
        $modalId = $this->modalId;
        $config = json_encode($this->config);
        $events = json_encode($this->events);
        $users = json_encode($this->users);
        $currentUser = json_encode($this->currentUser);
        
        return "
        // Initialize Calendar Component: {$calendarId}
        (function() {
            const calendarEl = document.getElementById('{$calendarId}');
            const modalEl = document.getElementById('{$modalId}');
            const config = {$config};
            const initialEvents = {$events};
            const users = {$users};
            const currentUser = {$currentUser};
            
            let calendar;
            let currentEvent = null;
            
            // Initialize FullCalendar
            if (calendarEl) {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: config.view,
                    height: config.height,
                    headerToolbar: config.headerToolbar,
                    
                    // Interaction settings
                    editable: config.editable,
                    selectable: config.selectable,
                    selectMirror: config.selectMirror,
                    eventStartEditable: config.eventStartEditable,
                    eventDurationEditable: config.eventDurationEditable,
                    eventResizableFromStart: config.eventResizableFromStart,
                    
                    // Display settings
                    dayMaxEvents: config.dayMaxEvents,
                    weekends: config.weekends,
                    snapDuration: config.snapDuration,
                    
                    // Events
                    events: initialEvents,
                    
                    // Event handlers
                    select: function(info) {
                        if (config.permissions.canCreate) {
                            openEventModal({
                                start: info.startStr,
                                end: info.endStr
                            });
                        }
                        calendar.unselect();
                    },
                    
                    eventClick: function(info) {
                        const event = info.event;
                        const canEdit = event.extendedProps.canEdit || !config.permissions.editOwnOnly;
                        
                        if (canEdit) {
                            openEventModal({
                                id: event.id,
                                title: event.title,
                                start: event.startStr,
                                end: event.endStr || event.startStr
                            });
                        } else {
                            showMessage('You can only edit your own events', 'warning');
                        }
                    },
                    
                    eventDrop: function(info) {
                        const event = info.event;
                        if (!event.extendedProps.canEdit && config.permissions.editOwnOnly) {
                            info.revert();
                            showMessage('You can only move your own events', 'error');
                            return;
                        }
                        
                        updateEventOnServer(event);
                    },
                    
                    eventResize: function(info) {
                        const event = info.event;
                        if (!event.extendedProps.canEdit && config.permissions.editOwnOnly) {
                            info.revert();
                            showMessage('You can only resize your own events', 'error');
                            return;
                        }
                        
                        updateEventOnServer(event);
                    },
                    
                    eventMouseEnter: function(info) {
                        const canEdit = info.event.extendedProps.canEdit || !config.permissions.editOwnOnly;
                        info.el.style.cursor = canEdit ? 'pointer' : 'not-allowed';
                    }
                });
                
                calendar.render();
            }
            
            // Modal functions
            function openEventModal(eventData = {}) {
                if (!modalEl) return;
                
                currentEvent = eventData;
                
                // Set form values
                document.getElementById('{$modalId}EventTitle').value = eventData.title || '';
                
                // Initialize datetime pickers if available
                if (typeof jQuery !== 'undefined' && jQuery.fn.datetimepicker) {
                    initializeDateTimePickers();
                }
                
                setDateTimeValues(eventData.start, eventData.end);
                
                // Update modal state
                const modalTitle = document.getElementById('{$modalId}Title');
                const deleteBtn = document.getElementById('{$modalId}DeleteBtn');
                
                if (eventData.id) {
                    modalTitle.textContent = 'Edit Event';
                    if (deleteBtn) deleteBtn.style.display = 'inline-block';
                } else {
                    modalTitle.textContent = 'Add Event';
                    if (deleteBtn) deleteBtn.style.display = 'none';
                }
                
                modalEl.style.display = 'block';
                document.getElementById('{$modalId}EventTitle').focus();
            }
            
            function closeEventModal() {
                if (modalEl) {
                    modalEl.style.display = 'none';
                }
                currentEvent = null;
            }
            
            function initializeDateTimePickers() {
                const options = {
                    format: 'Y-m-d H:i',
                    step: config.timeInterval || 15,
                    timepicker: true,
                    datepicker: true,
                    closeOnDateSelect: false,
                    closeOnTimeSelect: true
                };
                
                jQuery('#{$modalId}EventStart, #{$modalId}EventEnd').datetimepicker(options);
            }
            
            function setDateTimeValues(start, end) {
                const startInput = document.getElementById('{$modalId}EventStart');
                const endInput = document.getElementById('{$modalId}EventEnd');
                
                if (start && startInput) {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.datetimepicker) {
                        jQuery(startInput).datetimepicker('setOptions', { value: new Date(start) });
                    } else {
                        startInput.value = formatDateTimeLocal(start);
                    }
                }
                
                if (end && endInput) {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.datetimepicker) {
                        jQuery(endInput).datetimepicker('setOptions', { value: new Date(end) });
                    } else {
                        endInput.value = formatDateTimeLocal(end);
                    }
                }
            }
            
            function formatDateTimeLocal(dateTimeStr) {
                const date = new Date(dateTimeStr);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            }
            
            function updateEventOnServer(event) {
                const eventData = {
                    id: event.id,
                    title: event.title,
                    start: event.startStr,
                    end: event.endStr || event.startStr
                };
                
                // Use global API client if available
                if (window.CalendarAPI && window.CalendarAPI.updateEvent) {
                    window.CalendarAPI.updateEvent(eventData)
                        .then(() => showMessage('Event updated successfully', 'success'))
                        .catch(error => showMessage('Failed to update event: ' + error.message, 'error'));
                } else {
                    console.log('Event update:', eventData);
                }
            }
            
            function showMessage(message, type = 'info') {
                if (window.showNotification) {
                    window.showNotification(message, type);
                } else {
                    console.log(type.toUpperCase() + ': ' + message);
                }
            }
            
            // User filter handling
            const userCheckboxes = document.querySelectorAll('#{$calendarId}UserCheckboxes input[type=\"checkbox\"]');
            userCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const checkboxItem = this.closest('.checkbox-item');
                    
                    if (this.checked) {
                        checkboxItem.classList.add('checked');
                    } else {
                        checkboxItem.classList.remove('checked');
                    }
                    
                    // Filter events based on selected users
                    filterEventsByUsers();
                });
            });
            
            function filterEventsByUsers() {
                const selectedUserIds = Array.from(userCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => parseInt(cb.value));
                
                // Filter events and refresh calendar
                const filteredEvents = initialEvents.filter(event => 
                    selectedUserIds.length === 0 || 
                    selectedUserIds.includes(event.extendedProps.userId)
                );
                
                if (calendar) {
                    calendar.removeAllEvents();
                    calendar.addEventSource(filteredEvents);
                }
            }
            
            // Modal event listeners
            if (modalEl) {
                // Close modal
                modalEl.addEventListener('click', function(e) {
                    if (e.target === modalEl || e.target.classList.contains('close') || e.target.classList.contains('modal-cancel')) {
                        closeEventModal();
                    }
                });
                
                // Form submission
                const modalForm = document.getElementById('{$modalId}Form');
                if (modalForm) {
                    modalForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        saveEvent();
                    });
                }
                
                // Delete button
                const deleteBtn = document.getElementById('{$modalId}DeleteBtn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        if (currentEvent && currentEvent.id && confirm('Are you sure you want to delete this event?')) {
                            deleteEvent(currentEvent.id);
                        }
                    });
                }
            }
            
            // Add event button
            const addEventBtn = document.getElementById('{$calendarId}AddEvent');
            if (addEventBtn) {
                addEventBtn.addEventListener('click', function() {
                    openEventModal();
                });
            }
            
            // Refresh users button
            const refreshBtn = document.getElementById('{$calendarId}RefreshUsers');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    if (window.CalendarAPI && window.CalendarAPI.refreshUsers) {
                        window.CalendarAPI.refreshUsers();
                    } else {
                        window.location.reload();
                    }
                });
            }
            
            function saveEvent() {
                const title = document.getElementById('{$modalId}EventTitle').value.trim();
                const start = document.getElementById('{$modalId}EventStart').value;
                const end = document.getElementById('{$modalId}EventEnd').value;
                
                if (!title || !start) {
                    showMessage('Please fill in all required fields', 'error');
                    return;
                }
                
                const eventData = {
                    title: title,
                    start: start,
                    end: end || start
                };
                
                if (currentEvent && currentEvent.id) {
                    eventData.id = currentEvent.id;
                }
                
                // Use global API client if available
                if (window.CalendarAPI) {
                    const apiCall = currentEvent && currentEvent.id 
                        ? window.CalendarAPI.updateEvent(eventData)
                        : window.CalendarAPI.createEvent(eventData);
                    
                    apiCall
                        .then(() => {
                            showMessage('Event saved successfully', 'success');
                            closeEventModal();
                            // Refresh calendar
                            if (window.CalendarAPI.refreshEvents) {
                                window.CalendarAPI.refreshEvents();
                            }
                        })
                        .catch(error => showMessage('Failed to save event: ' + error.message, 'error'));
                } else {
                    console.log('Save event:', eventData);
                    closeEventModal();
                }
            }
            
            function deleteEvent(eventId) {
                if (window.CalendarAPI && window.CalendarAPI.deleteEvent) {
                    window.CalendarAPI.deleteEvent(eventId)
                        .then(() => {
                            showMessage('Event deleted successfully', 'success');
                            closeEventModal();
                            if (window.CalendarAPI.refreshEvents) {
                                window.CalendarAPI.refreshEvents();
                            }
                        })
                        .catch(error => showMessage('Failed to delete event: ' + error.message, 'error'));
                } else {
                    console.log('Delete event:', eventId);
                    closeEventModal();
                }
            }
            
            // Expose calendar instance for external access
            window['{$calendarId}Instance'] = {
                calendar: calendar,
                openModal: openEventModal,
                closeModal: closeEventModal,
                refresh: function() {
                    if (calendar) calendar.refetchEvents();
                },
                addEvent: function(event) {
                    if (calendar) calendar.addEvent(event);
                },
                removeEvent: function(eventId) {
                    const event = calendar.getEventById(eventId);
                    if (event) event.remove();
                },
                getSelectedUsers: function() {
                    return Array.from(userCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => parseInt(cb.value));
                }
            };
            
        })();
        ";
    }
    
    /**
     * Create a calendar with configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method for simple calendars
     */
    public static function render($config = [], $events = [], $users = [], $currentUser = null) {
        $calendar = new self($config);
        $calendar->setData($events, $users, $currentUser);
        $calendar->render();
    }
}