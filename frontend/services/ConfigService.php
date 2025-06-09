<?php
/**
 * Configuration Service - Fixed version
 * Location: frontend/services/ConfigService.php
 */

class ConfigService {
    private static $instance;
    private $config;
    private $environment;
    
    private function __construct() {
        $this->loadConfiguration();
        $this->detectEnvironment();
    }
    
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
                'base_url' => null
            ],
            'assets' => [
                'css_version' => null,
                'js_version' => null,
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
            ]
        ];
        
        // Load environment-specific config if exists
        $configDir = __DIR__ . '/../config/';
        
        if (file_exists($configDir . 'app.php')) {
            $appConfig = include $configDir . 'app.php';
            if (is_array($appConfig)) {
                $this->config = $this->mergeConfig($this->config, $appConfig);
            }
        }
    }
    
    /**
     * Safe config merging to avoid array-to-string conversion
     */
    private function mergeConfig($array1, $array2) {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeConfig($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    private function detectEnvironment() {
        $this->environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        
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
        
        if ($this->environment === 'development') {
            $this->config['api']['debug'] = true;
            $this->config['assets']['cache_bust'] = true;
        }
    }
    
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
    
    public function getEnvironment() {
        return $this->environment;
    }
    
    public function isDevelopment() {
        return $this->environment === 'development';
    }
    
    public function isProduction() {
        return $this->environment === 'production';
    }
    
    /**
     * Get asset URL with versioning - fixed path resolution
     */
    public function getAssetUrl($path) {
        $baseUrl = $this->getBaseUrl();
        
        // Fix path construction
        $assetUrl = rtrim($baseUrl, '/') . '/frontend/' . ltrim($path, '/');
        
        if ($this->get('assets.cache_bust', true)) {
            // Construct proper file path for version checking
            $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
            $frontendDir = dirname($scriptDir); // Go up from pages to frontend
            $fullPath = $frontendDir . '/' . ltrim($path, '/');
            
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
            $this->getAssetUrl('css/style.css')
        ];
        
        // Page-specific CSS
        $pageSpecificCss = [
            'login' => ['css/login.css'],
            'calendar' => ['css/calendar.css'],
            'events' => ['css/events.css', 'css/table.css'],
            'users' => ['css/table.css'],
            'import' => ['css/import.css']
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
                'js/auth.js'
            ],
            'calendar' => [
                'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js',
                'js/script.js'
            ],
            'events' => [
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
                'js/events.js'
            ],
            'users' => [
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
                'js/users.js'
            ],
            'import' => [
                'js/import.js'
            ]
        ];
        
        if (isset($pageSpecificJs[$page])) {
            foreach ($pageSpecificJs[$page] as $js) {
                if (strpos($js, 'http') === 0) {
                    $jsFiles[] = $js;
                } else {
                    $jsFiles[] = $this->getAssetUrl($js);
                }
            }
        }
        
        return $jsFiles;
    }
    
    /**
     * Get base URL for the application
     */
    public function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remove /pages from path if present
        $path = str_replace('/pages', '', $path);
        
        return $protocol . $host . rtrim($path, '/');
    }
    
    /**
     * Get API base URL
     */
    public function getApiUrl() {
        return $this->getBaseUrl() . '/backend/api.php';
    }
    
    /**
     * Generate configuration JavaScript for frontend
     */
    public function generateConfigJs() {
        $frontendConfig = [
            'api' => [
                'baseUrl' => $this->getApiUrl(),
                'timeout' => $this->get('api.timeout', 30)
            ],
            'calendar' => $this->get('calendar'),
            'import' => $this->get('import'),
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
     * Create a configuration helper for layouts
     */
    public function forLayout($page, $extraConfig = []) {
        $config = $this->getPageConfig($page, $extraConfig);
        $config['configJs'] = $this->generateConfigJs();
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
}