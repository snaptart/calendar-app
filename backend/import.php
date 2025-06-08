<?php
/**
 * Dedicated Import Endpoint Handler - Fixed Version
 * Location: backend/import.php
 * 
 * This is a separate endpoint specifically for handling imports with proper error handling
 */

// Set proper error handling to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Start output buffering to capture any unwanted output
ob_start();

try {
    require_once 'database/config.php';
    require_once 'auth/Auth.php';
    require_once 'models/User.php';
    require_once 'models/Event.php';
    require_once 'models/CalendarUpdate.php';
    require_once 'models/EventImport.php';
} catch (Exception $e) {
    // Clean any output buffer and send error
    ob_clean();
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
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database initialization error: ' . $e->getMessage()]);
    exit();
}

// Handle preflight requests
if ($method === 'OPTIONS') {
    ob_clean();
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
    
    error_log("Import API Error: " . $message);
    sendResponse(['error' => $message], $statusCode);
}

// Only allow POST requests
if ($method !== 'POST') {
    handleError('Method not allowed', 405);
}

try {
    // Require authentication
    $currentUser = $auth->requireAuth();
    
    // Check if it's a file upload
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') === false) {
        handleError('Invalid content type. File upload required.');
    }
    
    // Validate file size
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $uploadedSize = $_SERVER['CONTENT_LENGTH'] ?? 0;
    
    if ($uploadedSize > $maxFileSize) {
        handleError('File size exceeds maximum allowed size of 5MB');
    }
    
    $action = $_POST['action'] ?? 'import';
    
    switch ($action) {
        case 'validate':
            // Validate import file without actually importing
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded');
            }
            
            $validation = $eventImport->validateImportFile($_FILES['import_file']);
            sendResponse($validation);
            break;
            
        case 'import':
            // Import events from file
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded');
            }
            
            $importResult = $eventImport->importEvents($_FILES['import_file'], $currentUser['id']);
            
            // Broadcast notification if events were imported
            if ($importResult['imported_count'] > 0) {
                $calendarUpdate->broadcastNotification(
                    "{$currentUser['name']} imported {$importResult['imported_count']} events",
                    'info',
                    [
                        'import_user' => $currentUser['name'],
                        'imported_count' => $importResult['imported_count'],
                        'error_count' => $importResult['error_count']
                    ]
                );
            }
            
            sendResponse($importResult, 201);
            break;
            
        case 'preview':
            // Preview import without saving to database
            if (!isset($_FILES['import_file'])) {
                handleError('No file uploaded');
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
                
                foreach (array_slice($events, 0, 10) as $index => $event) {
                    try {
                        $processed = $previewImport->validateAndProcessEvent($event, $userCache);
                        $preview[] = [
                            'index' => $index,
                            'valid' => true,
                            'title' => $processed['title'],
                            'start' => $processed['start_datetime'],
                            'end' => $processed['end_datetime'],
                            'user_id' => $processed['user_id']
                        ];
                    } catch (Exception $e) {
                        $preview[] = [
                            'index' => $index,
                            'valid' => false,
                            'error' => $e->getMessage(),
                            'raw_data' => $event
                        ];
                    }
                }
                
                $validation['detailed_preview'] = $preview;
            }
            
            sendResponse($validation);
            break;
            
        case 'formats':
            // Get supported formats information
            sendResponse([
                'formats' => [
                    'json' => [
                        'name' => 'JSON',
                        'extensions' => ['.json'],
                        'description' => 'JavaScript Object Notation format',
                        'sample' => [
                            'title' => 'Sample Event',
                            'start' => '2025-06-15 10:00:00',
                            'end' => '2025-06-15 11:00:00',
                            'user_name' => 'John Doe'
                        ]
                    ],
                    'csv' => [
                        'name' => 'CSV',
                        'extensions' => ['.csv', '.txt'],
                        'description' => 'Comma-separated values format',
                        'sample_headers' => 'title,start,end,user_name,description'
                    ],
                    'ics' => [
                        'name' => 'ICS/iCal',
                        'extensions' => ['.ics', '.ical'],
                        'description' => 'iCalendar format'
                    ]
                ],
                'limits' => [
                    'max_file_size' => '5MB',
                    'max_events' => 20
                ],
                'requirements' => [
                    'title' => 'Required - Event title',
                    'start' => 'Required - Start date/time (future dates only)',
                    'end' => 'Optional - End date/time (defaults to start time)',
                    'user_name' => 'Required - Must match existing user name',
                    'description' => 'Optional - Event description'
                ]
            ]);
            break;
            
        default:
            handleError('Invalid action. Supported actions: validate, import, preview, formats');
    }
    
} catch (Exception $e) {
    handleError($e->getMessage(), 500);
}

// End output buffering
ob_end_flush();
?>