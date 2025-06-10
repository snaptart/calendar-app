<?php
/**
 * Calendar Component - REFACTORED (HTML Generation Only)
 * Location: frontend/components/calendar/Calendar.php
 * 
 * Generates semantic HTML with data attributes for JavaScript initialization
 * All behavior and FullCalendar logic handled by script.js
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
        'elementId' => 'calendar',
        'modalId' => 'eventModal',
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
        'realTime' => true,
        'sseEnabled' => true,
        'apiEndpoints' => [
            'events' => null,
            'users' => null,
            'sse' => null
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
        ],
        'classes' => [
            'wrapper' => 'calendar-component',
            'controls' => 'calendar-controls',
            'userFilters' => 'user-filters',
            'actions' => 'calendar-actions',
            'calendar' => 'calendar-wrapper'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->elementId = $this->config['elementId'];
        $this->modalId = $this->config['modalId'];
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
     * Render the calendar HTML with data attributes
     */
    public function render() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?>">
            <?php if ($this->config['showUserFilters']): ?>
                <?php $this->renderUserFilters(); ?>
            <?php endif; ?>
            
            <?php $this->renderCalendarContainer(); ?>
            
            <?php if ($this->config['showEventModal']): ?>
                <?php $this->renderEventModal(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render user filters section
     */
    private function renderUserFilters() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['controls']); ?>">
            <div class="<?php echo htmlspecialchars($this->config['classes']['userFilters']); ?>">
                <h3>Show Calendars</h3>
                <div id="<?php echo $this->elementId; ?>UserCheckboxes" 
                     class="checkbox-group"
                     data-component="user-filters"
                     data-component-id="<?php echo $this->elementId; ?>-user-filters"
                     data-target="#<?php echo $this->elementId; ?>"
                     data-auto-init="true">
                    
                    <?php foreach ($this->users as $user): ?>
                        <?php 
                        $checked = $user['selected'] ? 'checked' : '';
                        $checkedClass = $user['selected'] ? ' checked' : '';
                        ?>
                        <div class="checkbox-item<?php echo $checkedClass; ?>"
                             data-user-id="<?php echo $user['id']; ?>">
                            <input 
                                type="checkbox" 
                                id="<?php echo $this->elementId; ?>-user-<?php echo $user['id']; ?>" 
                                value="<?php echo $user['id']; ?>"
                                class="calendar-user-checkbox"
                                data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                data-user-color="<?php echo htmlspecialchars($user['color']); ?>"
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
                
                <button id="<?php echo $this->elementId; ?>RefreshUsers" 
                        class="btn btn-small btn-outline"
                        data-component="button"
                        data-action="refresh-users"
                        data-target="#<?php echo $this->elementId; ?>">
                    🔄 Refresh Users
                </button>
            </div>
            
            <div class="<?php echo htmlspecialchars($this->config['classes']['actions']); ?>">
                <?php if ($this->config['permissions']['canCreate']): ?>
                    <button id="<?php echo $this->elementId; ?>AddEvent" 
                            class="btn btn-primary"
                            data-component="button"
                            data-action="add-event"
                            data-target="#<?php echo $this->elementId; ?>">
                        <span class="btn-icon">+</span>
                        Add Event
                    </button>
                <?php endif; ?>
                
                <div class="connection-status">
                    <span id="<?php echo $this->elementId; ?>Status" 
                          class="status"
                          data-component="status"
                          data-component-id="<?php echo $this->elementId; ?>-status"
                          data-initial-state="ready"
                          data-auto-init="true">
                        Ready
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render main calendar container with data attributes
     */
    private function renderCalendarContainer() {
        $calendarConfig = [
            'initialView' => $this->config['view'],
            'height' => $this->config['height'],
            'headerToolbar' => $this->config['headerToolbar'],
            'editable' => $this->config['editable'],
            'selectable' => $this->config['selectable'],
            'selectMirror' => $this->config['selectMirror'],
            'eventStartEditable' => $this->config['eventStartEditable'],
            'eventDurationEditable' => $this->config['eventDurationEditable'],
            'eventResizableFromStart' => $this->config['eventResizableFromStart'],
            'dayMaxEvents' => $this->config['dayMaxEvents'],
            'weekends' => $this->config['weekends'],
            'snapDuration' => $this->config['snapDuration'],
            'timeInterval' => $this->config['timeInterval']
        ];
        
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['calendar']); ?>">
            <div id="<?php echo htmlspecialchars($this->elementId); ?>"
                 data-component="calendar"
                 data-component-id="<?php echo htmlspecialchars($this->elementId); ?>"
                 data-config='<?php echo json_encode($calendarConfig, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-events='<?php echo json_encode($this->events, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-users='<?php echo json_encode($this->users, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-current-user='<?php echo json_encode($this->currentUser, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-permissions='<?php echo json_encode($this->config['permissions'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-real-time="<?php echo $this->config['realTime'] ? 'true' : 'false'; ?>"
                 data-sse-enabled="<?php echo $this->config['sseEnabled'] ? 'true' : 'false'; ?>"
                 <?php if ($this->config['apiEndpoints']['events']): ?>
                     data-api-url="<?php echo htmlspecialchars($this->config['apiEndpoints']['events']); ?>"
                 <?php endif; ?>
                 <?php if ($this->config['apiEndpoints']['sse']): ?>
                     data-sse-url="<?php echo htmlspecialchars($this->config['apiEndpoints']['sse']); ?>"
                 <?php endif; ?>
                 data-modal-target="#<?php echo htmlspecialchars($this->modalId); ?>"
                 data-auto-init="true">
            </div>
        </div>
        <?php
    }
    
    /**
     * Render event modal with data attributes
     */
    private function renderEventModal() {
        $modalConfig = [
            'size' => 'medium',
            'backdrop' => 'static',
            'keyboard' => true,
            'closeOnEscape' => true
        ];
        
        ?>
        <div id="<?php echo htmlspecialchars($this->modalId); ?>" 
             class="modal"
             data-component="modal"
             data-component-id="<?php echo htmlspecialchars($this->modalId); ?>"
             data-modal-type="event"
             data-config='<?php echo json_encode($modalConfig, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-target="#<?php echo htmlspecialchars($this->elementId); ?>"
             data-auto-init="true">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="<?php echo $this->modalId; ?>Title" 
                        data-component="modal-title">
                        Event Details
                    </h2>
                    <span class="close" 
                          data-action="close-modal"
                          data-target="#<?php echo htmlspecialchars($this->modalId); ?>">&times;</span>
                </div>
                
                <div class="modal-body">
                    <form id="<?php echo $this->modalId; ?>Form"
                          data-component="form"
                          data-component-id="<?php echo $this->modalId; ?>-form"
                          data-validation="client"
                          data-submit-method="POST"
                          data-permissions='<?php echo json_encode($this->config['permissions'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                          data-auto-init="true">
                        
                        <div class="form-group">
                            <label for="<?php echo $this->modalId; ?>EventTitle">
                                Event Title *
                            </label>
                            <input 
                                type="text" 
                                id="<?php echo $this->modalId; ?>EventTitle" 
                                name="title"
                                placeholder="Enter event title..." 
                                class="form-control"
                                data-validate="required|minlength:3"
                                data-validate-message="Event title is required (minimum 3 characters)"
                                required
                            >
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="<?php echo $this->modalId; ?>EventStart">
                                    Start Date & Time *
                                </label>
                                <input 
                                    type="text" 
                                    id="<?php echo $this->modalId; ?>EventStart" 
                                    name="start"
                                    placeholder="Select start date & time..." 
                                    class="form-control datetime-picker"
                                    data-validate="required"
                                    data-validate-message="Start date and time is required"
                                    readonly
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="<?php echo $this->modalId; ?>EventEnd">
                                    End Date & Time *
                                </label>
                                <input 
                                    type="text" 
                                    id="<?php echo $this->modalId; ?>EventEnd" 
                                    name="end"
                                    placeholder="Select end date & time..." 
                                    class="form-control datetime-picker"
                                    data-validate="required"
                                    data-validate-message="End date and time is required"
                                    readonly
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" 
                                    class="btn btn-primary"
                                    data-component="button"
                                    data-action="save-event">
                                Save Event
                            </button>
                            
                            <?php if ($this->config['permissions']['canDelete']): ?>
                                <button 
                                    type="button" 
                                    id="<?php echo $this->modalId; ?>DeleteBtn" 
                                    class="btn btn-danger" 
                                    style="display: none;"
                                    data-component="button"
                                    data-action="delete-event"
                                    data-confirm="Are you sure you want to delete this event?"
                                >
                                    Delete Event
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" 
                                    class="btn btn-outline modal-cancel"
                                    data-action="close-modal"
                                    data-target="#<?php echo htmlspecialchars($this->modalId); ?>">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Set API endpoints
     */
    public function setApiEndpoints($endpoints) {
        $this->config['apiEndpoints'] = array_merge($this->config['apiEndpoints'], $endpoints);
        return $this;
    }
    
    /**
     * Set permissions
     */
    public function setPermissions($permissions) {
        $this->config['permissions'] = array_merge($this->config['permissions'], $permissions);
        return $this;
    }
    
    /**
     * Enable/disable real-time features
     */
    public function setRealTime($enabled, $sseUrl = null) {
        $this->config['realTime'] = $enabled;
        $this->config['sseEnabled'] = $enabled;
        
        if ($sseUrl) {
            $this->config['apiEndpoints']['sse'] = $sseUrl;
        }
        
        return $this;
    }
    
    /**
     * Set CSS classes
     */
    public function setClasses($classes) {
        $this->config['classes'] = array_merge($this->config['classes'], $classes);
        return $this;
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
    
    /**
     * Render minimal calendar (just the calendar, no controls)
     */
    public static function renderMinimal($elementId, $events = [], $config = []) {
        $defaultConfig = [
            'elementId' => $elementId,
            'showUserFilters' => false,
            'showEventModal' => false
        ];
        
        $calendar = new self(array_merge($defaultConfig, $config));
        $calendar->setData($events, [], null);
        $calendar->renderCalendarContainer();
    }
}