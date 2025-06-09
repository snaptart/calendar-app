<?php
/**
 * Service Usage Examples
 * Location: frontend/examples/service-examples.php
 * 
 * Demonstrates how to use the service layer components
 */

require_once __DIR__ . '/../services/ApiClient.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ConfigService.php';

// ============================================================================
// EXAMPLE 1: Basic ApiClient Usage
// ============================================================================

function exampleApiClientBasic() {
    try {
        // Create API client
        $api = ApiClient::create(['debug' => true]);
        
        // Test connection
        $testResult = $api->testConnection();
        echo "API Status: " . $testResult['message'] . "\n";
        
        // Get all events
        $events = $api->getEvents();
        echo "Found " . count($events) . " events\n";
        
        // Create new event
        $newEvent = $api->createEvent(
            'Team Meeting',
            '2025-06-15 10:00:00',
            '2025-06-15 11:00:00'
        );
        echo "Created event: " . $newEvent['title'] . "\n";
        
    } catch (UnauthorizedException $e) {
        echo "Authentication required: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "API Error: " . $e->getMessage() . "\n";
    }
}

// ============================================================================
// EXAMPLE 2: AuthService Usage in Pages
// ============================================================================

function exampleAuthServiceInPage() {
    // Simple authentication guard
    $auth = AuthService::guard(true); // Require authentication
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    if ($currentUser) {
        echo "Welcome, " . $currentUser['name'] . "!\n";
        
        // Check if user can edit a specific event
        $sampleEvent = ['user_id' => $currentUser['id']];
        if ($auth->canEditEvent($sampleEvent)) {
            echo "You can edit this event\n";
        }
    }
}

// ============================================================================
// EXAMPLE 3: Complete Page with Services
// ============================================================================

function exampleCalendarPageWithServices() {
    // Initialize services
    $config = ConfigService::getInstance();
    $auth = AuthService::guard(true);
    $api = ApiClient::createAuthenticated();
    
    // Get page configuration
    $pageConfig = $config->forLayout('calendar', [
        'breadcrumbs' => [
            ['title' => 'Calendar']
        ]
    ]);
    
    // Load data
    try {
        $users = $api->getUsers();
        $events = $api->getEvents();
        
        // Create layout and render
        require_once __DIR__ . '/../layouts/AppLayout.php';
        
        AppLayout::createCalendarPage($pageConfig, 
            // Calendar content
            function() use ($events) {
                ?>
                <div class="calendar-wrapper">
                    <div id="calendar"></div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize calendar with events
                    const events = <?php echo json_encode($events); ?>;
                    
                    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                        initialView: window.CalendarConfig.calendar.default_view,
                        events: events,
                        height: window.CalendarConfig.calendar.height
                    });
                    
                    calendar.render();
                });
                </script>
                <?php
            },
            // Controls content  
            function() use ($users) {
                ?>
                <div class="user-filters">
                    <h3>Show Calendars</h3>
                    <div class="checkbox-group">
                        <?php foreach ($users as $user): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="user-<?php echo $user['id']; ?>" value="<?php echo $user['id']; ?>">
                                <div class="user-color" style="background-color: <?php echo $user['color']; ?>"></div>
                                <label for="user-<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
            }
        );
        
    } catch (Exception $e) {
        echo "Error loading page: " . $e->getMessage();
    }
}

// ============================================================================
// EXAMPLE 4: Login Page with Services
// ============================================================================

function exampleLoginPageWithServices() {
    $config = ConfigService::getInstance();
    $auth = new AuthService();
    
    // Check if already authenticated
    if ($auth->isAuthenticated()) {
        $auth->redirectAfterLogin('./calendar.php');
        return;
    }
    
    // Handle form submission
    if ($_POST) {
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $result = $auth->login(
                $_POST['email'],
                $_POST['password'],
                isset($_POST['rememberMe'])
            );
            
            if ($result['success']) {
                $auth->redirectAfterLogin();
            } else {
                $error = $result['error'];
            }
        }
    }
    
    // Get page configuration
    $pageConfig = $config->forLayout('login');
    
    // Render login page
    require_once __DIR__ . '/../layouts/AuthLayout.php';
    
    $layout = new AuthLayout($pageConfig);
    $layout->addAuthScripts();
    
    $layout->renderAuthPage(function() use ($error ?? null) {
        if (isset($error)) {
            echo '<div class="error-message">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        
        <form method="POST" class="auth-form active">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Remember me</label>
            </div>
            
            <button type="submit" class="auth-button">Sign In</button>
        </form>
        <?php
    });
}

