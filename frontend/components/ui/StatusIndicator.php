<?php
/**
 * StatusIndicator Component - Reusable status display with real-time updates
 * Location: frontend/components/ui/StatusIndicator.php
 * 
 * Displays connection status, user status, and system health indicators
 */

class StatusIndicator {
    private $config;
    private $statusId;
    private $currentStatus;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'statusId' => 'statusIndicator',
        'type' => 'connection', // connection, user, system, custom
        'size' => 'medium', // small, medium, large
        'showText' => true,
        'showIcon' => true,
        'showDot' => true,
        'animated' => true,
        'autoUpdate' => false,
        'updateInterval' => 5000, // 5 seconds
        'realTime' => false, // Use SSE for real-time updates
        'classes' => [
            'container' => 'status-indicator',
            'dot' => 'status-dot',
            'icon' => 'status-icon',
            'text' => 'status-text',
            'connected' => 'connected',
            'disconnected' => 'disconnected',
            'warning' => 'warning',
            'error' => 'error',
            'loading' => 'loading'
        ],
        'statuses' => [
            'connected' => [
                'text' => 'Connected',
                'icon' => '🟢',
                'color' => '#48bb78',
                'class' => 'connected'
            ],
            'disconnected' => [
                'text' => 'Disconnected',
                'icon' => '🔴',
                'color' => '#f56565',
                'class' => 'disconnected'
            ],
            'connecting' => [
                'text' => 'Connecting...',
                'icon' => '🟡',
                'color' => '#ed8936',
                'class' => 'loading'
            ],
            'warning' => [
                'text' => 'Warning',
                'icon' => '⚠️',
                'color' => '#f6e05e',
                'class' => 'warning'
            ],
            'error' => [
                'text' => 'Error',
                'icon' => '❌',
                'color' => '#f56565',
                'class' => 'error'
            ],
            'loading' => [
                'text' => 'Loading...',
                'icon' => '⏳',
                'color' => '#4299e1',
                'class' => 'loading'
            ],
            'ready' => [
                'text' => 'Ready',
                'icon' => '✅',
                'color' => '#48bb78',
                'class' => 'connected'
            ],
            'initializing' => [
                'text' => 'Initializing...',
                'icon' => '🔄',
                'color' => '#4299e1',
                'class' => 'loading'
            ]
        ],
        'userStatuses' => [
            'authenticated' => [
                'text' => 'Logged In',
                'icon' => '👤',
                'color' => '#48bb78',
                'class' => 'connected'
            ],
            'unauthenticated' => [
                'text' => 'Not Logged In',
                'icon' => '🚫',
                'color' => '#f56565',
                'class' => 'disconnected'
            ],
            'checking' => [
                'text' => 'Checking...',
                'icon' => '⏳',
                'color' => '#4299e1',
                'class' => 'loading'
            ]
        ],
        'api' => [
            'checkUrl' => null,
            'checkInterval' => 30000, // 30 seconds
            'timeout' => 5000
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->statusId = $this->config['statusId'];
        $this->currentStatus = 'initializing';
    }
    
    /**
     * Set status type
     */
    public function setType($type) {
        $this->config['type'] = $type;
        return $this;
    }
    
    /**
     * Set current status
     */
    public function setStatus($status, $customText = null) {
        $this->currentStatus = $status;
        if ($customText) {
            $this->config['statuses'][$status]['text'] = $customText;
        }
        return $this;
    }
    
    /**
     * Add custom status
     */
    public function addStatus($key, $config) {
        $defaultStatus = [
            'text' => ucfirst($key),
            'icon' => '●',
            'color' => '#718096',
            'class' => 'custom'
        ];
        
        $this->config['statuses'][$key] = array_merge($defaultStatus, $config);
        return $this;
    }
    
    /**
     * Enable auto-update
     */
    public function enableAutoUpdate($interval = 5000) {
        $this->config['autoUpdate'] = true;
        $this->config['updateInterval'] = $interval;
        return $this;
    }
    
    /**
     * Set API check URL
     */
    public function setCheckUrl($url) {
        $this->config['api']['checkUrl'] = $url;
        return $this;
    }
    
