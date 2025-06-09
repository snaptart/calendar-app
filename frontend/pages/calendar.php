<?php
/**
 * Calendar Page - Converted from frontend/pages/index.html
 * Location: frontend/pages/calendar.php
 * 
 * Main calendar view using the new service layer architecture
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
    'activeNavItem' => 'calendar'
]);

// Render the calendar page
AppLayout::createCalendarPage(
    $pageConfig,
    
    // Main calendar content
    function() use ($auth) {
        ?>
        <div class="calendar-wrapper">
            <div id="calendar"></div>
        </div>

        <!-- Event Modal -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add Event</h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <div class="form-group">
                            <label for="eventTitle">Event Title</label>
                            <input type="text" id="eventTitle" placeholder="Enter event title..." required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="eventStart">Start Date & Time</label>
                                <input type="text" id="eventStart" placeholder="Select start date & time..." required readonly>
                            </div>
                            <div class="form-group">
                                <label for="eventEnd">End Date & Time</label>
                                <input type="text" id="eventEnd" placeholder="Select end date & time..." required readonly>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Event</button>
                            <button type="button" id="deleteEventBtn" class="btn btn-danger" style="display: none;">Delete Event</button>
                            <button type="button" class="btn btn-outline" id="cancelBtn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        // Initialize calendar when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Add navigation functionality
            const importEventsBtn = document.getElementById('importEventsBtn');
            if (importEventsBtn) {
                importEventsBtn.addEventListener('click', function() {
                    window.location.href = './import.php';
                });
            }
            
            const viewEventsBtn = document.getElementById('viewEventsBtn');
            if (viewEventsBtn) {
                viewEventsBtn.addEventListener('click', function() {
                    window.location.href = './events.php';
                });
            }
            
            // Update active navigation
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(function(link) {
                link.classList.remove('active');
                const linkPage = link.getAttribute('href').replace('./', '');
                
                if ((currentPage === 'calendar.php' || currentPage === '') && linkPage === 'calendar.php') {
                    link.classList.add('active');
                }
            });
        });
        </script>
        <?php
    },
    
    // Calendar controls content
    function() use ($users, $currentUser) {
        ?>
        <div class="user-filters">
            <h3>Show Calendars</h3>
            <div id="userCheckboxes" class="checkbox-group">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        // Auto-select current user's calendar
                        $isCurrentUser = $currentUser && $user['id'] == $currentUser['id'];
                        $checked = $isCurrentUser ? 'checked' : '';
                        $checkedClass = $isCurrentUser ? ' checked' : '';
                        ?>
                        <div class="checkbox-item<?php echo $checkedClass; ?>">
                            <input 
                                type="checkbox" 
                                id="user-<?php echo $user['id']; ?>" 
                                value="<?php echo $user['id']; ?>"
                                <?php echo $checked; ?>
                            >
                            <div 
                                class="user-color" 
                                style="background-color: <?php echo htmlspecialchars($user['color'] ?? '#3498db'); ?>"
                            ></div>
                            <label 
                                for="user-<?php echo $user['id']; ?>" 
                                style="cursor: pointer;"
                            >
                                <?php echo htmlspecialchars($user['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No users found. <button id="refreshUsers" class="btn btn-small btn-outline">🔄 Refresh</button></p>
                <?php endif; ?>
            </div>
            <button id="refreshUsers" class="btn btn-small btn-outline">🔄 Refresh Users</button>
        </div>
        
        <div class="calendar-actions">
            <button id="addEventBtn" class="btn btn-primary">
                <span class="btn-icon">+</span>
                Add Event
            </button>
            <button id="viewEventsBtn" class="btn btn-outline">
                <span class="btn-icon">📋</span>
                View All Events
            </button>
            <button id="importEventsBtn" class="btn btn-outline">
                <span class="btn-icon">📥</span>
                Import Events
            </button>
            <div class="connection-status">
                <span id="connectionStatus" class="status">Initializing...</span>
            </div>
        </div>
        
        <script>
        // User checkbox event handling
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('#userCheckboxes input[type="checkbox"]');
            
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const checkboxItem = this.closest('.checkbox-item');
                    
                    if (this.checked) {
                        checkboxItem.classList.add('checked');
                    } else {
                        checkboxItem.classList.remove('checked');
                    }
                    
                    // Trigger calendar refresh with new user selection
                    if (typeof window.CalendarApp !== 'undefined' && window.CalendarApp.refreshCalendar) {
                        window.CalendarApp.refreshCalendar();
                    }
                });
            });
        });
        </script>
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

// Enhanced calendar initialization
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we have the calendar app script
    if (typeof CollaborativeCalendarApp !== 'undefined') {
        console.log('Initializing calendar with PHP data...');
        
        // The existing script.js will handle the calendar initialization
        // but now it can use the data provided by PHP
        if (window.CalendarPage.initialUsers.length > 0) {
            console.log('Loaded ' + window.CalendarPage.initialUsers.length + ' users from PHP');
        }
        
        if (window.CalendarPage.initialEvents.length > 0) {
            console.log('Loaded ' + window.CalendarPage.initialEvents.length + ' initial events from PHP');
        }
    }
});
</script>
<?php

// Include the original JavaScript functionality
// The original script.js will work with the new architecture
// because we've maintained the same DOM structure and element IDs
?>