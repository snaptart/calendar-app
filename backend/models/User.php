<?php
/**
 * Improved User Model Class with Better Statistics
 * Location: backend/models/User.php
 * 
 * Enhanced version that ensures accurate data retrieval and formatting
 */

class User {
    private $pdo;
    private $calendarUpdate;
    
    public function __construct($pdo, $calendarUpdate = null) {
        $this->pdo = $pdo;
        $this->calendarUpdate = $calendarUpdate;
    }
    
    /**
     * Get all users
     * 
     * @return array Array of users
     */
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name, email, color, created_at, last_login 
                FROM users 
                ORDER BY name ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            throw new Exception("Failed to fetch users");
        }
    }
    
    /**
     * Get all users with comprehensive event statistics
     * 
     * @return array Array of users with detailed stats
     */
    public function getAllUsersWithStats() {
        try {
            // First get all users
            $stmt = $this->pdo->query("
                SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.color,
                    u.created_at,
                    u.last_login,
                    u.password_hash
                FROM users u
                ORDER BY u.name ASC
            ");
            
            $users = $stmt->fetchAll();
            
            // Then get event counts for each user
            $eventCountsQuery = $this->pdo->query("
                SELECT 
                    user_id,
                    COUNT(*) as event_count,
                    COUNT(CASE WHEN start_datetime >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN start_datetime < NOW() THEN 1 END) as past_events,
                    MIN(start_datetime) as first_event_date,
                    MAX(start_datetime) as last_event_date
                FROM events 
                GROUP BY user_id
            ");
            
            $eventCounts = [];
            while ($row = $eventCountsQuery->fetch()) {
                $eventCounts[$row['user_id']] = $row;
            }
            
            // Format the data for frontend consumption
            return array_map(function($user) use ($eventCounts) {
                $userId = (int)$user['id'];
                $eventStats = $eventCounts[$userId] ?? null;
                
                return [
                    'id' => $userId,
                    'name' => $user['name'] ?: 'Unknown User',
                    'email' => $user['email'] ?: null,
                    'color' => $user['color'] ?: '#3498db',
                    'created_at' => $user['created_at'],
                    'last_login' => $user['last_login'],
                    'event_count' => $eventStats ? (int)$eventStats['event_count'] : 0,
                    'upcoming_events' => $eventStats ? (int)$eventStats['upcoming_events'] : 0,
                    'past_events' => $eventStats ? (int)$eventStats['past_events'] : 0,
                    'first_event_date' => $eventStats ? $eventStats['first_event_date'] : null,
                    'last_event_date' => $eventStats ? $eventStats['last_event_date'] : null,
                    'status' => $this->determineUserStatus($user['last_login'], $user['password_hash']),
                    'has_password' => !empty($user['password_hash']),
                    'member_since' => $this->formatMemberSince($user['created_at'])
                ];
            }, $users);
            
        } catch (PDOException $e) {
            error_log("Error fetching users with stats: " . $e->getMessage());
            throw new Exception("Failed to fetch users with statistics");
        }
    }
    
    /**
     * Get detailed user information by ID
     * 
     * @param int $userId User ID
     * @return array|null User data with stats or null if not found
     */
    public function getUserWithStats($userId) {
        try {
            // Get user info
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, color, created_at, last_login, password_hash
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return null;
            }
            
            // Get event statistics
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN start_datetime >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN start_datetime < NOW() THEN 1 END) as past_events,
                    MIN(start_datetime) as first_event,
                    MAX(start_datetime) as latest_event,
                    AVG(TIMESTAMPDIFF(MINUTE, start_datetime, end_datetime)) as avg_event_duration
                FROM events 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
            
            // Get recent events
            $stmt = $this->pdo->prepare("
                SELECT id, title, start_datetime, end_datetime
                FROM events 
                WHERE user_id = ? 
                ORDER BY start_datetime DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $recentEvents = $stmt->fetchAll();
            
            return [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'color' => $user['color'],
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login'],
                'status' => $this->determineUserStatus($user['last_login'], $user['password_hash']),
                'has_password' => !empty($user['password_hash']),
                'statistics' => [
                    'total_events' => (int)$stats['total_events'],
                    'upcoming_events' => (int)$stats['upcoming_events'],
                    'past_events' => (int)$stats['past_events'],
                    'first_event' => $stats['first_event'],
                    'latest_event' => $stats['latest_event'],
                    'avg_event_duration' => $stats['avg_event_duration'] ? round($stats['avg_event_duration']) : 0
                ],
                'recent_events' => $recentEvents
            ];
            
        } catch (PDOException $e) {
            error_log("Error fetching user with stats: " . $e->getMessage());
            throw new Exception("Failed to fetch user details");
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, color, created_at, last_login 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            throw new Exception("Failed to fetch user");
        }
    }
    
    /**
     * Get user by name
     * 
     * @param string $name User name
     * @return array|null User data or null if not found
     */
    public function getUserByName($name) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, color, created_at, last_login 
                FROM users 
                WHERE name = ?
            ");
            $stmt->execute([$name]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user by name: " . $e->getMessage());
            throw new Exception("Failed to fetch user");
        }
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
            throw new Exception("Failed to fetch user");
        }
    }
    
    /**
     * Create a new user
     * 
     * @param string $name User name
     * @param string $email User email (optional)
     * @param string $passwordHash Password hash (optional)
     * @param string $color User color (optional)
     * @return array Created user data
     */
    public function createUser($name, $email = null, $passwordHash = null, $color = null) {
        try {
            // Validate name
            if (empty($name) || strlen(trim($name)) < 2) {
                throw new Exception('Name must be at least 2 characters long');
            }
            
            $name = trim($name);
            
            // Check for potentially harmful characters
            if (preg_match('/[<>"\']/', $name)) {
                throw new Exception('Name contains invalid characters');
            }
            
            // Check if name already exists
            if ($this->getUserByName($name)) {
                throw new Exception('This name is already taken. Please choose a different name.');
            }
            
            // Check if email already exists (if provided)
            if ($email && $this->getUserByEmail($email)) {
                throw new Exception('An account with this email already exists');
            }
            
            // Generate random color if not provided
            if (!$color) {
                $colors = ['#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22', '#3498db'];
                $color = $colors[array_rand($colors)];
            }
            
            // Insert new user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, color) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $passwordHash, $color]);
            
            $userId = $this->pdo->lastInsertId();
            
            return [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'color' => $color,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            
            // Check for duplicate key errors
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception('An account with this email already exists');
                } elseif (strpos($e->getMessage(), 'name') !== false) {
                    throw new Exception('This name is already taken');
                }
            }
            
            throw new Exception('Failed to create user');
        }
    }
    
    /**
     * Update user information
     * 
     * @param int $userId User ID
     * @param array $data Data to update
     * @return array Updated user data
     */
    public function updateUser($userId, $data) {
        try {
            $allowedFields = ['name', 'email', 'color'];
            $updateFields = [];
            $updateValues = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }
            
            $updateValues[] = $userId;
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET " . implode(', ', $updateFields) . " 
                WHERE id = ?
            ");
            $stmt->execute($updateValues);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('User not found or no changes made');
            }
            
            return $this->getUserById($userId);
            
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            throw new Exception('Failed to update user');
        }
    }
    
    /**
     * Update user's last login timestamp
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search users by name or email
     * 
     * @param string $query Search query
     * @param int $limit Maximum results to return
     * @return array Array of matching users
     */
    public function searchUsers($query, $limit = 10) {
        try {
            $searchTerm = "%{$query}%";
            
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, color, created_at 
                FROM users 
                WHERE name LIKE ? OR email LIKE ?
                ORDER BY name 
                LIMIT ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching users: " . $e->getMessage());
            throw new Exception('Failed to search users');
        }
    }
    
    /**
     * Get total user count
     * 
     * @return int Total number of users
     */
    public function getUserCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user activity summary
     * 
     * @param int $days Number of days to look back
     * @return array Activity summary
     */
    public function getUserActivity($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(DISTINCT CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN u.id END) as active_users,
                    COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN u.id END) as new_users,
                    COUNT(DISTINCT CASE WHEN e.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN e.user_id END) as users_with_events
                FROM users u
                LEFT JOIN events e ON u.id = e.user_id
            ");
            $stmt->execute([$days, $days, $days]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Determine user status based on last login and account type
     * 
     * @param string|null $lastLogin Last login timestamp
     * @param string|null $passwordHash Whether user has a password (registered vs guest)
     * @return string User status (active, inactive, new, guest)
     */
    private function determineUserStatus($lastLogin, $passwordHash = null) {
        // If no password hash, it's a guest user (legacy users without registration)
        if (empty($passwordHash)) {
            return 'guest';
        }
        
        // If never logged in, it's a new registered user
        if (!$lastLogin || $lastLogin === '0000-00-00 00:00:00') {
            return 'new';
        }
        
        try {
            $lastLoginDate = new DateTime($lastLogin);
            $now = new DateTime();
            $daysDiff = $now->diff($lastLoginDate)->days;
            
            if ($daysDiff <= 1) {
                return 'active';
            } else if ($daysDiff <= 7) {
                return 'recent';
            } else {
                return 'inactive';
            }
        } catch (Exception $e) {
            return 'unknown';
        }
    }
    
    /**
     * Format member since date in a human-readable way
     * 
     * @param string $createdAt Created timestamp
     * @return string Formatted member since date
     */
    private function formatMemberSince($createdAt) {
        if (!$createdAt) {
            return 'Unknown';
        }
        
        try {
            $created = new DateTime($createdAt);
            return $created->format('M j, Y');
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check if user exists by name
     * 
     * @param string $name User name
     * @return bool True if exists, false otherwise
     */
    public function userExistsByName($name) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking user existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user exists by email
     * 
     * @param string $email User email
     * @return bool True if exists, false otherwise
     */
    public function userExistsByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking user existence by email: " . $e->getMessage());
            return false;
        }
    }
}
?>