    /**
     * Render the status indicator
     */
    public function render() {
        $statusConfig = $this->getStatusConfig($this->currentStatus);
        
        ?>
        <div id="<?php echo htmlspecialchars($this->statusId); ?>" 
             class="<?php echo htmlspecialchars($this->getContainerClasses()); ?>"
             data-status="<?php echo htmlspecialchars($this->currentStatus); ?>"
             data-type="<?php echo htmlspecialchars($this->config['type']); ?>">
            
            <?php if ($this->config['showDot']): ?>
                <span class="<?php echo htmlspecialchars($this->config['classes']['dot']); ?>"
                      style="background-color: <?php echo htmlspecialchars($statusConfig['color']); ?>"></span>
            <?php endif; ?>
            
            <?php if ($this->config['showIcon']): ?>
                <span class="<?php echo htmlspecialchars($this->config['classes']['icon']); ?>">
                    <?php echo $statusConfig['icon']; ?>
                </span>
            <?php endif; ?>
            
            <?php if ($this->config['showText']): ?>
                <span class="<?php echo htmlspecialchars($this->config['classes']['text']); ?>">
                    <?php echo htmlspecialchars($statusConfig['text']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateStatusJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Get status configuration
     */
    private function getStatusConfig($status) {
        // Check user statuses for user type
        if ($this->config['type'] === 'user' && isset($this->config['userStatuses'][$status])) {
            return $this->config['userStatuses'][$status];
        }
        
        // Check regular statuses
        if (isset($this->config['statuses'][$status])) {
            return $this->config['statuses'][$status];
        }
        
        // Return default status
        return [
            'text' => ucfirst($status),
            'icon' => '●',
            'color' => '#718096',
            'class' => 'custom'
        ];
    }
    
    /**
     * Get container CSS classes
     */
    private function getContainerClasses() {
        $classes = [$this->config['classes']['container']];
        
        // Add size class
        $classes[] = 'size-' . $this->config['size'];
        
        // Add type class
        $classes[] = 'type-' . $this->config['type'];
        
        // Add status class
        $statusConfig = $this->getStatusConfig($this->currentStatus);
        $classes[] = $statusConfig['class'];
        
        // Add animated class
        if ($this->config['animated']) {
            $classes[] = 'animated';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Generate status indicator JavaScript
     */
    private function generateStatusJs() {
        $statusId = $this->statusId;
        $config = json_encode($this->config);
        
        return "
        // Initialize StatusIndicator: {$statusId}
        (function() {
            const statusEl = document.getElementById('{$statusId}');
            const config = {$config};
            
            if (!statusEl) return;
            
            let currentStatus = statusEl.dataset.status;
            let updateTimer = null;
            let checkTimer = null;
            
            // Update status display
            function updateStatus(status, customText) {
                if (status === currentStatus && !customText) return;
                
                currentStatus = status;
                statusEl.dataset.status = status;
                
                const statusConfig = getStatusConfig(status);
                
                // Update dot color
                const dotEl = statusEl.querySelector('.' + config.classes.dot);
                if (dotEl) {
                    dotEl.style.backgroundColor = statusConfig.color;
                }
                
                // Update icon
                const iconEl = statusEl.querySelector('.' + config.classes.icon);
                if (iconEl) {
                    iconEl.textContent = statusConfig.icon;
                }
                
                // Update text
                const textEl = statusEl.querySelector('.' + config.classes.text);
                if (textEl) {
                    textEl.textContent = customText || statusConfig.text;
                }
                
                // Update container class
                updateContainerClass(statusConfig.class);
                
                // Emit status change event
                const changeEvent = new CustomEvent('statusChange', {
                    detail: { 
                        statusId: '{$statusId}',
                        status: status,
                        config: statusConfig 
                    }
                });
                document.dispatchEvent(changeEvent);
            }
            
            function getStatusConfig(status) {
                // Check user statuses for user type
                if (config.type === 'user' && config.userStatuses[status]) {
                    return config.userStatuses[status];
                }
                
                // Check regular statuses
                if (config.statuses[status]) {
                    return config.statuses[status];
                }
                
                // Return default
                return {
                    text: status.charAt(0).toUpperCase() + status.slice(1),
                    icon: '●',
                    color: '#718096',
                    class: 'custom'
                };
            }
            
            function updateContainerClass(statusClass) {
                // Remove old status classes
                const statusClasses = Object.values(config.statuses).map(s => s.class);
                if (config.type === 'user') {
                    statusClasses.push(...Object.values(config.userStatuses).map(s => s.class));
                }
                
                statusClasses.forEach(cls => {
                    statusEl.classList.remove(cls);
                });
                
                // Add new status class
                statusEl.classList.add(statusClass);
            }
            
            // Auto-update functionality
            function startAutoUpdate() {
                if (!config.autoUpdate || updateTimer) return;
                
                updateTimer = setInterval(() => {
                    // Emit update request event
                    const updateEvent = new CustomEvent('statusUpdateRequest', {
                        detail: { statusId: '{$statusId}', type: config.type }
                    });
                    document.dispatchEvent(updateEvent);
                }, config.updateInterval);
            }
            
            function stopAutoUpdate() {
                if (updateTimer) {
                    clearInterval(updateTimer);
                    updateTimer = null;
                }
            }
            
            // API health check
            function startHealthCheck() {
                if (!config.api.checkUrl || checkTimer) return;
                
                // Initial check
                performHealthCheck();
                
                // Periodic checks
                checkTimer = setInterval(performHealthCheck, config.api.checkInterval);
            }
            
            function performHealthCheck() {
                if (!config.api.checkUrl) return;
                
                updateStatus('connecting');
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), config.api.timeout);
                
                fetch(config.api.checkUrl, {
                    method: 'GET',
                    signal: controller.signal,
                    credentials: 'include'
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error('HTTP ' + response.status);
                    }
                })
                .then(data => {
                    if (data.status) {
                        updateStatus(data.status, data.message);
                    } else {
                        updateStatus('connected');
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    
                    if (error.name === 'AbortError') {
                        updateStatus('error', 'Connection timeout');
                    } else {
                        updateStatus('disconnected', error.message);
                    }
                });
            }
            
            // Real-time updates via SSE (if available)
            function setupRealTimeUpdates() {
                if (!config.realTime || typeof EventSource === 'undefined') return;
                
                // Listen for SSE events
                document.addEventListener('sseConnection', function(e) {
                    updateStatus('connected');
                });
                
                document.addEventListener('sseDisconnection', function(e) {
                    updateStatus('disconnected');
                });
                
                document.addEventListener('sseReconnecting', function(e) {
                    updateStatus('connecting', 'Reconnecting...');
                });
            }
            
            // Event listeners
            document.addEventListener('statusUpdate', function(e) {
                if (e.detail.statusId === '{$statusId}' || e.detail.type === config.type) {
                    updateStatus(e.detail.status, e.detail.message);
                }
            });
            
            // Authentication status updates
            if (config.type === 'user') {
                document.addEventListener('authStatusChange', function(e) {
                    if (e.detail.authenticated) {
                        updateStatus('authenticated', 'Logged in as: ' + (e.detail.user ? e.detail.user.name : 'User'));
                    } else {
                        updateStatus('unauthenticated');
                    }
                });
            }
            
            // Connection status updates
            if (config.type === 'connection') {
                // Listen for global connection events
                document.addEventListener('connectionStatusChange', function(e) {
                    updateStatus(e.detail.status, e.detail.message);
                });
                
                // Browser online/offline events
                window.addEventListener('online', function() {
                    updateStatus('connected', 'Back online');
                });
                
                window.addEventListener('offline', function() {
                    updateStatus('disconnected', 'No internet connection');
                });
            }
            
            // Initialize
            if (config.autoUpdate) {
                startAutoUpdate();
            }
            
            if (config.api.checkUrl) {
                startHealthCheck();
            }
            
            if (config.realTime) {
                setupRealTimeUpdates();
            }
            
            // Expose status API
            window['{$statusId}'] = {
                update: updateStatus,
                getStatus: function() { return currentStatus; },
                startAutoUpdate: startAutoUpdate,
                stopAutoUpdate: stopAutoUpdate,
                performHealthCheck: performHealthCheck
            };
            
            // Global status update function
            window.updateStatus = function(statusId, status, message) {
                if (statusId === '{$statusId}') {
                    updateStatus(status, message);
                }
            };
            
        })();
        ";
    }
    
    /**
     * Render with default styling
     */
    public function renderWithStyles() {
        ?>
        <style>
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-indicator.size-small {
            padding: 4px 8px;
            font-size: 0.75rem;
            gap: 6px;
        }
        
        .status-indicator.size-large {
            padding: 8px 16px;
            font-size: 1rem;
            gap: 10px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .status-indicator.size-small .status-dot {
            width: 6px;
            height: 6px;
        }
        
        .status-indicator.size-large .status-dot {
            width: 10px;
            height: 10px;
        }
        
        .status-indicator.animated .status-dot {
            animation: pulse 2s infinite;
        }
        
        .status-indicator.loading .status-dot {
            animation: pulse 1s infinite;
        }
        
        .status-indicator.connected {
            background: rgba(72, 187, 120, 0.1);
            border-color: rgba(72, 187, 120, 0.3);
            color: #22543d;
        }
        
        .status-indicator.disconnected {
            background: rgba(245, 101, 101, 0.1);
            border-color: rgba(245, 101, 101, 0.3);
            color: #742a2a;
        }
        
        .status-indicator.warning {
            background: rgba(246, 224, 94, 0.1);
            border-color: rgba(246, 224, 94, 0.3);
            color: #744210;
        }
        
        .status-indicator.error {
            background: rgba(245, 101, 101, 0.1);
            border-color: rgba(245, 101, 101, 0.3);
            color: #742a2a;
        }
        
        .status-indicator.loading {
            background: rgba(66, 153, 225, 0.1);
            border-color: rgba(66, 153, 225, 0.3);
            color: #2a4365;
        }
        
        .status-icon {
            font-size: 1em;
            line-height: 1;
        }
        
        .status-text {
            white-space: nowrap;
            font-weight: 500;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        /* Type-specific styles */
        .status-indicator.type-connection {
            /* Connection-specific styling */
        }
        
        .status-indicator.type-user {
            /* User-specific styling */
        }
        
        .status-indicator.type-system {
            /* System-specific styling */
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .status-indicator {
                background: rgba(0, 0, 0, 0.2);
                border-color: rgba(255, 255, 255, 0.1);
            }
            
            .status-indicator.connected {
                background: rgba(72, 187, 120, 0.2);
                border-color: rgba(72, 187, 120, 0.4);
                color: #68d391;
            }
            
            .status-indicator.disconnected {
                background: rgba(245, 101, 101, 0.2);
                border-color: rgba(245, 101, 101, 0.4);
                color: #fc8181;
            }
            
            .status-indicator.warning {
                background: rgba(246, 224, 94, 0.2);
                border-color: rgba(246, 224, 94, 0.4);
                color: #f6e05e;
            }
            
            .status-indicator.loading {
                background: rgba(66, 153, 225, 0.2);
                border-color: rgba(66, 153, 225, 0.4);
                color: #90cdf4;
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 480px) {
            .status-indicator {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            
            .status-text {
                display: none; /* Hide text on very small screens */
            }
        }
        </style>
        <?php
        
        $this->render();
    }
    
    /**
     * Create a status indicator
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Create a connection status indicator
     */
    public static function createConnectionStatus($statusId = 'connectionStatus', $config = []) {
        $connectionConfig = array_merge([
            'statusId' => $statusId,
            'type' => 'connection',
            'autoUpdate' => true,
            'updateInterval' => 5000,
            'realTime' => true
        ], $config);
        
        return new self($connectionConfig);
    }
    
    /**
     * Create a user status indicator
     */
    public static function createUserStatus($statusId = 'userStatus', $config = []) {
        $userConfig = array_merge([
            'statusId' => $statusId,
            'type' => 'user',
            'autoUpdate' => false,
            'showDot' => false
        ], $config);
        
        return new self($userConfig);
    }
    
    /**
     * Create a system status indicator
     */
    public static function createSystemStatus($statusId = 'systemStatus', $config = []) {
        $systemConfig = array_merge([
            'statusId' => $statusId,
            'type' => 'system',
            'api' => [
                'checkUrl' => '../../backend/api.php?action=health_check',
                'checkInterval' => 30000
            ],
            'autoUpdate' => true
        ], $config);
        
        return new self($systemConfig);
    }
}