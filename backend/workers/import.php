<?php
/**
 * Standalone Import Worker - Can be called directly or via proxy
 * Location: backend/workers/import.php
 * 
 * This worker handles all import functionality and can be accessed
 * either directly or through the main API proxy
 */

// Set proper error handling to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Only set headers if they haven't been set already (in case called via proxy)
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

// Start output buffering to capture any unwanted output
if (!ob_get_level()) {
    ob_start();
}

// Determine if we're being called directly or via proxy
$calledViaProxy = (strpos($_SERVER['SCRIPT_NAME'], 'api.php') !== false);

// Adjust include paths based on how we're called
if ($calledViaProxy) {
    // Called via proxy from api.php - paths are relative to backend/
    $basePath = '';
} else {
    // Called directly - paths are relative to backend/workers/
    $basePath = '../';
}

try {
    require_once $basePath . 'database/config.php';
    require_once $basePath . 'auth/Auth.php';
    require_once $basePath . 'models/User.php';
    require_once $basePath . 'models/Event.php';
    require_once $basePath . 'models/CalendarUpdate.php';
    require_once $basePath . 'models/EventImport.php';
} catch (Exception $e) {
    // Clean any output buffer and send error
    if (ob_get_level()) ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Initialize models with error handling
try {
    $calendarUpdate = new CalendarUpdate($pdo);
    $userModel = new User($pdo, $calendarUpdate);
    $eventModel = new Event($pdo, $calendarUpdate);
    $eventImport = new EventImport($pdo, $calendarUpdate, $userModel, $eventModel);
    $auth = new Auth($pdo);
} catch (Exception $e) {
    if (ob_get_level()) ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database initialization error: ' . $e->getMessage()]);
    exit();
}

// Handle preflight requests
if ($method === 'OPTIONS') {
    if (ob_get_level()) ob_clean();
    http_response_code(200);
    exit();
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Handle errors
 */
function handleError($message, $statusCode = 400) {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Import Worker Error: " . $message);
    sendResponse(['error' => $message], $statusCode);
}

/**
 * Validate file size
 */
function validateFileSize() {
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadedSize = $_SERVER['CONTENT_LENGTH'] ?? 0;
    
    if ($uploadedSize > $maxFileSize) {
        handleError('File size exceeds maximum allowed size of 5MB');
    }
}

/**
 * Log import activity
 */
function logImportActivity($userId, $action, $details = []) {
    error_log("Import Activity - User: {$userId}, Action: {$action}, Details: " . json_encode($details));
}

/**
 * Handle GET requests (for format information, etc.)
 */
function handleGetRequest($auth, $eventImport) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'import_formats':
        case 'supported_import_formats':
        case 'formats':
            // Don't require auth for format information
            sendResponse([
                'supported_formats' => [
                    'json' => [
                        'name' => 'JSON',
                        'extensions' => ['.json'],
                        'description' => 'JavaScript Object Notation format',
                        'mime_types' => ['application/json', 'text/json'],
                        'sample' => [
                            'title' => 'Sample Event',
                            'start' => '2025-06-15 10:00:00',
                            'end' => '2025-06-15 11:00:00',
                            'user_name' => 'John Doe',
                            'description' => 'Optional event description'
                        ]
                    ],
                    'csv' => [
                        'name' => 'CSV',
                        'extensions' => ['.csv', '.txt'],
                        'description' => 'Comma-separated values format',
                        'mime_types' => ['text/csv', 'text/plain'],
                        'sample_headers' => 'title,start,end,user_name,description',
                        'sample_row' => 'Team Meeting,2025-06-15 10:00:00,2025-06-15 11:00:00,John Doe,Weekly sync'
                    ],
                    'ics' => [
                        'name' => 'ICS/iCal',
                        'extensions' => ['.ics', '.ical'],
                        'description' => 'iCalendar format (RFC 5545)',
                        'mime_types' => ['text/calendar'],
                        'note' => 'Standard calendar format supported by most calendar applications'
                    ]
                ],
                'limits' => [
                    'max_file_size' => '5MB',
                    'max_file_size_bytes' => 5 * 1024 * 1024,
                    'max_events_per_import' => 20,
                    'supported_encodings' => ['UTF-8', 'ISO-8859-1']
                ],
                'requirements' => [
                    'authentication' => 'Required - Must be logged in',
                    'title' => 'Required - Event title (max 255 characters)',
                    'start' => 'Required - Start date/time (future dates only)',
                    'end' => 'Optional - End date/time (defaults to start time)',
                    'user_name' => 'Required - Must match existing user name exactly',
                    'description' => 'Optional - Event description'
                ],
                'validation_rules' => [
                    'future_events_only' => 'Only events with future start dates can be imported',
                    'no_conflicts' => 'Events cannot overlap with existing events for the same user',
                    'valid_users' => 'All user names must match existing users in the system',
                    'date_format' => 'Dates should be in YYYY-MM-DD HH:MM:SS format or ISO 8601'
                ],
                'error_handling' => [
                    'partial_import' => 'Valid events will be imported even if some events have errors',
                    'rollback' => 'If database error occurs, no events will be imported',
                    'error_reporting' => 'Detailed error messages provided for each failed event'
                ]
            ]);
            break;
            
        case 'import_stats':
        case 'stats':
            $currentUser = $auth->requireAuth();
            
            $stats = $eventImport->getImportStats($currentUser['id']);
            
            // Add additional statistics
            $stats['user_info'] = [
                'id' => $currentUser['id'],
                'name' => $currentUser['name']
            ];
            
            sendResponse($stats);
            break;
            
        case 'test':
            $currentUser = $auth->requireAuth();
            
            sendResponse([
                'status' => 'success',
                'message' => 'Import worker is operational',
                'access_method' => $GLOBALS['calledViaProxy'] ? 'proxied' : 'direct',
                'user' => [
                    'id' => $currentUser['id'],
                    'name' => $currentUser['name']
                ],
                'capabilities' => [
                    'validate' => 'Validate import files',
                    'import' => 'Import events to database',
                    'preview' => 'Preview events before import',
                    'formats' => 'Get supported format information',
                    'stats' => 'Get import statistics'
                ],
                'timestamp' => time(),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'max_upload_size' => ini_get('upload_max_filesize'),
                    'max_post_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit')
                ]
            ]);
            break;
            
        default:
            handleError('Invalid GET action. Supported: formats, stats, test');
    }
}

