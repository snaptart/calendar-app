<?php
/**
 * User Model Class
 * Location: backend/models/User.php
 * 
 * Handles all user-related business logic and database operations
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
            $stmt = $this->pdo->query("SELECT id, name, color, created_at FROM users ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            throw new Exception("Failed to fetch users");
        }
    }
    
    /**
     * Get all users with event statistics
     * 
     * @return array Array of users with stats
     */
    public function getAllUsersWithStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.color,
                    u.created_at,
                    u.last_login,
                    COUNT(e.id) as event_count
                FROM users u
                LEFT JOIN events e ON u.id = e.user_id
                GROUP BY u.id, u.name, u.email, u.color, u.created_at, u.last_login
                ORDER BY u.name ASC
            ");
            
            $users = $stmt->fetchAll();
            
            // Format the data for better frontend consumption
            return array_map(function($user) {
                return [
                    'id' => (int)$user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'color' => $user['color'],
                    'created_at' => $user['created_at'],
                    'last_login' => $user['last_login'],
                    'event_count' => (int)$user['event_count'],
                    'status' => $this->determineUserStatus($user['last_login'])
                ];
            }, $users);
        } catch (PDOException $e) {
            error_log("Error fetching users with stats: " . $e->getMessage());
            throw new Exception("Failed to fetch users with statistics");
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
            $stmt = $this->pdo->prepare("SELECT id, name, email, color, created_at, last_login FROM users WHERE id = ?");
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
            $stmt = $this->pdo->prepare("SELECT id, name, email, color, created_at, last_login FROM users WHERE name = ?");
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
     * Delete a user and all associated data
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUser($userId) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Delete user's events (handled by foreign key cascade)
            // Delete user's sessions (handled by foreign key cascade)
            
            // Delete user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                throw new Exception('User not found');
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            // Broadcast user deletion if CalendarUpdate is available
            if ($this->calendarUpdate) {
                $this->calendarUpdate->broadcastUpdate('user_deleted', ['id' => $userId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error deleting user: " . $e->getMessage());
            throw new Exception('Failed to delete user');
        }
    }
    
    /**
     * Get user statistics
     * 
     * @param int $userId User ID
     * @return array User statistics
     */
    public function getUserStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(e.id) as total_events,
                    COUNT(CASE WHEN e.start_datetime >= CURDATE() THEN 1 END) as upcoming_events,
                    COUNT(CASE WHEN e.start_datetime < CURDATE() THEN 1 END) as past_events,
                    MIN(e.start_datetime) as first_event,
                    MAX(e.start_datetime) as last_event
                FROM events e 
                WHERE e.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user stats: " . $e->getMessage());
            throw new Exception('Failed to fetch user statistics');
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
    
    /**
     * Determine user status based on last login
     * 
     * @param string|null $lastLogin Last login timestamp
     * @return string User status (active, inactive, new)
     */
    private function determineUserStatus($lastLogin) {
        if (!$lastLogin || $lastLogin === '0000-00-00 00:00:00') {
            return 'new';
        }
        
        try {
            $lastLoginDate = new DateTime($lastLogin);
            $now = new DateTime();
            $daysDiff = $now->diff($lastLoginDate)->days;
            
            if ($daysDiff <= 1) {
                return 'active';
            } else {
                return 'inactive';
            }
        } catch (Exception $e) {
            return 'unknown';
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
     * Get user count
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
}
?>