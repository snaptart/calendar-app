<?php
/**
 * Configuration Service - Centralized configuration management
 * Location: frontend/services/ConfigService.php
 * 
 * Manages application configuration, asset loading, and environment settings
 */

class ConfigService {
    private static $instance;
    private $config;
    private $environment;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->loadConfiguration();
        $this->detectEnvironment();
    }
    
    /**
     * Load configuration from files
     */
    private function loadConfiguration() {
        // Default configuration
        $this->config = [
            'app' => [
                'name' => 'Collaborative Calendar',
                'description' => 'Ice time management system for arenas and skating programs',
                'version' => '2.0.0',
                'timezone' => 'America/Chicago'
            ],
            'api' => [
                'timeout' => 30,
                'debug' => false,
                'base_url' => null // Auto-detect
            ],
            'assets' => [
                'css_version' => null, // Auto-generate
                'js_version' => null,  // Auto-generate
                'cache_bust' => true
            ],
            'calendar' => [
                'default_view' => 'dayGridMonth',
                'snap_duration' => '00:05:00',
                'time_interval' => 15,
                'height' => 'auto'
            ],
            'import' => [
                'max_file_size' => 5 * 1024 * 1024, // 5MB
                'max_events' => 20,
                'allowed_formats' => ['.json', '.csv', '.ics', '.ical', '.txt']
            ],
            'sse' => [
                'max_reconnect_attempts' => 10,
                'base_reconnect_delay' => 1000,
                'max_reconnect_delay' => 30000
            ]
        ];
        
        // Load environment-specific config if exists
        $configDir = __DIR__ . '/../config/';
        
        if (file_exists($configDir . 'app.php')) {
            $appConfig = include $configDir . 'app.php';
            $this->config = array_merge_recursive($this->config, $appConfig);
        }
        
        // Load local config overrides if exists
        if (file_exists($configDir . 'local.php')) {
            $localConfig = include $configDir . 'local.php';
            $this->config = array_merge_recursive($this->config, $localConfig);
        }
    }
    
    /**
     * Detect environment (development, production, etc.)
     */
    private function detectEnvironment() {
        // Check for environment variable
        $this->environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        
        // Auto-detect based on domain/IP
        if (!$this->environment) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            if (in_array($host, ['localhost', '127.0.0.1', '::1']) || 
                strpos($host, '.local') !== false ||
                strpos($host, '.dev') !== false) {
                $this->environment = 'development';
            } else {
                $this->environment = 'production';
            }
        }
        
        // Apply environment-specific settings
        if ($this->environment === 'development') {
            $this->config['api']['debug'] = true;
            $this->config['assets']['cache_bust'] = true;
        }
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public function set($key, $value) {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * Get environment
     */
    public function getEnvironment() {
        return $this->environment;
    }
    
    /**
     * Check if development environment
     */
    public function isDevelopment() {
        return $this->environment === 'development';
    }
    
    /**
     * Check if production environment
     */
    public function isProduction() {
        return $this->environment === 'production';
    }
    
    /**
     * Get asset URL with versioning
     */
    public function getAssetUrl($path) {
        $baseUrl = $this->getBaseUrl();
        $assetUrl = rtrim($baseUrl, '/') . '/frontend/' . ltrim($path, '/');
        
        if ($this->get('assets.cache_bust', true)) {
            $fullPath = __DIR__ . '/../' . ltrim($path, '/');
            if (file_exists($fullPath)) {
                $version = filemtime($fullPath);
                $assetUrl .= '?v=' . $version;
            } else {
                $assetUrl .= '?v=' . time();
            }
        }
        
        return $assetUrl;
    }
    
    /**
     * Get CSS files for a page
     */
    public function getCssFiles($page) {
        $cssFiles = [
            'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap',
            $this->getAssetUrl('assets/css/style.css')
        ];
        
        // Page-specific CSS
        $pageSpecificCss = [
            'login' => ['assets/css/login.css'],
            'calendar' => ['assets/css/calendar.css'],
            'events' => ['assets/css/events.css', 'assets/css/table.css'],
            'users' => ['assets/css/table.css'],
            'import' => ['assets/css/import.css']
        ];
        
        if (isset($pageSpecificCss[$page])) {
            foreach ($pageSpecificCss[$page] as $css) {
                $cssFiles[] = $this->getAssetUrl($css);
            }
        }
        
        return $cssFiles;
    }
    
    /**
     * Get JavaScript files for a page
     */
    public function getJsFiles($page) {
        $jsFiles = [
            'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'
        ];
        
        // Page-specific JavaScript
        $pageSpecificJs = [
            'login' => [
                'assets/js/auth.js'
            ],
            'calendar' => [
                'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js',
                'assets/js/script.js'
            ],
            'events' => [
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
                'assets/js/events.js'
            ],
            'users' => [
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
                'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
                'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
                'assets/js/users.js'
            ],
            'import' => [
                'assets/js/import.js'
            ]
        ];
        
        if (isset($pageSpecificJs[$page])) {
            foreach ($pageSpecificJs[$page] as $js) {
                if (strpos($js, 'http') === 0) {
                    $jsFiles[] = $js; // External URL
                } else {
                    $jsFiles[] = $this->getAssetUrl($js); // Local file
                }
            }
        }
        
        return $jsFiles;
    }
    
    /**
     * Get DataTables CSS files
     */
    public function getDataTablesCss() {
        return [
            'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css',
            'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css',
            'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'
        ];
    }
    
    /**
     * Get base URL for the application
     */
    public function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remove /frontend/pages from path if present
        $path = str_replace('/frontend/pages', '', $path);
        
        return $protocol . $host . rtrim($path, '/');
    }
    
    /**
     * Get API base URL
     */
    public function getApiUrl() {
        return $this->getBaseUrl() . '/backend/api.php';
    }
    
    /**
     * Get SSE URL
     */
    public function getSseUrl() {
        return $this->getBaseUrl() . '/backend/workers/sse.php';
    }
    
    /**
     * Generate configuration JavaScript for frontend
     */
    public function generateConfigJs() {
        $frontendConfig = [
            'api' => [
                'baseUrl' => $this->getApiUrl(),
                'sseUrl' => $this->getSseUrl(),
                'timeout' => $this->get('api.timeout', 30)
            ],
            'calendar' => $this->get('calendar'),
            'import' => $this->get('import'),
            'sse' => $this->get('sse'),
            'app' => [
                'name' => $this->get('app.name'),
                'version' => $this->get('app.version'),
                'environment' => $this->environment
            ]
        ];
        
        return "window.CalendarConfig = " . json_encode($frontendConfig, JSON_PRETTY_PRINT) . ";";
    }
    
    /**
     * Get page configuration
     */
    public function getPageConfig($page, $extraConfig = []) {
        $config = [
            'title' => $this->get('app.name') . ' - ' . ucfirst($page),
            'css' => $this->getCssFiles($page),
            'js' => $this->getJsFiles($page),
            'pageTitle' => $this->getPageTitle($page),
            'activeNavItem' => $page
        ];
        
        return array_merge($config, $extraConfig);
    }
    
    /**
     * Get page title
     */
    private function getPageTitle($page) {
        $titles = [
            'calendar' => '📅 Collaborative Calendar',
            'events' => '📅 Events Management',
            'users' => '👥 System Users',
            'import' => '📥 Import Events',
            'login' => '📅 Calendar'
        ];
        
        return $titles[$page] ?? '📅 Calendar';
    }
    
    /**
     * Get navigation items configuration
     */
    public function getNavigationConfig() {
        return [
            'calendar' => [
                'url' => './calendar.php',
                'title' => '📅 Calendar',
                'icon' => '📅'
            ],
            'events' => [
                'url' => './events.php',
                'title' => '📋 Events',
                'icon' => '📋'
            ],
            'users' => [
                'url' => './users.php',
                'title' => '👥 Users',
                'icon' => '👥'
            ],
            'import' => [
                'url' => './import.php',
                'title' => '📥 Import',
                'icon' => '📥'
            ]
        ];
    }
    
    /**
     * Create a configuration helper for layouts
     */
    public function forLayout($page, $extraConfig = []) {
        $config = $this->getPageConfig($page, $extraConfig);
        
        // Add configuration JavaScript
        $configJs = $this->generateConfigJs();
        if (isset($config['js'])) {
            $config['js'][] = 'data:text/javascript;base64,' . base64_encode($configJs);
        }
        
        return $config;
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Static helper methods
     */
    public static function get($key, $default = null) {
        return self::getInstance()->get($key, $default);
    }
    
    public static function getAssetUrl($path) {
        return self::getInstance()->getAssetUrl($path);
    }
    
    public static function getPageConfig($page, $extraConfig = []) {
        return self::getInstance()->getPageConfig($page, $extraConfig);
    }
    
    public static function forLayout($page, $extraConfig = []) {
        return self::getInstance()->forLayout($page, $extraConfig);
    }
}