<?php

class PageController {
    private $pageConfig;
    private $dependencies;
    private $currentUser;
    
    public function __construct($currentUser) {
        $this->pageConfig = require_once 'frontend/config/pages.php';
        $this->dependencies = require_once 'frontend/config/dependencies.php';
        $this->currentUser = $currentUser;
        
        // Load URL helper
        require_once 'frontend/helpers/UrlHelper.php';
    }
    
    public function handleRequest() {
        $page = $this->getRequestedPage();
        $config = $this->getPageConfig($page);
        
        $this->renderPage($page, $config);
    }
    
    private function getRequestedPage() {
        $page = isset($_GET['page']) ? $_GET['page'] : 'calendar';
        return isset($this->pageConfig[$page]) ? $page : 'calendar';
    }
    
    public function getUrlParameters() {
        return [
            'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'event_id' => isset($_GET['event_id']) ? (int)$_GET['event_id'] : null,
            'action' => isset($_GET['action']) ? $_GET['action'] : null,
            'year' => isset($_GET['year']) ? (int)$_GET['year'] : null,
            'month' => isset($_GET['month']) ? (int)$_GET['month'] : null,
            'day' => isset($_GET['day']) ? (int)$_GET['day'] : null
        ];
    }
    
    private function renderPage($page, $config) {
        $currentUser = $this->currentUser;
        $pageController = $this;
        include 'frontend/layout/html-head.php';
        ?>
        <body>
            <!-- Header Section -->
            <?php include 'frontend/layout/header.php'; ?>
            
            <div class="container">
                <div class="layout-wrapper">
                    <!-- Sidebar Section -->
                    <?php if (isset($config['sidebar']) && $config['sidebar']): ?>
                    <aside class="sidebar">
                        <?php include 'frontend/layout/sidebar.php'; ?>
                    </aside>
                    <?php endif; ?>

                    <!-- Main Content Section -->
                    <main id="main-content">
                        <?php
                        $pageFile = "frontend/pages/{$page}.php";
                        if (file_exists($pageFile)) {
                            include $pageFile;
                        } else {
                            echo '<div class="error-message"><p>Page not found.</p></div>';
                        }
                        ?>
                    </main>
                </div>
            </div>

            <?php include 'frontend/layout/footer.php'; ?>
            <?php $this->renderScripts($config); ?>
        </body>
        </html>
        <?php
    }
    
    private function renderScripts($config) {
        // Load external dependencies JS
        if (isset($config['requires']) && is_array($config['requires'])) {
            foreach ($config['requires'] as $dep) {
                if (isset($this->dependencies[$dep]['js'])) {
                    foreach ($this->dependencies[$dep]['js'] as $js) {
                        echo "<script src=\"{$js}\"></script>\n    ";
                    }
                }
            }
        }
        
        // Pass data to JavaScript
        ?>
        <script>
            window.currentUser = <?php echo json_encode($this->currentUser); ?>;
            window.currentPage = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'calendar'; ?>';
            window.urlParameters = <?php echo json_encode($this->getUrlParameters()); ?>;
        </script>
        
        <?php
        // Load page-specific scripts
        if (isset($config['scripts']) && is_array($config['scripts'])) {
            foreach ($config['scripts'] as $script) {
                // Load modular scripts as ES6 modules for imports
                if (in_array($script, ['calendar.js', 'users.js', 'import.js', 'events.js'])) {
                    echo "<script type=\"module\" src=\"frontend/assets/js/{$script}\"></script>\n    ";
                } else {
                    echo "<script src=\"frontend/assets/js/{$script}\"></script>\n    ";
                }
            }
        }
    }
    
    public function getPageConfig($page = null) {
        if ($page === null) {
            $page = $this->getRequestedPage();
        }
        return isset($this->pageConfig[$page]) ? $this->pageConfig[$page] : $this->pageConfig['calendar'];
    }
    
    public function getDependencies() {
        return $this->dependencies;
    }
}