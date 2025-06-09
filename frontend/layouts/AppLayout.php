<?php
/**
 * Application Layout Template - Main app layout with authentication
 * Location: frontend/layouts/AppLayout.php
 * 
 * Extends BaseLayout to provide authenticated app structure with header, navigation, and footer
 */

require_once __DIR__ . '/BaseLayout.php';

class AppLayout extends BaseLayout {
    protected $pageTitle = '';
    protected $breadcrumbs = [];
    protected $currentUser = null;
    protected $navigationItems = [];
    protected $showUserSection = true;
    protected $showNavigation = true;
    protected $showBreadcrumbs = false;
    protected $containerClass = 'container';
    protected $activeNavItem = '';
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        parent::__construct($config);
        
        // Initialize navigation items
        $this->initializeNavigation();
        
        // Apply app-specific configuration
        $this->configureApp($config);
    }
    
    /**
     * Initialize default navigation items
     */
    protected function initializeNavigation() {
        $this->navigationItems = [
            'calendar' => [
                'url' => './calendar.php',
                'title' => '📅 Calendar',
                'active' => false
            ],
            'events' => [
                'url' => './events.php',
                'title' => '📋 Events',
                'active' => false
            ],
            'users' => [
                'url' => './users.php',
                'title' => '👥 Users',
                'active' => false
            ],
            'import' => [
                'url' => './import.php',
                'title' => '📥 Import',
                'active' => false
            ]
        ];
    }
    
    /**
     * Configure app-specific settings
     */
    protected function configureApp($config) {
        if (isset($config['pageTitle'])) {
            $this->pageTitle = $config['pageTitle'];
        }
        
        if (isset($config['breadcrumbs'])) {
            $this->breadcrumbs = $config['breadcrumbs'];
            $this->showBreadcrumbs = !empty($this->breadcrumbs);
        }
        
        if (isset($config['currentUser'])) {
            $this->currentUser = $config['currentUser'];
        }
        
        if (isset($config['showUserSection'])) {
            $this->showUserSection = $config['showUserSection'];
        }
        
        if (isset($config['showNavigation'])) {
            $this->showNavigation = $config['showNavigation'];
        }
        
        if (isset($config['activeNavItem'])) {
            $this->activeNavItem = $config['activeNavItem'];
            $this->setActiveNavigationItem($config['activeNavItem']);
        }
        
        if (isset($config['containerClass'])) {
            $this->containerClass = $config['containerClass'];
        }
    }
    
    /**
     * Set active navigation item
     */
    public function setActiveNavigationItem($itemKey) {
        // Reset all items
        foreach ($this->navigationItems as $key => $item) {
            $this->navigationItems[$key]['active'] = false;
        }
        
        // Set active item
        if (isset($this->navigationItems[$itemKey])) {
            $this->navigationItems[$itemKey]['active'] = true;
        }
        
        return $this;
    }
    
    /**
     * Add breadcrumb
     */
    public function addBreadcrumb($title, $url = null) {
        $this->breadcrumbs[] = [
            'title' => $title,
            'url' => $url
        ];
        $this->showBreadcrumbs = true;
        return $this;
    }
    
    /**
     * Set current user
     */
    public function setCurrentUser($user) {
        $this->currentUser = $user;
        return $this;
    }
    
    /**
     * Render header section
     */
    protected function renderHeader() {
        ?>
        <header>
            <div class="header-content">
                <div class="header-left">
                    <h1><?php echo $this->pageTitle ?: '📅 Collaborative Calendar'; ?></h1>
                    
                    <?php if ($this->showNavigation): ?>
                        <nav class="main-navigation">
                            <?php $this->renderNavigation(); ?>
                        </nav>
                    <?php endif; ?>
                    
                    <?php if ($this->showBreadcrumbs): ?>
                        <nav class="breadcrumb-nav">
                            <?php $this->renderBreadcrumbs(); ?>
                        </nav>
                    <?php endif; ?>
                </div>
                
                <?php if ($this->showUserSection): ?>
                    <div class="user-section">
                        <?php $this->renderUserSection(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }
    
    /**
     * Render main navigation
     */
    protected function renderNavigation() {
        foreach ($this->navigationItems as $key => $item) {
            $activeClass = $item['active'] ? ' active' : '';
            echo '<a href="' . $this->e($item['url']) . '" class="nav-link' . $activeClass . '">';
            echo $this->e($item['title']);
            echo '</a>' . "\n";
        }
    }
    
    /**
     * Render breadcrumbs
     */
    protected function renderBreadcrumbs() {
        $breadcrumbCount = count($this->breadcrumbs);
        
        foreach ($this->breadcrumbs as $index => $breadcrumb) {
            if ($breadcrumb['url'] && $index < $breadcrumbCount - 1) {
                echo '<a href="' . $this->e($breadcrumb['url']) . '" class="nav-link">';
                echo $this->e($breadcrumb['title']);
                echo '</a>';
            } else {
                echo '<span class="nav-current">' . $this->e($breadcrumb['title']) . '</span>';
            }
            
            if ($index < $breadcrumbCount - 1) {
                echo '<span class="nav-separator">|</span>';
            }
        }
    }
    
    /**
     * Render user section
     */
    protected function renderUserSection() {
        ?>
        <label for="userName">Logged in as</label>
        <input type="text" id="userName" placeholder="Authenticating..." disabled />
        <span id="userStatus" class="status">Checking authentication...</span>
        <!-- Logout button will be added dynamically by JavaScript -->
        <?php
    }
    
    /**
     * Render the main content wrapper
     */
    public function renderContent($contentCallback) {
        $this->start();
        
        echo '<div class="' . $this->e($this->containerClass) . '">' . "\n";
        
        // Render header
        $this->renderHeader();
        
        // Render main content
        if (is_callable($contentCallback)) {
            call_user_func($contentCallback);
        } else {
            echo $contentCallback;
        }
        
        echo '</div>' . "\n";
        
        // Render loading overlay and message containers
        $this->renderLoadingOverlay();
        $this->renderMessageContainers();
        
        $this->end();
    }
    
    /**
     * Render calendar controls section
     */
    public function renderCalendarControls($content = null) {
        ?>
        <div class="calendar-controls">
            <?php 
            if (is_callable($content)) {
                call_user_func($content);
            } elseif ($content) {
                echo $content;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Render table controls section
     */
    public function renderTableControls($title, $description, $actions = null) {
        ?>
        <div class="table-controls">
            <div class="table-info">
                <h3><?php echo $this->e($title); ?></h3>
                <?php if ($description): ?>
                    <p class="table-description"><?php echo $this->e($description); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="table-actions">
                <?php 
                if (is_callable($actions)) {
                    call_user_func($actions);
                } elseif ($actions) {
                    echo $actions;
                }
                ?>
                
                <div class="connection-status">
                    <span id="connectionStatus" class="status">Initializing...</span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render content wrapper with background
     */
    public function renderContentWrapper($content, $wrapperClass = 'content-wrapper') {
        ?>
        <div class="<?php echo $this->e($wrapperClass); ?>">
            <?php 
            if (is_callable($content)) {
                call_user_func($content);
            } else {
                echo $content;
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Add authentication scripts
     */
    public function addAuthenticationScripts() {
        $this->addInlineJS('
            // Global authentication event handlers
            document.addEventListener("DOMContentLoaded", function() {
                // Add logout functionality if button exists
                const logoutBtn = document.getElementById("logoutBtn");
                if (logoutBtn) {
                    logoutBtn.addEventListener("click", function() {
                        if (confirm("Are you sure you want to logout?")) {
                            window.location.href = "./login.php";
                        }
                    });
                }
                
                // Handle authentication status updates
                if (typeof EventBus !== "undefined") {
                    EventBus.on("auth:unauthorized", function() {
                        window.location.href = "./login.php";
                    });
                }
            });
        ');
        
        return $this;
    }
    
    /**
     * Add navigation highlighting script
     */
    public function addNavigationScript() {
        $this->addInlineJS('
            document.addEventListener("DOMContentLoaded", function() {
                // Update active navigation based on current page
                const currentPage = window.location.pathname.split("/").pop() || "calendar.php";
                const navLinks = document.querySelectorAll(".nav-link");
                
                navLinks.forEach(function(link) {
                    link.classList.remove("active");
                    const linkPage = link.getAttribute("href").replace("./", "");
                    
                    if (currentPage === linkPage || 
                        (currentPage === "" && linkPage === "calendar.php") ||
                        (currentPage === "index.php" && linkPage === "calendar.php")) {
                        link.classList.add("active");
                    }
                });
            });
        ');
        
        return $this;
    }
    
    /**
     * Create a complete authenticated page
     */
    public static function createPage($config, $contentCallback) {
        $layout = new self($config);
        
        // Add common authentication and navigation scripts
        $layout->addAuthenticationScripts()
               ->addNavigationScript();
        
        // Render the complete page
        $layout->renderContent($contentCallback);
    }
    
    /**
     * Create a simple content page (like tables)
     */
    public static function createTablePage($config, $tableContent) {
        $config['containerClass'] = 'container';
        
        self::createPage($config, function() use ($config, $tableContent) {
            $layout = new self($config);
            
            // Render table controls if provided
            if (isset($config['tableTitle'])) {
                $layout->renderTableControls(
                    $config['tableTitle'],
                    $config['tableDescription'] ?? '',
                    $config['tableActions'] ?? null
                );
            }
            
            // Render main table content
            if (is_callable($tableContent)) {
                call_user_func($tableContent);
            } else {
                echo $tableContent;
            }
        });
    }
    
    /**
     * Create a calendar page
     */
    public static function createCalendarPage($config, $calendarContent, $controlsContent = null) {
        self::createPage($config, function() use ($config, $calendarContent, $controlsContent) {
            $layout = new self($config);
            
            // Render calendar controls
            $layout->renderCalendarControls($controlsContent);
            
            // Render calendar content
            if (is_callable($calendarContent)) {
                call_user_func($calendarContent);
            } else {
                echo $calendarContent;
            }
        });
    }
}