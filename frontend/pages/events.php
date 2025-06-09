<?php
/**
 * Events Page - Converted from frontend/pages/events.html
 * Location: frontend/pages/events.php
 * 
 * Events management page using the new service layer architecture
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
    'pageTitle' => '📅 Events Management',
    'activeNavItem' => 'events',
    'breadcrumbs' => [
        ['title' => 'Calendar', 'url' => './calendar.php'],
        ['title' => 'Events']
    ]
]);

// Render the events page
AppLayout::createTablePage(
    array_merge($pageConfig, [
        'tableTitle' => 'Calendar Events',
        'tableDescription' => 'View and manage calendar events with advanced filtering and search',
        'tableActions' => function() use ($users) {
            ?>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="eventViewFilter">View:</label>
                    <select id="eventViewFilter" class="form-control form-control-sm">
                        <option value="all">All Events</option>
                        <option value="my">My Events Only</option>
                        <option value="others">Others' Events</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="eventStatusFilter">Status:</label>
                    <select id="eventStatusFilter" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="past">Past</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="eventUserFilter">User:</label>
                    <select id="eventUserFilter" class="form-control form-control-sm">
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
                <button id="refreshEventsBtn" class="btn btn-outline">
                    <span class="btn-icon">🔄</span>
                    Refresh Data
                </button>
                <button id="addEventBtn" class="btn btn-primary">
                    <span class="btn-icon">+</span>
                    Add Event
                </button>
            </div>
            <?php
        }
    ]),
    
    // Table content
    function() use ($totalEvents, $upcomingEvents, $ongoingEvents, $myEvents) {
        ?>
        <div class="table-wrapper">
            <div class="table-container">
                <table id="eventsTable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Date/Time</th>
                            <th>End Date/Time</th>
                            <th>Duration</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be populated via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Event Statistics Summary -->
        <div id="eventsSummary" class="events-summary">
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

        <!-- Event Modal -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Event Details</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="eventDetails">
                        <!-- Event details will be populated here -->
                    </div>
                    <div id="eventForm" style="display: none;">
                        <form id="editEventForm">
                            <div class="form-group">
                                <label for="eventTitle">Event Title</label>
                                <input type="text" id="eventTitle" placeholder="Enter event title..." required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="eventStart">Start Date & Time</label>
                                    <input type="datetime-local" id="eventStart" required>
                                </div>
                                <div class="form-group">
                                    <label for="eventEnd">End Date & Time</label>
                                    <input type="datetime-local" id="eventEnd" required>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" id="deleteEventBtn" class="btn btn-danger">Delete Event</button>
                                <button type="button" class="btn btn-outline" id="cancelEditBtn">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Events page specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Add event button functionality
            const addEventBtn = document.getElementById('addEventBtn');
            if (addEventBtn) {
                addEventBtn.addEventListener('click', function() {
                    window.location.href = './calendar.php';
                });
            }
            
            // Initialize the existing events.js functionality
            // The original events.js will work because we maintain the same DOM structure
            console.log('Events page initialized with PHP backend');
        });
        </script>
        <?php
    }
);

// Add authentication status to JavaScript
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

// Enhanced events page initialization
document.addEventListener('DOMContentLoaded', function() {
    if (typeof EventsApp !== 'undefined') {
        console.log('Initializing events page with PHP data...');
        console.log('Loaded ' + window.EventsPage.users.length + ' users from PHP');
        console.log('Statistics:', window.EventsPage.statistics);
    }
});
</script>
<?php

// The original events.js will handle the DataTables and other functionality
// because we've maintained the same DOM structure and element IDs
?>