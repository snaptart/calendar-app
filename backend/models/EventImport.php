<?php
/**
 * EventImport Model Class
 * Location: backend/models/EventImport.php
 * 
 * Handles importing events from various formats (JSON, CSV, ICS)
 * with validation, conflict detection, and user management
 */

class EventImport {
    private $pdo;
    private $calendarUpdate;
    private $userModel;
    private $eventModel;
    
    // Import limits
    const MAX_EVENTS_PER_IMPORT = 20;
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    
    // Supported formats
    const SUPPORTED_FORMATS = ['json', 'csv', 'ics'];
    
    public function __construct($pdo, $calendarUpdate = null, $userModel = null, $eventModel = null) {
        $this->pdo = $pdo;
        $this->calendarUpdate = $calendarUpdate;
        $this->userModel = $userModel;
        $this->eventModel = $eventModel;
    }
    
    /**
     * Import events from uploaded file
     * 
     * @param array $fileData $_FILES array data
     * @param int $importingUserId ID of user performing the import
     * @return array Import result with statistics
     */
    public function importEvents($fileData, $importingUserId) {
        try {
            // Validate file upload
            $this->validateFileUpload($fileData);
            
            // Get file format
            $format = $this->detectFileFormat($fileData);
            
            // Read and parse file content
            $fileContent = file_get_contents($fileData['tmp_name']);
            $parsedEvents = $this->parseFileContent($fileContent, $format);
            
            // Validate and process events
            $processedEvents = $this->processEvents($parsedEvents, $importingUserId);
            
            // Import valid events to database
            $importResult = $this->importToDatabase($processedEvents, $importingUserId);
            
            // Clean up temp file
            if (file_exists($fileData['tmp_name'])) {
                unlink($fileData['tmp_name']);
            }
            
            return $importResult;
            
        } catch (Exception $e) {
            error_log("Import error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Validate file upload
     * 
     * @param array $fileData File upload data
     * @throws Exception If validation fails
     */
    private function validateFileUpload($fileData) {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            switch ($fileData['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File size exceeds maximum allowed size of 5MB');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('File upload was interrupted');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file was uploaded');
                default:
                    throw new Exception('File upload failed');
            }
        }
        
        // Check file size
        if ($fileData['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of 5MB');
        }
        
        if ($fileData['size'] === 0) {
            throw new Exception('Uploaded file is empty');
        }
        
        // Check if file exists and is readable
        if (!file_exists($fileData['tmp_name']) || !is_readable($fileData['tmp_name'])) {
            throw new Exception('Uploaded file cannot be read');
        }
    }
    
    /**
     * Detect file format based on extension and content
     * 
     * @param array $fileData File upload data
     * @return string File format (json, csv, ics)
     * @throws Exception If format is not supported
     */
    public function detectFileFormat($fileData) {
        $fileName = strtolower($fileData['name']);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        // Map extensions to formats
        $extensionMap = [
            'json' => 'json',
            'csv' => 'csv',
            'ics' => 'ics',
            'ical' => 'ics',
            'txt' => 'csv' // Assume txt files are CSV
        ];
        
        if (isset($extensionMap[$extension])) {
            return $extensionMap[$extension];
        }
        
        // Try to detect based on content
        $content = file_get_contents($fileData['tmp_name'], false, null, 0, 1024);
        
        if (strpos($content, 'BEGIN:VCALENDAR') !== false) {
            return 'ics';
        }
        
        if ($content[0] === '{' || $content[0] === '[') {
            return 'json';
        }
        
        // Default to CSV if we can't determine
        return 'csv';
    }
    
    /**
     * Parse file content based on format
     * 
     * @param string $content File content
     * @param string $format File format
     * @return array Parsed events
     * @throws Exception If parsing fails
     */
    public function parseFileContent($content, $format) {
        switch ($format) {
            case 'json':
                return $this->parseJsonContent($content);
            case 'csv':
                return $this->parseCsvContent($content);
            case 'ics':
                return $this->parseIcsContent($content);
            default:
                throw new Exception('Unsupported file format');
        }
    }
    
    /**
     * Parse JSON content
     * 
     * @param string $content JSON content
     * @return array Parsed events
     * @throws Exception If JSON is invalid
     */
    private function parseJsonContent($content) {
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        // Handle both single event and array of events
        if (isset($data['title']) && isset($data['start'])) {
            return [$data]; // Single event
        }
        
        if (is_array($data)) {
            return $data; // Array of events
        }
        
        throw new Exception('JSON does not contain valid event data');
    }
    
    /**
     * Parse CSV content
     * 
     * @param string $content CSV content
     * @return array Parsed events
     * @throws Exception If CSV is invalid
     */
    private function parseCsvContent($content) {
        $lines = explode("\n", trim($content));
        
        if (empty($lines)) {
            throw new Exception('CSV file is empty');
        }
        
        // Get headers from first line
        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);
        
        // Map common header variations
        $headerMap = [
            'title' => ['title', 'name', 'subject', 'event'],
            'start' => ['start', 'start_date', 'start_datetime', 'begin'],
            'end' => ['end', 'end_date', 'end_datetime', 'finish'],
            'user_name' => ['user', 'user_name', 'owner', 'created_by'],
            'description' => ['description', 'notes', 'details']
        ];
        
        // Map headers to our field names
        $fieldMap = [];
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            foreach ($headerMap as $fieldName => $variations) {
                if (in_array($normalizedHeader, $variations)) {
                    $fieldMap[$index] = $fieldName;
                    break;
                }
            }
        }
        
        $events = [];
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $fields = str_getcsv($line);
            $event = [];
            
            foreach ($fields as $index => $value) {
                if (isset($fieldMap[$index])) {
                    $event[$fieldMap[$index]] = trim($value);
                }
            }
            
            if (!empty($event)) {
                $events[] = $event;
            }
        }
        
