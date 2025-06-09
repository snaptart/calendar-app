<?php
/**
 * User Model Class for itmdev
 * Location: backend/models/User.php
 * 
 * Updated to work with the itmdev database schema
 * FIXED: Line 27 - Changed $pdo to $this->pdo
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
                SELECT 
                    u.user_ID as id, 
                    u.user_Name as name, 
                    u.user_Email as email, 
                    '#3498db' as color,  -- Default color for compatibility
                    u.create_ts as created_at, 
                    u.user_Last_Login as last_login,
                    r.role_Name as role_name
                FROM user u
                LEFT JOIN role r ON u.role_ID = r.role_ID
                WHERE u.user_Status = 'Active'
                ORDER BY u.user_Name ASC
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
                    u.user_ID,
                    u.user_Name,
                    u.user_Email,
                    u.create_ts,
                    u.user_Last_Login,
                    u.password,
                    r.role_Name
                FROM user u
                LEFT JOIN role r ON u.role_ID = r.role_ID
                WHERE u.user_Status = 'Active'
                ORDER BY u.user_Name ASC
            ");
            
            $users = $stmt->fetchAll();
            
            // Then get event counts for each user from episode table
            $eventCountsQuery = $this->pdo->query("
                SELECT 
                    user_ID,
                    COUNT(*) as event_count,
                    COUNT(CASE WHEN episode_Start_Date_Time >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN episode_Start_Date_Time < NOW() THEN 1 END) as past_events,
                    MIN(episode_Start_Date_Time) as first_event_date,
                    MAX(episode_Start_Date_Time) as last_event_date
                FROM episode 
                WHERE user_ID IS NOT NULL
                GROUP BY user_ID
            ");
            
            $eventCounts = [];
            while ($row = $eventCountsQuery->fetch()) {
                $eventCounts[$row['user_ID']] = $row;
            }
            
            // Format the data for frontend consumption
            return array_map(function($user) use ($eventCounts) {
                $userId = (int)$user['user_ID'];
                $eventStats = $eventCounts[$userId] ?? null;
                
                return [
                    'id' => $userId,
                    'name' => $user['user_Name'] ?: 'Unknown User',
                    'email' => $user['user_Email'] ?: null,
                    'color' => '#3498db', // Default color for compatibility
                    'created_at' => $user['create_ts'],
                    'last_login' => $user['user_Last_Login'],
                    'event_count' => $eventStats ? (int)$eventStats['event_count'] : 0,
                    'upcoming_events' => $eventStats ? (int)$eventStats['upcoming_events'] : 0,
                    'past_events' => $eventStats ? (int)$eventStats['past_events'] : 0,
                    'first_event_date' => $eventStats ? $eventStats['first_event_date'] : null,
                    'last_event_date' => $eventStats ? $eventStats['last_event_date'] : null,
                    'status' => $this->determineUserStatus($user['user_Last_Login'], $user['password']),
                    'has_password' => !empty($user['password']),
                    'member_since' => $this->formatMemberSince($user['create_ts']),
                    'role_name' => $user['role_Name'] ?? 'Unknown'
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
                SELECT u.user_ID, u.user_Name, u.user_Email, u.create_ts, 
                       u.user_Last_Login, u.password, r.role_Name
                FROM user u
                LEFT JOIN role r ON u.role_ID = r.role_ID
                WHERE u.user_ID = ? AND u.user_Status = 'Active'
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return null;
            }
            
            // Get event statistics from episode table
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN episode_Start_Date_Time >= NOW() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN episode_Start_Date_Time < NOW() THEN 1 END) as past_events,
                    MIN(episode_Start_Date_Time) as first_event,
                    MAX(episode_Start_Date_Time) as latest_event,
                    AVG(episode_Duration) as avg_event_duration
                FROM episode 
                WHERE user_ID = ?
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
            
            // Get recent events
            $stmt = $this->pdo->prepare("
                SELECT e.episode_ID as id, e.episode_Title as title, 
                       e.episode_Start_Date_Time as start_datetime, 
                       e.episode_End_Date_Time as end_datetime
                FROM episode e
                WHERE e.user_ID = ? 
                ORDER BY e.episode_Start_Date_Time DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $recentEvents = $stmt->fetchAll();
            
            return [
                'id' => (int)$user['user_ID'],
                'name' => $user['user_Name'],
                'email' => $user['user_Email'],
                'color' => '#3498db', // Default color
                'created_at' => $user['create_ts'],
                'last_login' => $user['user_Last_Login'],
                'status' => $this->determineUserStatus($user['user_Last_Login'], $user['password']),
                'has_password' => !empty($user['password']),
                'role_name' => $user['role_Name'],
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
                SELECT u.user_ID as id, u.user_Name as name, u.user_Email as email, 
                       u.create_ts as created_at, u.user_Last_Login as last_login,
                       '#3498db' as color
                FROM user u
                WHERE u.user_ID = ? AND u.user_Status = 'Active'
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
                SELECT u.user_ID as id, u.user_Name as name, u.user_Email as email, 
                       u.create_ts as created_at, u.user_Last_Login as last_login,
                       '#3498db' as color
                FROM user u
                WHERE u.user_Name = ? AND u.user_Status = 'Active'
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
            $stmt = $this->pdo->prepare("
                SELECT u.*, r.role_Name 
                FROM user u
                LEFT JOIN role r ON u.role_ID = r.role_ID
                WHERE u.user_Email = ? AND u.user_Status = 'Active'
            ");
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
     * @param string $color User color (optional, ignored in itmdev)
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
            
            // Get default role ID for Calendar User
            $stmt = $this->pdo->prepare("SELECT role_ID FROM role WHERE role_Name = 'Calendar User'");
            $stmt->execute();
            $role = $stmt->fetch();
            
            if (!$role) {
                // Create default Calendar User role
                $stmt = $this->pdo->prepare("
                    INSERT INTO role (role_Name, role_Desc, role_Level, role_Entity, created_by) 
                    VALUES ('Calendar User', 'Default calendar user role', 10, 'calendar', 'system')
                ");
                $stmt->execute();
                $roleId = $this->pdo->lastInsertId();
            } else {
                $roleId = $role['role_ID'];
            }
            
            // Generate unique email if not provided
            if (!$email) {
                $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
            }
            
            // Insert new user
            $stmt = $this->pdo->prepare("
                INSERT INTO user (role_ID, user_Name, user_Email, password, user_Status, created_by) 
                VALUES (?, ?, ?, ?, 'Active', 'calendar_system')
            ");
            $stmt->execute([$roleId, $name, $email, $passwordHash ?: '']);
            
            $userId = $this->pdo->lastInsertId();
            
            return [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'color' => '#3498db', // Default color for compatibility
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            
            // Check for duplicate key errors
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'user_Email') !== false) {
                    throw new Exception('An account with this email already exists');
                } elseif (strpos($e->getMessage(), 'user_Name') !== false) {
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
            $allowedFields = ['user_Name', 'user_Email'];
            $updateFields = [];
            $updateValues = [];
            
            foreach ($allowedFields as $field) {
                $apiField = ($field === 'user_Name') ? 'name' : 'email';
                if (isset($data[$apiField])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$apiField];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }
            
            $updateFields[] = "updated_by = ?";
            $updateValues[] = 'calendar_system';
            $updateValues[] = $userId;
            
            $stmt = $this->pdo->prepare("
                UPDATE user 
                SET " . implode(', ', $updateFields) . " 
                WHERE user_ID = ? AND user_Status = 'Active'
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
            $stmt = $this->pdo->prepare("UPDATE user SET user_Last_Login = NOW() WHERE user_ID = ?");
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
                SELECT u.user_ID as id, u.user_Name as name, u.user_Email as email, 
                       u.create_ts as created_at, '#3498db' as color
                FROM user u
                WHERE (u.user_Name LIKE ? OR u.user_Email LIKE ?) 
                  AND u.user_Status = 'Active'
                ORDER BY u.user_Name 
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
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM user WHERE user_Status = 'Active'");
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
                    COUNT(DISTINCT u.user_ID) as total_users,
                    COUNT(DISTINCT CASE WHEN u.user_Last_Login >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN u.user_ID END) as active_users,
                    COUNT(DISTINCT CASE WHEN u.create_ts >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN u.user_ID END) as new_users,
                    COUNT(DISTINCT CASE WHEN e.create_ts >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN e.user_ID END) as users_with_events
                FROM user u
                LEFT JOIN episode e ON u.user_ID = e.user_ID
                WHERE u.user_Status = 'Active'
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
     * @param string|null $password Whether user has a password (registered vs guest)
     * @return string User status (active, inactive, new, guest)
     */
    private function determineUserStatus($lastLogin, $password = null) {
        // If no password, it's a guest user (legacy users without registration)
        if (empty($password)) {
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user WHERE user_Name = ? AND user_Status = 'Active'");
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user WHERE user_Email = ? AND user_Status = 'Active'");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking user existence by email: " . $e->getMessage());
            return false;
        }
    }
}
?>