// ============================================================================
// EXAMPLE 5: File Upload with ApiClient
// ============================================================================

function exampleFileUpload() {
    if ($_FILES['importFile']) {
        try {
            $api = ApiClient::createAuthenticated();
            
            $file = $_FILES['importFile'];
            $tempPath = $file['tmp_name'];
            $originalName = $file['name'];
            
            // Validate file
            $validation = $api->validateImportFile($tempPath, $originalName);
            
            if ($validation['valid']) {
                // Import the file
                $result = $api->importEvents($tempPath, $originalName);
                
                echo "Import successful! Imported " . $result['imported_count'] . " events.\n";
            } else {
                echo "File validation failed: " . implode(', ', $validation['errors']) . "\n";
            }
            
        } catch (Exception $e) {
            echo "Import error: " . $e->getMessage() . "\n";
        }
    }
}

// ============================================================================
// EXAMPLE 6: Configuration-Driven Component
// ============================================================================

function exampleConfigDrivenTable() {
    $config = ConfigService::getInstance();
    
    // Get table configuration
    $tableConfig = [
        'title' => 'Events Management',
        'description' => 'View and manage calendar events',
        'css' => $config->getDataTablesCss(),
        'js' => $config->getJsFiles('events'),
        'activeNavItem' => 'events'
    ];
    
    // Use configuration in layout
    require_once __DIR__ . '/../layouts/AppLayout.php';
    
    AppLayout::createTablePage($tableConfig, function() {
        ?>
        <table id="eventsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Owner</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data populated via AJAX -->
            </tbody>
        </table>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#eventsTable').DataTable({
                ajax: {
                    url: window.CalendarConfig.api.baseUrl + '?action=events',
                    dataSrc: ''
                },
                columns: [
                    { data: 'title' },
                    { data: 'start' },
                    { data: 'end' },
                    { data: 'extendedProps.userName' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return '<button class="btn btn-small">Edit</button>';
                        }
                    }
                ]
            });
        });
        </script>
        <?php
    });
}

// ============================================================================
// EXAMPLE 7: Error Handling and Debugging
// ============================================================================

function exampleErrorHandling() {
    try {
        $api = ApiClient::create(['debug' => true]);
        
        // This will throw an exception if not authenticated
        $events = $api->getEvents();
        
    } catch (UnauthorizedException $e) {
        // Handle authentication error
        error_log("User not authenticated: " . $e->getMessage());
        header('Location: ./login.php');
        exit;
        
    } catch (ForbiddenException $e) {
        // Handle permission error
        error_log("Access forbidden: " . $e->getMessage());
        echo "You don't have permission to access this resource.";
        
    } catch (NotFoundException $e) {
        // Handle not found error
        error_log("Resource not found: " . $e->getMessage());
        echo "The requested resource was not found.";
        
    } catch (ApiException $e) {
        // Handle other API errors
        error_log("API Error: " . $e->getMessage());
        echo "An error occurred while communicating with the server.";
        
    } catch (Exception $e) {
        // Handle unexpected errors
        error_log("Unexpected error: " . $e->getMessage());
        echo "An unexpected error occurred.";
    }
}

// ============================================================================
// EXAMPLE 8: Creating a Complete Page File
// ============================================================================

/*
// Example of a complete calendar.php file using services

<?php
require_once __DIR__ . '/services/ConfigService.php';
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/ApiClient.php';
require_once __DIR__ . '/layouts/AppLayout.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = AuthService::guard(true);
$api = ApiClient::createAuthenticated();

// Load data
try {
    $users = $api->getUsers();
    $currentUser = $auth->getCurrentUser();
    
    // Get page configuration
    $pageConfig = $config->forLayout('calendar');
    
    // Render page
    AppLayout::createCalendarPage($pageConfig, 
        function() {
            // Calendar content
            include __DIR__ . '/components/calendar/Calendar.php';
        },
        function() use ($users) {
            // Controls content
            include __DIR__ . '/components/calendar/Controls.php';
        }
    );
    
} catch (Exception $e) {
    error_log("Calendar page error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo "Error loading calendar page.";
}
?>
*/

?>