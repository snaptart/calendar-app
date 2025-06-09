<?php
/**
 * UserTable Component - Specialized DataTable for user management
 * Location: frontend/components/tables/UserTable.php
 * 
 * Pre-configured DataTable specifically for displaying user data
 */

require_once __DIR__ . '/DataTable.php';

class UserTable extends DataTable {
    
    /**
     * Constructor with user-specific defaults
     */
    public function __construct($config = []) {
        // User table specific configuration
        $userConfig = [
            'tableId' => 'usersTable',
            'order' => [[4, 'desc']], // Sort by Member Since (newest first)
            'pageLength' => 25,
            'language' => [
                'search' => 'Search users:',
                'lengthMenu' => 'Show _MENU_ users per page',
                'info' => 'Showing _START_ to _END_ of _TOTAL_ users',
                'infoEmpty' => 'No users found',
                'infoFiltered' => '(filtered from _MAX_ total users)',
                'emptyTable' => 'No users found in the system'
            ],
            'buttons' => [
                'csv' => [
                    'extend' => 'csv',
                    'text' => '📄 Export CSV',
                    'filename' => 'users_export',
                    'enabled' => true,
                    'exportOptions' => [
                        'columns' => [1, 2, 3, 4, 5, 6] // Exclude color column
                    ]
                ],
                'excel' => [
                    'extend' => 'excel',
                    'text' => '📊 Export Excel',
                    'filename' => 'users_export',
                    'enabled' => true,
                    'exportOptions' => [
                        'columns' => [1, 2, 3, 4, 5, 6]
                    ]
                ],
                'pdf' => [
                    'extend' => 'pdf',
                    'text' => '📑 Export PDF',
                    'filename' => 'users_export',
                    'enabled' => true,
                    'exportOptions' => [
                        'columns' => [1, 2, 3, 4, 5, 6]
                    ]
                ],
                'print' => [
                    'extend' => 'print',
                    'text' => '🖨️ Print',
                    'enabled' => true,
                    'exportOptions' => [
                        'columns' => [1, 2, 3, 4, 5, 6]
                    ]
                ]
            ]
        ];
        
        // Merge with provided config
        $mergedConfig = array_merge_recursive($userConfig, $config);
        
        parent::__construct($mergedConfig);
        
        // Set up user-specific columns
        $this->setupUserColumns();
    }
    
