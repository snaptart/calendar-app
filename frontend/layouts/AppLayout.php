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
     * Render enhanced user section with component attributes
     */
    protected function renderUserSection() {
        ?>
        <div class="user-section" data-component-container="user-section">
            <label for="userName">Logged in as</label>
            <input type="text" 
                   id="userName" 
                   placeholder="Authenticating..." 
                   disabled 
                   data-component="user-display"
                   data-component-id="user-display"
                   data-auto-init="false" />
            <span id="userStatus" 
                  class="status"
                  data-component="status"
                  data-component-id="user-status"
                  data-initial-state="checking">
                Checking authentication...
            </span>
            <!-- Logout button will be added dynamically by JavaScript -->
        </div>
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
     * Enhanced calendar controls with component attributes
     */
    public function renderCalendarControls($content = null) {
        ?>
        <div class="calendar-controls" data-component-container="calendar-controls">
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
     * Enhanced table controls with component attributes
     */
    public function renderTableControls($title, $description, $actions = null) {
        ?>
        <div class="table-controls" data-component-container="table-controls">
            <div class="table-info">
                <h3><?php echo $this->e($title); ?></h3>
                <?php if ($description): ?>
                    <p class="table-description"><?php echo $this->e($description); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="table-actions" data-component-container="table-actions">
                <?php 
                if (is_callable($actions)) {
                    call_user_func($actions);
                } elseif ($actions) {
                    echo $actions;
                }
                ?>
                
                <div class="connection-status">
                    <span id="connectionStatus" 
                          class="status"
                          data-component="status"
                          data-component-id="connection-status"
                          data-initial-state="ready">
                        Ready
                    </span>
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
     * Add component initialization scripts
     */
	 public function addComponentScripts()
	 {
		 // Load in proper order: core utilities first, then page scripts
		 $this->addJS('../assets/js/script.js');        // Main calendar functionality

		 return $this;
	 }
    
    /**
     * Enhanced method to add authentication scripts with component support
     */
    public function addAuthenticationScripts() {
        $this->addInlineJS('
            // Enhanced authentication event handlers with component integration
            document.addEventListener("DOMContentLoaded", function() {
                // Initialize components first
                if (typeof ComponentInitializer !== "undefined") {
                    ComponentInitializer.initializeAll();
                }
                
                // Add logout functionality if button exists
                const logoutBtn = document.getElementById("logoutBtn");
                if (logoutBtn) {
                    logoutBtn.addEventListener("click", function() {
                        if (confirm("Are you sure you want to logout?")) {
                            window.location.href = "./logout.php";
                        }
                    });
                }
                
                // Handle authentication status updates
                if (typeof EventBus !== "undefined") {
                    EventBus.on("auth:unauthorized", function() {
                        window.location.href = "./login.php";
                    });
                    
                    EventBus.on("auth:authenticated", function(data) {
                        console.log("User authenticated:", data.user);
                    });
                }
            });
        ');
        
        return $this;
    }
    
    /**
     * Enhanced navigation script with component support
     */
    public function addNavigationScript() {
        $this->addInlineJS('
            document.addEventListener("DOMContentLoaded", function() {
                // Wait for components to initialize
                setTimeout(function() {
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
                }, 100);
            });
        ');
        
        return $this;
    }
    
    /**
     * Enhanced render method with component debugging support
     */
    public function renderContentWithComponents($contentCallback, $debugMode = false) {
        $this->start();
        
        echo '<div class="' . $this->e($this->containerClass) . '"';
        if ($debugMode) {
            echo ' data-debug="true"';
        }
        echo ' data-app="collaborative-calendar">' . "\n";
        
        // Render header
        $this->renderHeader();
        
        // Render main content with component wrapper
        echo '<main data-component-container="main">' . "\n";
        if (is_callable($contentCallback)) {
            call_user_func($contentCallback);
        } else {
            echo $contentCallback;
        }
        echo '</main>' . "\n";
        
        echo '</div>' . "\n";
        
        // Render loading overlay and message containers
        $this->renderLoadingOverlay();
        $this->renderMessageContainers();
        
        $this->end();
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
     * Enhanced method to create a complete authenticated page with component support
     */
    public static function createComponentPage($config, $contentCallback) {
        $layout = new self($config);
        
        // Add component scripts and authentication
        $layout->addComponentScripts()
               ->addAuthenticationScripts()
               ->addNavigationScript();
        
        // Render the complete page
        $layout->renderContent($contentCallback);
    }
    
    /**
     * Create a simple content page (like tables)
     */
    public static function createTablePage($config, $tableContent) {
        $config['containerClass'] = 'container';
        
        self::createComponentPage($config, function() use ($config, $tableContent) {
            $layout = new self($config);
            
            // Render table controls if provided
            if (isset($config['tableTitle'])) {
                $layout->renderTableControls(
                    $config['tableTitle'],
                    $config['tableDescription'] ?? '',
                    $config['tableActions'] ?? null
                );
            }
            
            // Render main table content with component attributes
            echo '<div data-component-container="table">';
            if (is_callable($tableContent)) {
                call_user_func($tableContent);
            } else {
                echo $tableContent;
            }
            echo '</div>';
        });
    }
    
    /**
     * Enhanced method to create a calendar page with component support
     */
    public static function createCalendarPage($config, $calendarContent, $controlsContent = null) {
        self::createComponentPage($config, function() use ($config, $calendarContent, $controlsContent) {
            $layout = new self($config);
            
            // Render calendar controls with component support
            $layout->renderCalendarControls(function() use ($controlsContent) {
                echo '<div data-component-container="calendar-controls">';
                if (is_callable($controlsContent)) {
                    call_user_func($controlsContent);
                } elseif ($controlsContent) {
                    echo $controlsContent;
                }
                echo '</div>';
            });
            
            // Render calendar content with component attributes
            echo '<div data-component-container="calendar">';
            if (is_callable($calendarContent)) {
                call_user_func($calendarContent);
            } else {
                echo $calendarContent;
            }
            echo '</div>';
        });
    }
    
    /**
     * Create an import page with component support
     */
    public static function createImportPage($config, $importContent) {
        self::createComponentPage($config, function() use ($config, $importContent) {
            // Render import content with component attributes
            echo '<div data-component-container="import">';
            if (is_callable($importContent)) {
                call_user_func($importContent);
            } else {
                echo $importContent;
            }
            echo '</div>';
        });
    }
    
    /**
     * Create a form page with component support
     */
    public static function createFormPage($config, $formContent) {
        self::createComponentPage($config, function() use ($config, $formContent) {
            // Render form content with component attributes
            echo '<div data-component-container="form">';
            if (is_callable($formContent)) {
                call_user_func($formContent);
            } else {
                echo $formContent;
            }
            echo '</div>';
        });
    }
}