        if (empty($events)) {
            throw new Exception('No valid events found in CSV file');
        }
        
        return $events;
    }
    
    /**
     * Parse ICS content
     * 
     * @param string $content ICS content
     * @return array Parsed events
     * @throws Exception If ICS is invalid
     */
    private function parseIcsContent($content) {
        if (strpos($content, 'BEGIN:VCALENDAR') === false) {
            throw new Exception('Invalid ICS file format');
        }
        
        $events = [];
        $lines = explode("\n", $content);
        $currentEvent = null;
        $inEvent = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
                continue;
            }
            
            if ($line === 'END:VEVENT') {
                if ($currentEvent) {
                    $events[] = $this->processIcsEvent($currentEvent);
                }
                $inEvent = false;
                $currentEvent = null;
                continue;
            }
            
            if ($inEvent && $currentEvent !== null) {
                if (strpos($line, ':') !== false) {
                    list($property, $value) = explode(':', $line, 2);
                    $property = trim($property);
                    $value = trim($value);
                    
                    // Handle date-time properties
                    if (in_array($property, ['DTSTART', 'DTEND'])) {
                        $value = $this->parseIcsDateTime($value);
                    }
                    
                    $currentEvent[$property] = $value;
                }
            }
        }
        
        if (empty($events)) {
            throw new Exception('No valid events found in ICS file');
        }
        
        return $events;
    }
    
    /**
     * Process ICS event data into our format
     * 
     * @param array $icsEvent Raw ICS event data
     * @return array Processed event
     */
    private function processIcsEvent($icsEvent) {
        return [
            'title' => $icsEvent['SUMMARY'] ?? 'Imported Event',
            'start' => $icsEvent['DTSTART'] ?? null,
            'end' => $icsEvent['DTEND'] ?? null,
            'description' => $icsEvent['DESCRIPTION'] ?? null,
            'user_name' => $icsEvent['ORGANIZER'] ?? 'Unknown'
        ];
    }
    
    /**
     * Parse ICS date-time format
     * 
     * @param string $icsDateTime ICS datetime string
     * @return string MySQL datetime format
     */
    private function parseIcsDateTime($icsDateTime) {
        // Remove timezone info for now (YYYYMMDDTHHMMSSZ format)
        $icsDateTime = preg_replace('/[TZ]/', '', $icsDateTime);
        
        if (strlen($icsDateTime) >= 14) {
            $year = substr($icsDateTime, 0, 4);
            $month = substr($icsDateTime, 4, 2);
            $day = substr($icsDateTime, 6, 2);
            $hour = substr($icsDateTime, 8, 2);
            $minute = substr($icsDateTime, 10, 2);
            $second = substr($icsDateTime, 12, 2);
            
            return "$year-$month-$day $hour:$minute:$second";
        }
        
        return null;
    }
    
    /**
     * Process and validate parsed events
     * 
     * @param array $events Parsed events
     * @param int $importingUserId User performing the import
     * @return array Processed events with validation results
     */
    private function processEvents($events, $importingUserId) {
        if (count($events) > self::MAX_EVENTS_PER_IMPORT) {
            throw new Exception('Too many events in file. Maximum allowed: ' . self::MAX_EVENTS_PER_IMPORT);
        }
        
        $processedEvents = [];
        $userCache = [];
        
        foreach ($events as $index => $eventData) {
            try {
                $processed = $this->validateAndProcessEvent($eventData, $userCache);
                $processed['original_index'] = $index;
                $processedEvents[] = $processed;
            } catch (Exception $e) {
                // Add validation error to results but continue processing
                $processedEvents[] = [
                    'original_index' => $index,
                    'valid' => false,
                    'error' => $e->getMessage(),
                    'data' => $eventData
                ];
            }
        }
        
        return $processedEvents;
    }
    
    /**
     * Validate and process a single event
     * 
     * @param array $eventData Raw event data
     * @param array &$userCache User cache for performance
     * @return array Processed event
     * @throws Exception If validation fails
     */
    public function validateAndProcessEvent($eventData, &$userCache) {
        // Required fields validation
        if (empty($eventData['title'])) {
            throw new Exception('Event title is required');
        }
        
        if (empty($eventData['start'])) {
            throw new Exception('Event start date/time is required');
        }
        
        // Normalize and validate dates
        $startDateTime = $this->normalizeDateTime($eventData['start']);
        $endDateTime = isset($eventData['end']) ? 
            $this->normalizeDateTime($eventData['end']) : $startDateTime;
        
        // Validate future dates only
        $now = new DateTime();
        $startDate = new DateTime($startDateTime);
        
        if ($startDate <= $now) {
            throw new Exception('Only future events can be imported');
        }
        
        // Validate end date is after start date
        $endDate = new DateTime($endDateTime);
        if ($endDate < $startDate) {
            throw new Exception('End date must be after start date');
        }
        
        // Handle user assignment
        $userId = $this->resolveUserId($eventData, $userCache);
        
        // Check for conflicts with existing events from same user
        $this->checkEventConflicts($userId, $startDateTime, $endDateTime);
        
        return [
            'valid' => true,
            'title' => trim($eventData['title']),
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'user_id' => $userId,
            'description' => $eventData['description'] ?? null
        ];
    }
    
    /**
     * Normalize date/time to MySQL format
     * 
     * @param string $dateTime Various datetime formats
     * @return string MySQL datetime format
     * @throws Exception If date is invalid
     */
    private function normalizeDateTime($dateTime) {
        try {
            $dt = new DateTime($dateTime);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new Exception('Invalid date/time format: ' . $dateTime);
        }
    }
    
    /**
     * Resolve user ID from event data
     * 
     * @param array $eventData Event data
     * @param array &$userCache User cache
     * @return int User ID
     * @throws Exception If user cannot be resolved
     */
    private function resolveUserId($eventData, &$userCache) {
        $userName = $eventData['user_name'] ?? null;
        
        if (empty($userName)) {
            throw new Exception('User name is required for event assignment');
        }
        
        $userName = trim($userName);
        
        // Check cache first
        if (isset($userCache[$userName])) {
            return $userCache[$userName];
        }
        
        // Look up user in database
        if ($this->userModel) {
            $user = $this->userModel->getUserByName($userName);
            if ($user) {
                $userCache[$userName] = $user['id'];
                return $user['id'];
            }
        } else {
            // Fallback to direct database query
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE name = ?");
            $stmt->execute([$userName]);
            $user = $stmt->fetch();
            
            if ($user) {
                $userCache[$userName] = $user['id'];
                return $user['id'];
            }
        }
        
        throw new Exception("User '{$userName}' not found. Please ensure all users exist before importing.");
    }
    
    /**
     * Check for event conflicts (overlapping events from same user)
     * 
     * @param int $userId User ID
     * @param string $startDateTime Start date/time
     * @param string $endDateTime End date/time
     * @throws Exception If conflict is found
     */
    private function checkEventConflicts($userId, $startDateTime, $endDateTime) {
        $stmt = $this->pdo->prepare("
            SELECT id, title, start_datetime, end_datetime 
            FROM events 
            WHERE user_id = ? 
            AND (
                (start_datetime <= ? AND end_datetime > ?) OR
                (start_datetime < ? AND end_datetime >= ?) OR
                (start_datetime >= ? AND start_datetime < ?)
            )
            LIMIT 1
        ");
        
        $stmt->execute([
            $userId,
            $startDateTime, $startDateTime,
            $endDateTime, $endDateTime,
            $startDateTime, $endDateTime
        ]);
        
        $conflict = $stmt->fetch();
        
        if ($conflict) {
            throw new Exception(
                "Event conflicts with existing event '{$conflict['title']}' " .
                "({$conflict['start_datetime']} - {$conflict['end_datetime']})"
            );
        }
    }
    
    /**
     * Import processed events to database
     * 
     * @param array $processedEvents Validated events
     * @param int $importingUserId User performing import
     * @return array Import statistics
     */
    private function importToDatabase($processedEvents, $importingUserId) {
        $stats = [
            'total_events' => count($processedEvents),
            'imported_count' => 0,
            'error_count' => 0,
            'imported_events' => [],
            'errors' => []
        ];
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($processedEvents as $event) {
                if (!$event['valid']) {
                    $stats['error_count']++;
                    $stats['errors'][] = [
                        'index' => $event['original_index'],
                        'error' => $event['error'],
                        'data' => $event['data'] ?? null
                    ];
                    continue;
                }
                
                // Insert event
                $stmt = $this->pdo->prepare("
                    INSERT INTO events (user_id, title, start_datetime, end_datetime, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $event['user_id'],
                    $event['title'],
                    $event['start_datetime'],
                    $event['end_datetime']
                ]);
                
                $eventId = $this->pdo->lastInsertId();
                
                // Get full event data for broadcasting
                if ($this->eventModel) {
                    $fullEvent = $this->eventModel->getEventById($eventId);
                    if ($fullEvent) {
                        $stats['imported_events'][] = $fullEvent;
                        
                        // Broadcast update
                        if ($this->calendarUpdate) {
                            $this->calendarUpdate->broadcastUpdate('create', $fullEvent);
                        }
                    }
                }
                
                $stats['imported_count']++;
            }
            
            $this->pdo->commit();
            
            // Log import activity
            error_log("Import completed by user {$importingUserId}: {$stats['imported_count']} events imported, {$stats['error_count']} errors");
            
            return $stats;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Import transaction failed: " . $e->getMessage());
            throw new Exception('Import failed during database operation: ' . $e->getMessage());
        }
    }
    
    /**
     * Get import statistics for a user
     * 
     * @param int $userId User ID
     * @return array Import statistics
     */
    public function getImportStats($userId) {
        try {
            // This would require an import_logs table to track imports
            // For now, return basic event stats
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_events
                FROM events 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting import stats: " . $e->getMessage());
            return ['total_events' => 0, 'recent_events' => 0];
        }
    }
    
    /**
     * Validate file format and basic structure without full processing
     * 
     * @param array $fileData File upload data
     * @return array Validation result with preview
     */
    public function validateImportFile($fileData) {
        try {
            $this->validateFileUpload($fileData);
            $format = $this->detectFileFormat($fileData);
            
            $content = file_get_contents($fileData['tmp_name']);
            $events = $this->parseFileContent($content, $format);
            
            $preview = array_slice($events, 0, 5); // Show first 5 events
            
            return [
                'valid' => true,
                'format' => $format,
                'event_count' => count($events),
                'preview' => $preview,
                'within_limits' => count($events) <= self::MAX_EVENTS_PER_IMPORT
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>