// Route the request based on method
if ($method === 'GET') {
    handleGetRequest($auth, $eventImport);
    exit();
}

// Only allow POST requests for file operations
if ($method !== 'POST') {
    handleError('Method not allowed. This endpoint accepts GET and POST requests only.', 405);
}

try {
    // Require authentication for all POST operations
    $currentUser = $auth->requireAuth();
    
    // Validate file size early for POST requests
    validateFileSize();
    
    // Determine the action
    $action = $_POST['action'] ?? $_GET['action'] ?? 'import';
    
    // Handle different types of POST requests
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // File upload request
        handleFileUploadRequest($currentUser, $action, $eventImport, $calendarUpdate);
    } else {
        // Regular form data or other POST request
        handleRegularPostRequest($currentUser, $action, $eventImport);
    }
    
} catch (Exception $e) {
    // Log the error with context
    logImportActivity($currentUser['id'] ?? 'unknown', 'error', [
        'error_message' => $e->getMessage(),
        'action' => $_POST['action'] ?? $_GET['action'] ?? 'unknown'
    ]);
    
    handleError($e->getMessage(), 500);
}

/**
 * Handle file upload requests
 */
function handleFileUploadRequest($currentUser, $action, $eventImport, $calendarUpdate) {
    // Log the import attempt
    logImportActivity($currentUser['id'], "attempt_{$action}", [
        'file_name' => $_FILES['import_file']['name'] ?? 'unknown',
        'file_size' => $_FILES['import_file']['size'] ?? 0
    ]);
    
    switch ($action) {
        case 'validate':
        case 'validate_import_file':
            // Validate import file without actually importing
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded for validation');
            }
            
            $validation = $eventImport->validateImportFile($_FILES['import_file']);
            
            logImportActivity($currentUser['id'], 'validate_complete', [
                'valid' => $validation['valid'],
                'event_count' => $validation['event_count'] ?? 0
            ]);
            
            sendResponse($validation);
            break;
            
        case 'import':
        case 'import_events':
            // Import events from file
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded for import');
            }
            
            $importResult = $eventImport->importEvents($_FILES['import_file'], $currentUser['id']);
            
            // Log import completion
            logImportActivity($currentUser['id'], 'import_complete', [
                'imported_count' => $importResult['imported_count'],
                'error_count' => $importResult['error_count'],
                'total_events' => $importResult['total_events']
            ]);
            
            // Broadcast notification if events were imported
            if ($importResult['imported_count'] > 0) {
                $calendarUpdate->broadcastNotification(
                    "{$currentUser['name']} imported {$importResult['imported_count']} events",
                    'info',
                    [
                        'import_user' => $currentUser['name'],
                        'imported_count' => $importResult['imported_count'],
                        'error_count' => $importResult['error_count'],
                        'timestamp' => time()
                    ]
                );
                
                // Also broadcast user activity
                $calendarUpdate->broadcastUserActivity(
                    $currentUser['id'],
                    'import_events',
                    [
                        'user_name' => $currentUser['name'],
                        'event_count' => $importResult['imported_count']
                    ]
                );
            }
            
            sendResponse($importResult, 201);
            break;
            
        case 'preview':
        case 'preview_import':
            // Preview import without saving to database
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded for preview');
            }
            
            // Create a temporary instance for preview only
            $previewImport = new EventImport($pdo, null, $userModel, null);
            $validation = $previewImport->validateImportFile($_FILES['import_file']);
            
            if ($validation['valid']) {
                // Add detailed preview information
                $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
                $format = $previewImport->detectFileFormat($_FILES['import_file']);
                $events = $previewImport->parseFileContent($fileContent, $format);
                
                $preview = [];
                $userCache = [];
                $previewLimit = 10; // Limit preview to first 10 events
                
                foreach (array_slice($events, 0, $previewLimit) as $index => $event) {
                    try {
                        $processed = $previewImport->validateAndProcessEvent($event, $userCache);
                        $preview[] = [
                            'index' => $index + 1,
                            'valid' => true,
                            'title' => $processed['title'],
                            'start' => $processed['start_datetime'],
                            'end' => $processed['end_datetime'],
                            'user_id' => $processed['user_id'],
                            'raw_data' => [
                                'title' => $event['title'] ?? '',
                                'start' => $event['start'] ?? '',
                                'end' => $event['end'] ?? '',
                                'user_name' => $event['user_name'] ?? ''
                            ]
                        ];
                    } catch (Exception $e) {
                        $preview[] = [
                            'index' => $index + 1,
                            'valid' => false,
                            'error' => $e->getMessage(),
                            'raw_data' => $event
                        ];
                    }
                }
                
                $validation['detailed_preview'] = $preview;
                $validation['preview_limit'] = $previewLimit;
                $validation['total_events'] = count($events);
                $validation['showing_first'] = min($previewLimit, count($events));
            }
            
            logImportActivity($currentUser['id'], 'preview_complete', [
                'valid' => $validation['valid'],
                'event_count' => $validation['event_count'] ?? 0
            ]);
            
            sendResponse($validation);
            break;
            
        default:
            handleError('Invalid file upload action. Supported: validate, import, preview');
    }
}

/**
 * Handle regular POST requests (non-file upload)
 */
function handleRegularPostRequest($currentUser, $action, $eventImport) {
    switch ($action) {
        case 'stats':
            $stats = $eventImport->getImportStats($currentUser['id']);
            $stats['user_info'] = [
                'id' => $currentUser['id'],
                'name' => $currentUser['name']
            ];
            sendResponse($stats);
            break;
            
        default:
            handleError('Invalid POST action without file upload. For file operations, use multipart/form-data.');
    }
}

// End output buffering
if (ob_get_level()) {
    ob_end_flush();
}
?>