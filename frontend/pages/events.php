<?php
/**
 * Events Page - Updated with Component Architecture
 * Location: frontend/pages/events.php
 * 
 * Events management page using the new component-based architecture
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
    
    // Get event statistics
    $totalEvents = 0;
    $upcomingEvents = 0;
    $ongoingEvents = 0;
    $myEvents = 0;
    
    try {
        // Try to get basic event count
        $allEvents = $api->getEvents();
        $totalEvents = count($allEvents);
        
        // Calculate statistics
        $now = new DateTime();
        foreach ($allEvents as $event) {
            $start = new DateTime($event['start']);
            $end = new DateTime($event['end']);
            
            if ($now < $start) {
                $upcomingEvents++;
            } elseif ($now >= $start && $now <= $end) {
                $ongoingEvents++;
            }
            
            if ($currentUser && $event['extendedProps']['userId'] == $currentUser['id']) {
                $myEvents++;
            }
        }
    } catch (Exception $e) {
        error_log("Error loading event statistics: " . $e->getMessage());
    }
    
} catch (UnauthorizedException $e) {
    header('Location: ./login.php');
    exit;
} catch (Exception $e) {
    error_log("Events page error: " . $e->getMessage());
    $users = [];
}

// Get page configuration
$pageConfig = $config->forLayout('events', [
    'pageTitle' => '📋 Events Management',
    'activeNavItem' => 'events',
    'showUserSection' => true,
    'showNavigation' => true,
    'breadcrumbs' => [
        ['title' => 'Calendar', 'url' => './calendar.php'],
        ['title' => 'Events']
    ],
    'tableTitle' => 'Calendar Events',
    'tableDescription' => 'View and manage calendar events with advanced filtering and search',
    'tableActions' => function() use ($users) {
        ?>
        <div class="filter-controls">
            <div class="filter-group">
                <label for="eventViewFilter">View:</label>
                <select id="eventViewFilter" 
                        class="form-control form-control-sm"
                        data-component="filter"
                        data-target="#eventsTable"
                        data-filter-type="view">
                    <option value="all">All Events</option>
                    <option value="my">My Events Only</option>
                    <option value="others">Others' Events</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="eventStatusFilter">Status:</label>
                <select id="eventStatusFilter" 
                        class="form-control form-control-sm"
                        data-component="filter"
                        data-target="#eventsTable"
                        data-filter-type="status">
                    <option value="">All Status</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="past">Past</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="eventUserFilter">User:</label>
                <select id="eventUserFilter" 
                        class="form-control form-control-sm"
                        data-component="filter"
                        data-target="#eventsTable"
                        data-filter-type="user">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="action-buttons">
            <button id="refreshEventsBtn" 
                    class="btn btn-outline btn-small"
                    data-component="button"
                    data-action="refresh"
                    data-target="#eventsTable">
                <span class="btn-icon">🔄</span>
                Refresh Data
            </button>
            <button id="addEventBtn" 
                    class="btn btn-primary btn-small"
                    data-component="button"
                    data-action="navigate"
                    data-url="./calendar.php">
                <span class="btn-icon">+</span>
                Add Event
            </button>
        </div>
        <?php
    }
]);

// Render the events page using component architecture
AppLayout::createTablePage(
    $pageConfig,
    
    // Table content
    function() use ($totalEvents, $upcomingEvents, $ongoingEvents, $myEvents, $users, $currentUser) {
        ?>
        <div class="table-wrapper">
            <div class="table-container">
                <table id="eventsTable" 
                       class="table table-striped table-hover" 
                       style="width:100%"
                       data-component="datatable"
                       data-component-id="events-table"
                       data-config='<?php echo json_encode([
                           'responsive' => true,
                           'pageLength' => 25,
                           'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                           'order' => [[1, 'asc']], // Sort by start date
                           'searching' => true,
                           'paging' => true,
                           'info' => true,
                           'autoWidth' => false,
                           'processing' => true,
                           'serverSide' => false,
                           'dom' => 'Bfrtip'
                       ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-columns='<?php echo json_encode([
                           ['data' => 'title', 'name' => 'title', 'title' => 'Event Title', 'width' => '25%'],
                           ['data' => 'start', 'name' => 'start', 'title' => 'Start Date/Time', 'type' => 'datetime', 'width' => '18%'],
                           ['data' => 'end', 'name' => 'end', 'title' => 'End Date/Time', 'type' => 'datetime', 'width' => '18%'],
                           ['data' => 'duration', 'name' => 'duration', 'title' => 'Duration', 'width' => '12%', 'className' => 'text-center'],
                           ['data' => 'userName', 'name' => 'userName', 'title' => 'Owner', 'width' => '15%'],
                           ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'width' => '12%', 'className' => 'text-center']
                       ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-buttons='<?php echo json_encode([
                           ['extend' => 'csv', 'text' => '📄 Export CSV', 'filename' => 'events_export'],
                           ['extend' => 'excel', 'text' => '📊 Export Excel', 'filename' => 'events_export'],
                           ['extend' => 'pdf', 'text' => '📑 Export PDF', 'filename' => 'events_export'],
                           ['extend' => 'print', 'text' => '🖨️ Print']
                       ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-api-url="../../backend/api.php?action=events"
                       data-real-time="true"
                       data-permissions='<?php echo json_encode([
                           'canExport' => true,
                           'canFilter' => true,
                           'canSort' => true
                       ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-auto-init="true">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Start Date/Time</th>
                            <th>End Date/Time</th>
                            <th>Duration</th>
                            <th>Owner</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be populated via AJAX/JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Event Statistics Summary -->
        <div id="eventsSummary" 
             class="events-summary"
             data-component="summary"
             data-component-id="events-summary"
             data-auto-init="true">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-number" id="totalEventsCount"><?php echo $totalEvents; ?></div>
                    <div class="summary-label">Total Events</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="upcomingEventsCount"><?php echo $upcomingEvents; ?></div>
                    <div class="summary-label">Upcoming</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="ongoingEventsCount"><?php echo $ongoingEvents; ?></div>
                    <div class="summary-label">Ongoing</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="myEventsCount"><?php echo $myEvents; ?></div>
                    <div class="summary-label">My Events</div>
                </div>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div id="eventModal" 
             class="modal"
             data-component="modal"
             data-component-id="event-details-modal"
             data-modal-type="details"
             data-auto-init="true">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Event Details</h2>
                    <span class="close" data-action="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="eventDetails">
                        <!-- Event details will be populated here -->
                    </div>
                    <div id="eventForm" style="display: none;">
                        <form id="editEventForm"
                              data-component="form"
                              data-component-id="edit-event-form"
                              data-validation="true"
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
                                    <input type="datetime-local" 
                                           id="eventStart" 
                                           name="start"
                                           class="form-control"
                                           data-validate="required"
                                           required>
                                    <div class="form-error" id="eventStartError"></div>
                                </div>
                                <div class="form-group">
                                    <label for="eventEnd">End Date & Time *</label>
                                    <input type="datetime-local" 
                                           id="eventEnd" 
                                           name="end"
                                           class="form-control"
                                           data-validate="required"
                                           required>
                                    <div class="form-error" id="eventEndError"></div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" 
                                        class="btn btn-primary"
                                        data-component="button"
                                        data-action="save-changes">
                                    Save Changes
                                </button>
                                <button type="button" 
                                        id="deleteEventBtn" 
                                        class="btn btn-danger"
                                        data-component="button"
                                        data-action="delete-event"
                                        data-confirm="Are you sure you want to delete this event?">
                                    Delete Event
                                </button>
                                <button type="button" 
                                        class="btn btn-outline"
                                        data-action="cancel-edit">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
);

// Add authentication status and page-specific data to JavaScript
?>
<script>
<?php echo $auth->generateAuthJs(); ?>

// Add events page specific data
window.EventsPage = {
    users: <?php echo json_encode($users); ?>,
    currentUser: <?php echo json_encode($currentUser); ?>,
    statistics: {
        total: <?php echo $totalEvents; ?>,
        upcoming: <?php echo $upcomingEvents; ?>,
        ongoing: <?php echo $ongoingEvents; ?>,
        myEvents: <?php echo $myEvents; ?>
    }
};

// Configuration for the component system
<?php echo $config->generateConfigJs(); ?>

console.log('Events page loaded with component architecture');
console.log('Statistics:', window.EventsPage.statistics);
</script>