    /**
     * Set up default user table columns
     */
    private function setupUserColumns() {
        $this->addColumns([
            [
                'data' => 'color',
                'name' => 'color',
                'title' => 'Color',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '60px',
                'render' => [$this, 'renderColorColumn']
            ],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => 'Name',
                'orderable' => true,
                'searchable' => true,
                'width' => '25%'
            ],
            [
                'data' => 'email',
                'name' => 'email',
                'title' => 'Email',
                'orderable' => true,
                'searchable' => true,
                'width' => '25%',
                'render' => [$this, 'renderEmailColumn']
            ],
            [
                'data' => 'event_count',
                'name' => 'event_count',
                'title' => 'Events Created',
                'orderable' => true,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '120px',
                'type' => 'number',
                'render' => [$this, 'renderEventCountColumn']
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'Member Since',
                'orderable' => true,
                'searchable' => false,
                'width' => '18%',
                'type' => 'date',
                'render' => [$this, 'renderDateColumn']
            ],
            [
                'data' => 'last_login',
                'name' => 'last_login',
                'title' => 'Last Login',
                'orderable' => true,
                'searchable' => false,
                'width' => '15%',
                'type' => 'datetime',
                'render' => [$this, 'renderLastLoginColumn']
            ],
            [
                'data' => 'status',
                'name' => 'status',
                'title' => 'Status',
                'orderable' => true,
                'searchable' => true,
                'className' => 'text-center',
                'width' => '100px',
                'render' => [$this, 'renderStatusColumn']
            ]
        ]);
    }
    
    /**
     * Render color indicator column
     */
    public function renderColorColumn($value, $column) {
        $safeColor = htmlspecialchars($value ?: '#3498db');
        return '<div class="user-color-indicator" style="background-color: ' . $safeColor . '"></div>';
    }
    
    /**
     * Render email column with privacy protection
     */
    public function renderEmailColumn($value, $column) {
        if (!$value || $value === 'No email') {
            return '<span class="text-muted">No email</span>';
        }
        
        return htmlspecialchars($value);
    }
    
    /**
     * Render event count with badge styling
     */
    public function renderEventCountColumn($value, $column) {
        $count = intval($value ?: 0);
        $badgeClass = 'event-count';
        
        if ($count === 0) {
            $badgeClass .= ' zero';
        } elseif ($count >= 10) {
            $badgeClass .= ' high';
        }
        
        return '<span class="' . $badgeClass . '">' . $count . '</span>';
    }
    
    /**
     * Render date column
     */
    public function renderDateColumn($value, $column) {
        if (!$value || $value === '0000-00-00 00:00:00' || $value === null) {
            return '<span class="text-muted">Never</span>';
        }
        
        try {
            $date = new DateTime($value);
            return '<span title="' . $date->format('Y-m-d H:i:s') . '">' . 
                   $date->format('M j, Y') . '</span>';
        } catch (Exception $e) {
            return '<span class="text-muted">Invalid Date</span>';
        }
    }
    
    /**
     * Render last login with relative time
     */
    public function renderLastLoginColumn($value, $column) {
        if (!$value || $value === '0000-00-00 00:00:00' || $value === null) {
            return '<span class="text-muted">Never</span>';
        }
        
        try {
            $date = new DateTime($value);
            $now = new DateTime();
            $diff = $now->diff($date);
            
            $relativeTime = $this->getRelativeTime($diff);
            $fullDate = $date->format('M j, Y g:i A');
            
            return '<div class="datetime-cell">' .
                   '<div class="datetime-main">' . $date->format('M j, Y') . '</div>' .
                   '<div class="datetime-time">' . $date->format('g:i A') . '</div>' .
                   '<div class="datetime-relative">' . $relativeTime . '</div>' .
                   '</div>';
        } catch (Exception $e) {
            return '<span class="text-muted">Invalid Date</span>';
        }
    }
    
    /**
     * Render status badge
     */
    public function renderStatusColumn($value, $column) {
        $statusMap = [
            'active' => ['class' => 'active', 'text' => 'Active', 'icon' => '🟢'],
            'recent' => ['class' => 'recent', 'text' => 'Recent', 'icon' => '🟡'],
            'inactive' => ['class' => 'inactive', 'text' => 'Inactive', 'icon' => '🔴'],
            'new' => ['class' => 'new', 'text' => 'New', 'icon' => '🆕'],
            'guest' => ['class' => 'guest', 'text' => 'Guest', 'icon' => '👤'],
            'unknown' => ['class' => 'unknown', 'text' => 'Unknown', 'icon' => '❓']
        ];
        
        $status = $value ?: 'unknown';
        $statusInfo = $statusMap[$status] ?? $statusMap['unknown'];
        
        return '<span class="status-badge ' . $statusInfo['class'] . '">' . 
               $statusInfo['icon'] . ' ' . $statusInfo['text'] . '</span>';
    }
    
    /**
     * Get relative time string from DateInterval
     */
    private function getRelativeTime($diff) {
        if ($diff->days === 0) {
            if ($diff->h === 0) {
                return $diff->i <= 1 ? 'Just now' : $diff->i . ' min ago';
            }
            return $diff->h === 1 ? '1 hour ago' : $diff->h . ' hours ago';
        } elseif ($diff->days === 1) {
            return 'Yesterday';
        } elseif ($diff->days < 7) {
            return $diff->days . ' days ago';
        } elseif ($diff->days < 30) {
            $weeks = floor($diff->days / 7);
            return $weeks === 1 ? '1 week ago' : $weeks . ' weeks ago';
        } elseif ($diff->days < 365) {
            $months = floor($diff->days / 30);
            return $months === 1 ? '1 month ago' : $months . ' months ago';
        } else {
            $years = floor($diff->days / 365);
            return $years === 1 ? '1 year ago' : $years . ' years ago';
        }
    }
    
    /**
     * Set user data with validation and processing
     */
    public function setUserData($users) {
        if (!is_array($users)) {
            $users = [];
        }
        
        // Process and validate user data
        $processedUsers = array_map([$this, 'processUserData'], $users);
        
        return $this->setData($processedUsers);
    }
    
    /**
     * Process individual user data
     */
    private function processUserData($user) {
        // Ensure required fields exist with defaults
        return [
            'id' => $user['id'] ?? 0,
            'name' => $user['name'] ?? 'Unknown User',
            'email' => $user['email'] ?? null,
            'color' => $user['color'] ?? '#3498db',
            'created_at' => $user['created_at'] ?? null,
            'last_login' => $user['last_login'] ?? null,
            'event_count' => intval($user['event_count'] ?? 0),
            'upcoming_events' => intval($user['upcoming_events'] ?? 0),
            'past_events' => intval($user['past_events'] ?? 0),
            'status' => $this->calculateUserStatus($user),
            'has_password' => $user['has_password'] ?? false
        ];
    }
    
    /**
     * Calculate user status based on activity
     */
    private function calculateUserStatus($user) {
        // Return existing status if provided
        if (isset($user['status']) && $user['status']) {
            return $user['status'];
        }
        
        $now = new DateTime();
        
        // Check if user has password (registered vs guest)
        if (isset($user['has_password']) && !$user['has_password']) {
            return 'guest';
        }
        
        $lastLogin = $user['last_login'] ?? null;
        if (!$lastLogin || $lastLogin === '0000-00-00 00:00:00') {
            return 'new';
        }
        
        try {
            $lastLoginDate = new DateTime($lastLogin);
            $diffDays = $now->diff($lastLoginDate)->days;
            
            if ($diffDays <= 1) {
                return 'active';
            } elseif ($diffDays <= 7) {
                return 'recent';
            } else {
                return 'inactive';
            }
        } catch (Exception $e) {
            return 'unknown';
        }
    }
    
    /**
     * Add custom CSS for user table styling
     */
    public function renderWithStyles() {
        ?>
        <style>
        .user-color-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin: 0 auto;
        }
        
        .event-count {
            background: #4299e1;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
            min-width: 28px;
            display: inline-block;
            border: 1px solid #3182ce;
        }
        
        .event-count.zero {
            background: #a0aec0;
            border-color: #718096;
        }
        
        .event-count.high {
            background: #38a169;
            border-color: #2f855a;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }
        
        .status-badge.active {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .status-badge.recent {
            background: #bee3f8;
            color: #2a4365;
            border: 1px solid #90cdf4;
        }
        
        .status-badge.inactive {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .status-badge.new {
            background: #e6fffa;
            color: #234e52;
            border: 1px solid #81e6d9;
        }
        
        .status-badge.guest {
            background: #faf5ff;
            color: #553c9a;
            border: 1px solid #d6bcfa;
        }
        
        .status-badge.unknown {
            background: #f7fafc;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        
        .datetime-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
            line-height: 1.2;
        }
        
        .datetime-main {
            font-weight: 500;
            color: #2d3748;
            font-size: 0.875rem;
        }
        
        .datetime-time {
            font-size: 0.75rem;
            color: #4a5568;
        }
        
        .datetime-relative {
            font-size: 0.625rem;
            color: #718096;
            font-style: italic;
        }
        </style>
        <?php
        
        $this->render();
    }
    
    /**
     * Create user table with default configuration
     */
    public static function createUserTable($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method for user tables
     */
    public static function renderUserTable($users = [], $config = []) {
        $table = new self($config);
        $table->setUserData($users);
        $table->renderWithStyles();
    }
}