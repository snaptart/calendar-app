<?php

class PageController {
    private $pageConfig;
    private $dependencies;
    private $currentUser;
    
    public function __construct($currentUser) {
        $this->pageConfig = require_once 'frontend/config/pages.php';
        $this->dependencies = require_once 'frontend/config/dependencies.php';
        $this->currentUser = $currentUser;
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
    
    private function renderPage($page, $config) {
        $currentUser = $this->currentUser;
        $pageController = $this;
        include 'frontend/layout/html-head.php';
        ?>
        <body>
            <div class="container">
                <?php include 'frontend/layout/header.php'; ?>
                
                <?php if (isset($config['sidebar']) && $config['sidebar']): ?>
                <div class="calendar-controls">
                    <?php include 'frontend/layout/sidebar.php'; ?>
                </div>
                <?php endif; ?>

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