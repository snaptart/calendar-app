<?php
/**
 * Calendar Page - Updated with Component Architecture
 * Location: frontend/pages/calendar.php
 * 
 * Main calendar view using the new component-based architecture
 */

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ApiClient.php';
require_once __DIR__ . '/../layouts/AppLayout.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = AuthService::guard(true); // Require authentication
$api = ApiClient::createAuthenticated();

// Load data for the page
try {
    $users = $api->getUsers();
    $currentUser = $auth->getCurrentUser();
    
    // Optional: Get initial events for the current user
    $initialEvents = [];
    if ($currentUser) {
        try {
            $initialEvents = $api->getEvents([$currentUser['id']]);
        } catch (Exception $e) {
            error_log("Error loading initial events: " . $e->getMessage());
            // Continue without initial events
        }
    }
    
} catch (UnauthorizedException $e) {
    // Should not happen due to AuthService::guard, but handle just in case
    header('Location: ./login.php');
    exit;
} catch (Exception $e) {
    error_log("Calendar page error: " . $e->getMessage());
    $users = [];
    $initialEvents = [];
}

// Get page configuration
$pageConfig = $config->forLayout('calendar', [
    'pageTitle' => '📅 Collaborative Calendar',
    'activeNavItem' => 'calendar',
    'showUserSection' => true,
    'showNavigation' => true
]);

