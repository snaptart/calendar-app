<?php
/**
 * Event Model Class for itmdev
 * Location: backend/models/Event.php
 * 
 * Updated to work with the itmdev database schema using event and episode tables
 */

class Event {
    private $pdo;
    private $calendarUpdate;
    
    public function __construct($pdo, $calendarUpdate = null) {
        $this->pdo = $pdo;
        $this->calendarUpdate = $calendarUpdate;
    }
    
    /**
     * Get all events, optionally filtered by user IDs
     * 
     * @param array $userIds Optional array of user IDs to filter by
     * @return array Array of formatted events
     */
    public function getAllEvents($userIds = null) {
        try {
            if ($userIds && !empty($userIds)) {
                // Validate user IDs
                $userIds = array_filter($userIds, 'is_numeric');
                if (empty($userIds)) {
                    return [];
                }
                
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $stmt = $this->pdo->prepare("
                    SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                           e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                           u.user_Name as user_name
                    FROM episode e 
                    JOIN user u ON e.user_ID = u.user_ID 
                    WHERE e.user_ID IN ($placeholders) AND u.user_Status = 'Active'
                    ORDER BY e.episode_Start_Date_Time
                ");
                $stmt->execute($userIds);
            } else {
                $stmt = $this->pdo->query("
                    SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                           e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                           u.user_Name as user_name
                    FROM episode e 
                    JOIN user u ON e.user_ID = u.user_ID 
                    WHERE u.user_Status = 'Active'
                    ORDER BY e.episode_Start_Date_Time
                ");
            }
            
            $events = $stmt->fetchAll();
            
            // Format for FullCalendar
            return array_map([$this, 'formatEventForCalendar'], $events);
            
        } catch (PDOException $e) {
            error_log("Error fetching events: " . $e->getMessage());
            throw new Exception("Failed to fetch events");
        }
    }
    
    /**
     * Get events by user ID
     * 
     * @param int $userId User ID
     * @param string $startDate Optional start date filter (YYYY-MM-DD)
     * @param string $endDate Optional end date filter (YYYY-MM-DD)
     * @return array Array of formatted events
     */
    public function getEventsByUserId($userId, $startDate = null, $endDate = null) {
        try {
            $sql = "
                SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                       e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                       u.user_Name as user_name
                FROM episode e 
                JOIN user u ON e.user_ID = u.user_ID 
                WHERE e.user_ID = ? AND u.user_Status = 'Active'
            ";
            $params = [$userId];
            
            if ($startDate) {
                $sql .= " AND e.episode_Start_Date_Time >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND e.episode_Start_Date_Time <= ?";
                $params[] = $endDate . ' 23:59:59';
            }
            
            $sql .= " ORDER BY e.episode_Start_Date_Time";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $events = $stmt->fetchAll();
            
            // Format for FullCalendar
            return array_map([$this, 'formatEventForCalendar'], $events);
            
        } catch (PDOException $e) {
            error_log("Error fetching events by user ID: " . $e->getMessage());
            throw new Exception("Failed to fetch user events");
        }
    }
    
    /**
     * Get event by ID
     * 
     * @param int $eventId Event ID (episode_ID)
     * @return array|null Event data or null if not found
     */
    public function getEventById($eventId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                       e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                       u.user_Name as user_name
                FROM episode e 
                JOIN user u ON e.user_ID = u.user_ID 
                WHERE e.episode_ID = ? AND u.user_Status = 'Active'
            ");
            $stmt->execute([$eventId]);
            
            $event = $stmt->fetch();
            
            return $event ? $this->formatEventForCalendar($event) : null;
            
        } catch (PDOException $e) {
            error_log("Error fetching event by ID: " . $e->getMessage());
            throw new Exception("Failed to fetch event");
        }
    }
    
    /**
     * Create a new event
     * 
     * @param int $userId User ID who owns the event
     * @param string $title Event title
     * @param string $startDateTime Start date and time
     * @param string $endDateTime End date and time
     * @param array $additionalData Optional additional event data
     * @return array Created event data
     */
    public function createEvent($userId, $title, $startDateTime, $endDateTime = null, $additionalData = []) {
        try {
            // Validate input
            $this->validateEventData($title, $startDateTime, $endDateTime);
            
            // If no end time provided, use start time + 1 hour default
            if (!$endDateTime) {
                $startDate = new DateTime($startDateTime);
                $endDate = clone $startDate;
                $endDate->add(new DateInterval('PT1H'));
                $endDateTime = $endDate->format('Y-m-d H:i:s');
            }
            
            // Validate dates
            $this->validateEventDates($startDateTime, $endDateTime);
            
            // Calculate duration in minutes
            $start = new DateTime($startDateTime);
            $end = new DateTime($endDateTime);
            $duration = $end->diff($start)->i + ($end->diff($start)->h * 60);
            
            // Get default values for itmdev fields
            $facilityId = $this->getDefaultFacilityId();
            $programId = $this->getDefaultProgramId();
            $teamId = $this->getDefaultTeamId($userId);
            $resourceId = $this->getDefaultResourceId();
            
            // Begin transaction to create both event and episode
            $this->pdo->beginTransaction();
            
            try {
                // First create the event record
                $eventStmt = $this->pdo->prepare("
                    INSERT INTO event (
                        event_Type, event_Start_Date_Time, event_End_Date_Time, 
                        event_Last_Date_Time, episode_Duration, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $eventStmt->execute([
                    'Calendar Event',
                    $startDateTime,
                    $endDateTime,
                    $endDateTime,
                    $duration,
                    'calendar_system'
                ]);
                
                $eventId = $this->pdo->lastInsertId();
                
                // Then create the episode record
                $episodeStmt = $this->pdo->prepare("
                    INSERT INTO episode (
                        event_ID, resource_ID, facility_ID, program_ID, team_ID_new, 
                        user_ID, episode_Start_Date_Time, episode_End_Date_Time, 
                        episode_Duration, episode_Title, episode_Description, 
                        episode_Color, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $episodeStmt->execute([
                    $eventId,
                    $resourceId,
                    $facilityId,
                    $programId,
                    $teamId,
                    $userId,
                    $startDateTime,
                    $endDateTime,
                    $duration,
                    trim($title),
                    $additionalData['description'] ?? null,
                    $additionalData['color'] ?? '#3498db',
                    'calendar_system'
                ]);
                
                $episodeId = $this->pdo->lastInsertId();
                
                $this->pdo->commit();
                
                // Get the created event with user info
                $createdEvent = $this->getEventById($episodeId);
                
                // Broadcast update
                if ($this->calendarUpdate && $createdEvent) {
                    $this->calendarUpdate->broadcastUpdate('create', $createdEvent);
                }
                
                return $createdEvent;
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            throw new Exception("Failed to create event");
        }
    }
    
    /**
     * Update an event
     * 
     * @param int $eventId Event ID (episode_ID)
     * @param int $userId User ID (for ownership verification)
     * @param array $data Event data to update
     * @return array Updated event data
     */
    public function updateEvent($eventId, $userId, $data) {
        try {
            // Check if event exists and user owns it
            $existingEvent = $this->getEventRaw($eventId);
            
            if (!$existingEvent) {
                throw new Exception('Event not found');
            }
            
            if ($existingEvent['user_ID'] != $userId) {
                throw new Exception('You can only edit your own events');
            }
            
            // Validate required fields
            if (!isset($data['title']) || !isset($data['start'])) {
                throw new Exception('Missing required fields: title and start are required');
            }
            
            // Validate data
            $this->validateEventData($data['title'], $data['start'], $data['end'] ?? null);
            
            if (isset($data['end'])) {
                $this->validateEventDates($data['start'], $data['end']);
            }
            
            // Calculate new duration
            $start = new DateTime($data['start']);
            $end = new DateTime($data['end'] ?? $data['start']);
            $duration = $end->diff($start)->i + ($end->diff($start)->h * 60);
            
            // Begin transaction to update both event and episode
            $this->pdo->beginTransaction();
            
            try {
                // Update episode record
                $episodeStmt = $this->pdo->prepare("
                    UPDATE episode 
                    SET episode_Title = ?, episode_Start_Date_Time = ?, 
                        episode_End_Date_Time = ?, episode_Duration = ?,
                        updated_by = ?
                    WHERE episode_ID = ?
                ");
                
                $episodeStmt->execute([
                    trim($data['title']),
                    $data['start'],
                    $data['end'] ?? $data['start'],
                    $duration,
                    'calendar_system',
                    $eventId
                ]);
                
                // Update corresponding event record
                $eventStmt = $this->pdo->prepare("
                    UPDATE event 
                    SET event_Start_Date_Time = ?, event_End_Date_Time = ?, 
                        event_Last_Date_Time = ?, episode_Duration = ?,
                        updated_by = ?
                    WHERE event_ID = ?
                ");
                
                $eventStmt->execute([
                    $data['start'],
                    $data['end'] ?? $data['start'],
                    $data['end'] ?? $data['start'],
                    $duration,
                    'calendar_system',
                    $existingEvent['event_ID']
                ]);
                
                $this->pdo->commit();
                
                if ($episodeStmt->rowCount() === 0) {
                    throw new Exception('No changes made to the event');
                }
                
                // Get updated event
                $updatedEvent = $this->getEventById($eventId);
                
                // Broadcast update
                if ($this->calendarUpdate && $updatedEvent) {
                    $this->calendarUpdate->broadcastUpdate('update', $updatedEvent);
                }
                
                return $updatedEvent;
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            throw new Exception("Failed to update event");
        }
    }
    
    /**
     * Delete an event
     * 
     * @param int $eventId Event ID (episode_ID)
     * @param int $userId User ID (for ownership verification)
     * @return bool Success status
     */
    public function deleteEvent($eventId, $userId) {
        try {
            // Check if event exists and user owns it
            $existingEvent = $this->getEventRaw($eventId);
            
            if (!$existingEvent) {
                throw new Exception('Event not found');
            }
            
            if ($existingEvent['user_ID'] != $userId) {
                throw new Exception('You can only delete your own events');
            }
            
            // Begin transaction to delete both episode and event if no other episodes
            $this->pdo->beginTransaction();
            
            try {
                // Delete episode
                $episodeStmt = $this->pdo->prepare("DELETE FROM episode WHERE episode_ID = ?");
                $episodeStmt->execute([$eventId]);
                
                // Check if this was the last episode for this event
                $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM episode WHERE event_ID = ?");
                $checkStmt->execute([$existingEvent['event_ID']]);
                $episodeCount = $checkStmt->fetchColumn();
                
                // If no more episodes, delete the event record too
                if ($episodeCount == 0) {
                    $eventStmt = $this->pdo->prepare("DELETE FROM event WHERE event_ID = ?");
                    $eventStmt->execute([$existingEvent['event_ID']]);
                }
                
                $this->pdo->commit();
                
                // Broadcast update
                if ($this->calendarUpdate) {
                    $this->calendarUpdate->broadcastUpdate('delete', ['id' => $eventId]);
                }
                
                return true;
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting event: " . $e->getMessage());
            throw new Exception("Failed to delete event");
        }
    }
    
    /**
     * Get events within a date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $userIds Optional array of user IDs to filter by
     * @return array Array of formatted events
     */
    public function getEventsInRange($startDate, $endDate, $userIds = null) {
        try {
            $sql = "
                SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                       e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                       u.user_Name as user_name
                FROM episode e 
                JOIN user u ON e.user_ID = u.user_ID 
                WHERE e.episode_Start_Date_Time >= ? AND e.episode_Start_Date_Time <= ?
                  AND u.user_Status = 'Active'
            ";
            $params = [$startDate, $endDate . ' 23:59:59'];
            
            if ($userIds && !empty($userIds)) {
                $userIds = array_filter($userIds, 'is_numeric');
                if (!empty($userIds)) {
                    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                    $sql .= " AND e.user_ID IN ($placeholders)";
                    $params = array_merge($params, $userIds);
                }
            }
            
            $sql .= " ORDER BY e.episode_Start_Date_Time";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $events = $stmt->fetchAll();
            
            return array_map([$this, 'formatEventForCalendar'], $events);
            
        } catch (PDOException $e) {
            error_log("Error fetching events in range: " . $e->getMessage());
            throw new Exception("Failed to fetch events in date range");
        }
    }
    
    /**
     * Search events by title
     * 
     * @param string $query Search query
     * @param int $userId Optional user ID to filter by
     * @param int $limit Maximum results to return
     * @return array Array of matching events
     */
    public function searchEvents($query, $userId = null, $limit = 20) {
        try {
            $searchTerm = "%{$query}%";
            
            $sql = "
                SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                       e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                       u.user_Name as user_name
                FROM episode e 
                JOIN user u ON e.user_ID = u.user_ID 
                WHERE e.episode_Title LIKE ? AND u.user_Status = 'Active'
            ";
            $params = [$searchTerm];
            
            if ($userId) {
                $sql .= " AND e.user_ID = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY e.episode_Start_Date_Time DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $events = $stmt->fetchAll();
            
            return array_map([$this, 'formatEventForCalendar'], $events);
            
        } catch (PDOException $e) {
            error_log("Error searching events: " . $e->getMessage());
            throw new Exception("Failed to search events");
        }
    }
    
    /**
     * Get upcoming events for a user
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of events to return
     * @return array Array of upcoming events
     */
    public function getUpcomingEvents($userId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.episode_ID, e.episode_Title, e.episode_Start_Date_Time, 
                       e.episode_End_Date_Time, e.episode_Color, e.user_ID,
                       u.user_Name as user_name
                FROM episode e 
                JOIN user u ON e.user_ID = u.user_ID 
                WHERE e.user_ID = ? AND e.episode_Start_Date_Time >= NOW() 
                  AND u.user_Status = 'Active'
                ORDER BY e.episode_Start_Date_Time ASC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            
            $events = $stmt->fetchAll();
            
            return array_map([$this, 'formatEventForCalendar'], $events);
            
        } catch (PDOException $e) {
            error_log("Error fetching upcoming events: " . $e->getMessage());
            throw new Exception("Failed to fetch upcoming events");
        }
    }
    
    /**
     * Get event statistics for a user
     * 
     * @param int $userId User ID
     * @return array Event statistics
     */
    public function getEventStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN episode_Start_Date_Time >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN episode_Start_Date_Time < NOW() THEN 1 END) as past_events,
                    MIN(episode_Start_Date_Time) as first_event,
                    MAX(episode_Start_Date_Time) as latest_event
                FROM episode 
                WHERE user_ID = ?
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error fetching event stats: " . $e->getMessage());
            throw new Exception("Failed to fetch event statistics");
        }
    }
    
    /**
     * Format event data for FullCalendar
     * 
     * @param array $event Raw event data from database
     * @return array Formatted event data
     */
    private function formatEventForCalendar($event) {
        return [
            'id' => (int)$event['episode_ID'],
            'title' => $event['episode_Title'],
            'start' => $event['episode_Start_Date_Time'],
            'end' => $event['episode_End_Date_Time'],
            'backgroundColor' => $event['episode_Color'] ?? '#3498db',
            'borderColor' => $event['episode_Color'] ?? '#3498db',
            'extendedProps' => [
                'userId' => (int)$event['user_ID'],
                'userName' => $event['user_name']
            ]
        ];
    }
    
    /**
     * Get raw event data (internal use)
     * 
     * @param int $eventId Event ID (episode_ID)
     * @return array|null Raw event data
     */
    private function getEventRaw($eventId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM episode WHERE episode_ID = ?");
            $stmt->execute([$eventId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching raw event: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get default facility ID
     */
    private function getDefaultFacilityId() {
        try {
            $stmt = $this->pdo->query("SELECT facility_ID FROM facility WHERE facility_Status = 'Active' ORDER BY facility_ID LIMIT 1");
            $facility = $stmt->fetch();
            return $facility ? $facility['facility_ID'] : 1;
        } catch (Exception $e) {
            return 1;
        }
    }
    
    /**
     * Get default program ID
     */
    private function getDefaultProgramId() {
        try {
            $stmt = $this->pdo->query("SELECT program_ID FROM program WHERE program_Status = 'Active' ORDER BY program_ID LIMIT 1");
            $program = $stmt->fetch();
            return $program ? $program['program_ID'] : 1;
        } catch (Exception $e) {
            return 1;
        }
    }
    
    /**
     * Get default team ID
     */
    private function getDefaultTeamId($userId = null) {
        try {
            $stmt = $this->pdo->query("SELECT team_ID FROM team ORDER BY team_ID LIMIT 1");
            $team = $stmt->fetch();
            return $team ? $team['team_ID'] : 1;
        } catch (Exception $e) {
            return 1;
        }
    }
    
    /**
     * Get default resource ID
     */
    private function getDefaultResourceId() {
        try {
            $stmt = $this->pdo->query("SELECT resource_ID FROM resource WHERE resource_Status = 'Active' ORDER BY resource_ID LIMIT 1");
            $resource = $stmt->fetch();
            return $resource ? $resource['resource_ID'] : 1;
        } catch (Exception $e) {
            return 1;
        }
    }
    
    /**
     * Validate event data
     * 
     * @param string $title Event title
     * @param string $startDateTime Start date and time
     * @param string $endDateTime End date and time (optional)
     * @throws Exception If validation fails
     */
    private function validateEventData($title, $startDateTime, $endDateTime = null) {
        if (empty($title) || strlen(trim($title)) < 1) {
            throw new Exception('Event title is required');
        }
        
        if (strlen(trim($title)) > 100) {
            throw new Exception('Event title is too long (maximum 100 characters)');
        }
        
        if (empty($startDateTime)) {
            throw new Exception('Start date and time is required');
        }
        
        // Validate date format
        if (!$this->isValidDateTime($startDateTime)) {
            throw new Exception('Invalid start date and time format');
        }
        
        if ($endDateTime && !$this->isValidDateTime($endDateTime)) {
            throw new Exception('Invalid end date and time format');
        }
    }
    
    /**
     * Validate that end date is after start date
     * 
     * @param string $startDateTime Start date and time
     * @param string $endDateTime End date and time
     * @throws Exception If validation fails
     */
    private function validateEventDates($startDateTime, $endDateTime) {
        try {
            $start = new DateTime($startDateTime);
            $end = new DateTime($endDateTime);
            
            if ($end < $start) {
                throw new Exception('End date and time must be after start date and time');
            }
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'End date') === 0) {
                throw $e;
            }
            throw new Exception('Invalid date format');
        }
    }
    
    /**
     * Check if a string is a valid datetime
     * 
     * @param string $dateTime Date time string
     * @return bool True if valid, false otherwise
     */
    private function isValidDateTime($dateTime) {
        try {
            new DateTime($dateTime);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get total event count
     * 
     * @return int Total number of events
     */
    public function getEventCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM episode");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting event count: " . $e->getMessage());
            return 0;
        }
    }
}
?>