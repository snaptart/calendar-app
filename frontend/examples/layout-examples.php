<?php
/**
 * Example Usage of Layout Templates
 * Location: frontend/examples/layout-examples.php
 * 
 * This file demonstrates how to use the layout templates
 */

require_once __DIR__ . '/../layouts/BaseLayout.php';
require_once __DIR__ . '/../layouts/AppLayout.php';
require_once __DIR__ . '/../layouts/AuthLayout.php';

// ============================================================================
// EXAMPLE 1: Basic BaseLayout Usage
// ============================================================================

function exampleBaseLayout() {
    $layout = new BaseLayout([
        'title' => 'My Custom Page',
        'css' => ['assets/css/custom.css'],
        'js' => ['assets/js/custom.js'],
        'bodyClasses' => ['custom-page']
    ]);
    
    $layout->render(function() {
        echo '<h1>Hello World!</h1>';
        echo '<p>This is content rendered with BaseLayout.</p>';
    });
}

// ============================================================================
// EXAMPLE 2: AppLayout for Calendar Page
// ============================================================================

function exampleCalendarPage() {
    AppLayout::createCalendarPage([
        'title' => 'Calendar - Ice Time Management',
        'pageTitle' => '📅 Collaborative Calendar',
        'activeNavItem' => 'calendar',
        'css' => ['assets/css/calendar.css'],
        'js' => [
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js',
            'assets/js/script.js'
        ]
    ], 
    // Calendar content
    function() {
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
        <?php
    },
    // Calendar controls
    function() {
        ?>
        <div class="user-filters">
            <h3>Show Calendars</h3>
            <div id="userCheckboxes" class="checkbox-group">
                <!-- User checkboxes will be populated here -->
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
        <?php
    });
}

// ============================================================================
// EXAMPLE 3: AppLayout for Events Table Page
// ============================================================================

function exampleEventsPage() {
    AppLayout::createTablePage([
        'title' => 'Events - Ice Time Management',
        'pageTitle' => '📅 Events Management',
        'activeNavItem' => 'events',
        'breadcrumbs' => [
            ['title' => 'Calendar', 'url' => './calendar.php'],
            ['title' => 'Events']
        ],
        'tableTitle' => 'Calendar Events',
        'tableDescription' => 'View and manage calendar events with advanced filtering and search',
        'tableActions' => function() {
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
        },
        'css' => ['assets/css/events.css', 'assets/css/table.css'],
        'js' => ['assets/js/events.js']
    ], 
    function() {
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
                    <div class="summary-number" id="totalEventsCount">0</div>
                    <div class="summary-label">Total Events</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="upcomingEventsCount">0</div>
                    <div class="summary-label">Upcoming</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="ongoingEventsCount">0</div>
                    <div class="summary-label">Ongoing</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number" id="myEventsCount">0</div>
                    <div class="summary-label">My Events</div>
                </div>
            </div>
        </div>
        <?php
    });
}

// ============================================================================
// EXAMPLE 4: AuthLayout for Login Page
// ============================================================================

function exampleLoginPage() {
    // Simple approach - use the static method
    AuthLayout::createLoginPage([
        'title' => 'Login - Ice Time Management',
        'js' => ['assets/js/auth.js']
    ]);
}

// ============================================================================
// EXAMPLE 5: Custom AppLayout Usage
// ============================================================================

function exampleCustomAppPage() {
    $layout = new AppLayout([
        'title' => 'Import Events - Ice Time Management',
        'pageTitle' => '📥 Import Events',
        'activeNavItem' => 'import',
        'breadcrumbs' => [
            ['title' => 'Calendar', 'url' => './calendar.php'],
            ['title' => 'Import Events']
        ],
        'css' => ['assets/css/import.css'],
        'js' => ['assets/js/import.js']
    ]);
    
    $layout->addAuthenticationScripts()
           ->addNavigationScript();
    
    $layout->renderContent(function() {
        ?>
        <!-- Import Instructions -->
        <div class="import-instructions">
            <div class="instruction-content">
                <h3>📋 Import Instructions</h3>
                <div class="instruction-grid">
                    <div class="instruction-item">
                        <div class="instruction-icon">📄</div>
                        <div class="instruction-text">
                            <h4>Supported Formats</h4>
                            <p>JSON, CSV, and iCalendar (.ics) files</p>
                        </div>
                    </div>
                    <div class="instruction-item">
                        <div class="instruction-icon">📊</div>
                        <div class="instruction-text">
                            <h4>File Limits</h4>
                            <p>Maximum 5MB file size, up to 20 events</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import Form -->
        <div class="import-form-container">
            <div class="import-form-content">
                <h3>Select File to Import</h3>
                
                <!-- File Drop Zone -->
                <div id="dropZone" class="drop-zone">
                    <div class="drop-zone-content">
                        <div class="drop-zone-icon">📎</div>
                        <div class="drop-zone-text">
                            <h4>Drag and drop your file here</h4>
                            <p>or click to browse files</p>
                        </div>
                        <input type="file" id="importFile" accept=".json,.csv,.ics,.ical,.txt" hidden>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('importFile').click()">
                            Browse Files
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    });
}

// ============================================================================
// ROUTING EXAMPLES
// ============================================================================

// Example of how you might use these in actual page files:

/*
// calendar.php
<?php
require_once 'layouts/AppLayout.php';
exampleCalendarPage();
?>

// events.php
<?php
require_once 'layouts/AppLayout.php';
exampleEventsPage();
?>

// login.php
<?php
require_once 'layouts/AuthLayout.php';
exampleLoginPage();
?>
*/

?>