// Render the calendar page using component architecture
AppLayout::createCalendarPage(
    $pageConfig,
    
    // Main calendar content
    function() use ($auth, $initialEvents, $users, $currentUser) {
        ?>
        <div class="calendar-wrapper">
            <div id="calendar"
                 data-component="calendar"
                 data-component-id="main-calendar"
                 data-config='<?php echo json_encode([
                     'initialView' => 'dayGridMonth',
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
                     'timeInterval' => 15
                 ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-events='<?php echo json_encode($initialEvents, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-users='<?php echo json_encode($users, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-current-user='<?php echo json_encode($currentUser, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-permissions='<?php echo json_encode([
                     'canCreate' => true,
                     'canEdit' => true,
                     'canDelete' => true,
                     'editOwnOnly' => true
                 ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                 data-real-time="true"
                 data-sse-enabled="true"
                 data-api-url="../../backend/api.php?action=events"
                 data-sse-url="../../backend/workers/sse.php"
                 data-modal-target="#eventModal"
                 data-auto-init="true">
            </div>
        </div>

        <!-- Event Modal -->
        <div id="eventModal" 
             class="modal event-modal"
             data-component="modal"
             data-component-id="event-modal"
             data-modal-type="event"
             data-config='<?php echo json_encode([
                 'size' => 'medium',
                 'backdrop' => 'static',
                 'keyboard' => true,
                 'closeOnEscape' => true
             ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-permissions='<?php echo json_encode([
                 'canCreate' => true,
                 'canEdit' => true,
                 'canDelete' => true
             ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-target="#calendar"
             data-auto-init="true">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="eventModalTitle" data-component="modal-title">Add Event</h2>
                    <span class="close" data-action="close-modal">&times;</span>
                </div>
                
                <div class="modal-body">
                    <form id="eventForm"
                          data-component="form"
                          data-component-id="event-form"
                          data-validation="true"
                          data-submit-method="POST"
                          data-auto-init="true">
                        
                        <div class="form-group">
                            <label for="eventTitle">Event Title *</label>
                            <input type="text" 
                                   id="eventTitle" 
                                   name="title"
                                   placeholder="Enter event title..." 
                                   class="form-control"
                                   data-validate="required|minlength:3"
                                   required>
                            <div class="form-error" id="eventTitleError"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="eventStart">Start Date & Time *</label>
                                <input type="text" 
                                       id="eventStart" 
                                       name="start"
                                       placeholder="Select start date & time..." 
                                       class="form-control datetime-picker"
                                       data-validate="required"
                                       readonly
                                       required>
                                <div class="form-error" id="eventStartError"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="eventEnd">End Date & Time *</label>
                                <input type="text" 
                                       id="eventEnd" 
                                       name="end"
                                       placeholder="Select end date & time..." 
                                       class="form-control datetime-picker"
                                       data-validate="required"
                                       readonly
                                       required>
                                <div class="form-error" id="eventEndError"></div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" 
                                    class="btn btn-primary"
                                    data-component="button"
                                    data-action="save-event">
                                Save Event
                            </button>
                            
                            <button type="button" 
                                    id="deleteEventBtn" 
                                    class="btn btn-danger" 
                                    style="display: none;"
                                    data-component="button"
                                    data-action="delete-event"
                                    data-confirm="Are you sure you want to delete this event?">
                                Delete Event
                            </button>
                            
                            <button type="button" 
                                    class="btn btn-outline"
                                    data-action="close-modal"
                                    data-target="#eventModal">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    },
    
    // Calendar controls content
    function() use ($users, $currentUser) {
        ?>
        <div class="user-filters">
            <h3>Show Calendars</h3>
            <div id="userCheckboxes" 
                 class="checkbox-group"
                 data-component="user-filters"
                 data-component-id="calendar-user-filters"
                 data-target="#calendar"
                 data-auto-init="true">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        // Auto-select current user's calendar
                        $isCurrentUser = $currentUser && $user['id'] == $currentUser['id'];
                        $checked = $isCurrentUser ? 'checked' : '';
                        $checkedClass = $isCurrentUser ? ' checked' : '';
                        ?>
                        <div class="checkbox-item<?php echo $checkedClass; ?>" data-user-id="<?php echo $user['id']; ?>">
                            <input type="checkbox" 
                                   id="user-<?php echo $user['id']; ?>" 
                                   value="<?php echo $user['id']; ?>"
                                   class="calendar-user-checkbox"
                                   data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                   data-user-color="<?php echo htmlspecialchars($user['color'] ?? '#3498db'); ?>"
                                   <?php echo $checked; ?>>
                            <div class="user-color" 
                                 style="background-color: <?php echo htmlspecialchars($user['color'] ?? '#3498db'); ?>"></div>
                            <label for="user-<?php echo $user['id']; ?>" style="cursor: pointer;">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No users found. <button id="refreshUsers" class="btn btn-small btn-outline">🔄 Refresh</button></p>
                <?php endif; ?>
            </div>
            <button id="refreshUsers" 
                    class="btn btn-small btn-outline"
                    data-component="button"
                    data-action="refresh-users"
                    data-target="#calendar">
                🔄 Refresh Users
            </button>
        </div>
        
        <div class="calendar-actions">
            <button id="addEventBtn" 
                    class="btn btn-primary"
                    data-component="button"
                    data-action="add-event"
                    data-target="#calendar">
                <span class="btn-icon">+</span>
                Add Event
            </button>
            <button id="viewEventsBtn" 
                    class="btn btn-outline"
                    data-component="button"
                    data-action="navigate"
                    data-url="./events.php">
                <span class="btn-icon">📋</span>
                View All Events
            </button>
            <button id="importEventsBtn" 
                    class="btn btn-outline"
                    data-component="button"
                    data-action="navigate"
                    data-url="./import.php">
                <span class="btn-icon">📥</span>
                Import Events
            </button>
            <div class="connection-status">
                <span id="connectionStatus" 
                      class="status"
                      data-component="status"
                      data-component-id="calendar-status"
                      data-initial-state="ready"
                      data-auto-init="true">
                    Ready
                </span>
            </div>
        </div>
        <?php
    }
);

// Add authentication status and configuration to JavaScript
?>
<script>
<?php echo $auth->generateAuthJs(); ?>

// Add calendar-specific configuration
window.CalendarPage = {
    initialUsers: <?php echo json_encode($users); ?>,
    currentUser: <?php echo json_encode($currentUser); ?>,
    initialEvents: <?php echo json_encode($initialEvents); ?>
};

// Configuration for the component system
<?php echo $config->generateConfigJs(); ?>

console.log('Calendar page loaded with component architecture');
</script>