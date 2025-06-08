<?php
/**
 * Event Model Class
 * Location: backend/models/Event.php
 * 
 * Handles all event-related business logic and database operations
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
                    SELECT e.*, u.name as user_name, u.color as user_color 
                    FROM events e 
                    JOIN users u ON e.user_id = u.id 
                    WHERE e.user_id IN ($placeholders)
                    ORDER BY e.start_datetime
                ");
                $stmt->execute($userIds);
            } else {
                $stmt = $this->pdo->query("
                    SELECT e.*, u.name as user_name, u.color as user_color 
                    FROM events e 
                    JOIN users u ON e.user_id = u.id 
                    ORDER BY e.start_datetime
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
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.user_id = ?
            ";
            $params = [$userId];
            
            if ($startDate) {
                $sql .= " AND e.start_datetime >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND e.start_datetime <= ?";
                $params[] = $endDate . ' 23:59:59';
            }
            
            $sql .= " ORDER BY e.start_datetime";
            
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
     * @param int $eventId Event ID
     * @return array|null Event data or null if not found
     */
    public function getEventById($eventId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.id = ?
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
            
            // If no end time provided, use start time
            if (!$endDateTime) {
                $endDateTime = $startDateTime;
            }
            
            // Validate dates
            $this->validateEventDates($startDateTime, $endDateTime);
            
            // Insert event
            $stmt = $this->pdo->prepare("
                INSERT INTO events (user_id, title, start_datetime, end_datetime) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                trim($title),
                $startDateTime,
                $endDateTime
            ]);
            
            $eventId = $this->pdo->lastInsertId();
            
            // Get the created event with user info
            $createdEvent = $this->getEventById($eventId);
            
            // Broadcast update
            if ($this->calendarUpdate && $createdEvent) {
                $this->calendarUpdate->broadcastUpdate('create', $createdEvent);
            }
            
            return $createdEvent;
            
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            throw new Exception("Failed to create event");
        }
    }
    
    /**
     * Update an event
     * 
     * @param int $eventId Event ID
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
            
            if ($existingEvent['user_id'] != $userId) {
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
            
            // Update event
            $stmt = $this->pdo->prepare("
                UPDATE events 
                SET title = ?, start_datetime = ?, end_datetime = ? 
                WHERE id = ?
            ");
            
            $stmt->execute([
                trim($data['title']),
                $data['start'],
                $data['end'] ?? $data['start'],
                $eventId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('No changes made to the event');
            }
            
            // Get updated event
            $updatedEvent = $this->getEventById($eventId);
            
            // Broadcast update
            if ($this->calendarUpdate && $updatedEvent) {
                $this->calendarUpdate->broadcastUpdate('update', $updatedEvent);
            }
            
            return $updatedEvent;
            
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            throw new Exception("Failed to update event");
        }
    }
    
    /**
     * Delete an event
     * 
     * @param int $eventId Event ID
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
            
            if ($existingEvent['user_id'] != $userId) {
                throw new Exception('You can only delete your own events');
            }
            
            // Delete event
            $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            
            // Broadcast update
            if ($this->calendarUpdate) {
                $this->calendarUpdate->broadcastUpdate('delete', ['id' => $eventId]);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error deleting event: " . $e->getMessage());
            throw new Exception("Failed to delete event");
        }
    }
    
    /**
     * Move/reschedule an event
     * 
     * @param int $eventId Event ID
     * @param int $userId User ID (for ownership verification)
     * @param string $newStartDateTime New start date and time
     * @param string $newEndDateTime New end date and time (optional)
     * @return array Updated event data
     */
    public function moveEvent($eventId, $userId, $newStartDateTime, $newEndDateTime = null) {
        try {
            if (!$newEndDateTime) {
                // Calculate duration from existing event
                $existingEvent = $this->getEventRaw($eventId);
                if ($existingEvent) {
                    $start = new DateTime($existingEvent['start_datetime']);
                    $end = new DateTime($existingEvent['end_datetime']);
                    $duration = $end->diff($start);
                    
                    $newStart = new DateTime($newStartDateTime);
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                    $newEndDateTime = $newEnd->format('Y-m-d H:i:s');
                }
            }
            
            return $this->updateEvent($eventId, $userId, [
                'title' => $existingEvent['title'], // Keep existing title
                'start' => $newStartDateTime,
                'end' => $newEndDateTime ?? $newStartDateTime
            ]);
            
        } catch (Exception $e) {
            error_log("Error moving event: " . $e->getMessage());
            throw new Exception("Failed to move event");
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
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.start_datetime >= ? AND e.start_datetime <= ?
            ";
            $params = [$startDate, $endDate . ' 23:59:59'];
            
            if ($userIds && !empty($userIds)) {
                $userIds = array_filter($userIds, 'is_numeric');
                if (!empty($userIds)) {
                    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                    $sql .= " AND e.user_id IN ($placeholders)";
                    $params = array_merge($params, $userIds);
                }
            }
            
            $sql .= " ORDER BY e.start_datetime";
            
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
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.title LIKE ?
            ";
            $params = [$searchTerm];
            
            if ($userId) {
                $sql .= " AND e.user_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY e.start_datetime DESC LIMIT ?";
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
                SELECT e.*, u.name as user_name, u.color as user_color 
                FROM events e 
                JOIN users u ON e.user_id = u.id 
                WHERE e.user_id = ? AND e.start_datetime >= NOW()
                ORDER BY e.start_datetime ASC 
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
                    COUNT(CASE WHEN start_datetime >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN start_datetime < NOW() THEN 1 END) as past_events,
                    MIN(start_datetime) as first_event,
                    MAX(start_datetime) as latest_event
                FROM events 
                WHERE user_id = ?
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
            'id' => (int)$event['id'],
            'title' => $event['title'],
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'backgroundColor' => $event['user_color'],
            'borderColor' => $event['user_color'],
            'extendedProps' => [
                'userId' => (int)$event['user_id'],
                'userName' => $event['user_name']
            ]
        ];
    }
    
    /**
     * Get raw event data (internal use)
     * 
     * @param int $eventId Event ID
     * @return array|null Raw event data
     */
    private function getEventRaw($eventId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching raw event: " . $e->getMessage());
            return null;
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
        
        if (strlen(trim($title)) > 255) {
            throw new Exception('Event title is too long (maximum 255 characters)');
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
            
            // Optional: Check if event is too long (e.g., more than 7 days)
            $diff = $end->diff($start);
            if ($diff->days > 7) {
                error_log("Warning: Long event created - {$diff->days} days");
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
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM events");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting event count: " . $e->getMessage());
            return 0;
        }
    }
}
?>