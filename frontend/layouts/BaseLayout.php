<?php
/**
 * Base Layout Template - Foundation for all pages
 * Location: frontend/layouts/BaseLayout.php
 * 
 * Provides common HTML structure, meta tags, and asset management
 */

class BaseLayout {
    protected $title = 'Collaborative Calendar';
    protected $description = 'Ice time management system for arenas and skating programs';
    protected $cssFiles = [];
    protected $jsFiles = [];
    protected $inlineCSS = '';
    protected $inlineJS = '';
    protected $bodyClasses = [];
    protected $htmlAttributes = [];
    protected $metaTags = [];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        // Set default CSS files
        $this->cssFiles = [
            'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap',
            '../assets/css/style.css'
        ];
        
        // Set default JS files
        $this->jsFiles = [
            'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'
        ];
        
        // Apply configuration
        $this->configure($config);
    }
    
    /**
     * Configure layout with options
     */
    protected function configure($config) {
        if (isset($config['title'])) {
            $this->title = $config['title'];
        }
        
        if (isset($config['description'])) {
            $this->description = $config['description'];
        }
        
        if (isset($config['css'])) {
            $this->cssFiles = array_merge($this->cssFiles, $config['css']);
        }
        
        if (isset($config['js'])) {
            $this->jsFiles = array_merge($this->jsFiles, $config['js']);
        }
        
        if (isset($config['bodyClasses'])) {
            $this->bodyClasses = is_array($config['bodyClasses']) 
                ? $config['bodyClasses'] 
                : [$config['bodyClasses']];
        }
        
        if (isset($config['metaTags'])) {
            $this->metaTags = $config['metaTags'];
        }
    }
    
    /**
     * Add CSS file
     */
    public function addCSS($file) {
        if (!in_array($file, $this->cssFiles)) {
            $this->cssFiles[] = $file;
        }
        return $this;
    }
    
    /**
     * Add JavaScript file
     */
    public function addJS($file) {
        if (!in_array($file, $this->jsFiles)) {
            $this->jsFiles[] = $file;
        }
        return $this;
    }
    
    /**
     * Add inline CSS
     */
    public function addInlineCSS($css) {
        $this->inlineCSS .= $css . "\n";
        return $this;
    }
    
    /**
     * Add inline JavaScript
     */
    public function addInlineJS($js) {
        $this->inlineJS .= $js . "\n";
        return $this;
    }
    
    /**
     * Add body class
     */
    public function addBodyClass($class) {
        if (!in_array($class, $this->bodyClasses)) {
            $this->bodyClasses[] = $class;
        }
        return $this;
    }
    
    /**
     * Set page title
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Render HTML head section
     */
    protected function renderHead() {
        ?>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($this->title); ?></title>
            <meta name="description" content="<?php echo htmlspecialchars($this->description); ?>">
            
            <?php $this->renderMetaTags(); ?>
            <?php $this->renderCSS(); ?>
        </head>
        <?php
    }
    
    /**
     * Render custom meta tags
     */
    protected function renderMetaTags() {
        foreach ($this->metaTags as $name => $content) {
            echo '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">' . "\n";
        }
    }
    
    /**
     * Render CSS files and inline CSS
     */
    protected function renderCSS() {
        // External CSS files
        foreach ($this->cssFiles as $cssFile) {
            if (filter_var($cssFile, FILTER_VALIDATE_URL)) {
                // External URL
                echo '<link rel="stylesheet" href="' . htmlspecialchars($cssFile) . '">' . "\n";
            } else {
                // Local file - add version for cache busting
                $version = $this->getFileVersion($cssFile);
                echo '<link rel="stylesheet" href="' . htmlspecialchars($cssFile) . '?v=' . $version . '">' . "\n";
            }
        }
        
        // Inline CSS
        if (!empty($this->inlineCSS)) {
            echo '<style>' . "\n" . $this->inlineCSS . '</style>' . "\n";
        }
    }
    
    /**
     * Render JavaScript files and inline JS
     */
    protected function renderJS() {
        // External JS files
        foreach ($this->jsFiles as $jsFile) {
            if (filter_var($jsFile, FILTER_VALIDATE_URL)) {
                // External URL
                echo '<script src="' . htmlspecialchars($jsFile) . '"></script>' . "\n";
            } else {
                // Local file - add version for cache busting
                $version = $this->getFileVersion($jsFile);
                echo '<script src="' . htmlspecialchars($jsFile) . '?v=' . $version . '"></script>' . "\n";
            }
        }
        
        // Inline JavaScript
        if (!empty($this->inlineJS)) {
            echo '<script>' . "\n" . $this->inlineJS . '</script>' . "\n";
        }
    }
    
    /**
     * Get file version for cache busting
     */
    protected function getFileVersion($file) {
        $filePath = __DIR__ . '/../' . $file;
        if (file_exists($filePath)) {
            return filemtime($filePath);
        }
        return time();
    }
    
    /**
     * Render body classes
     */
    protected function getBodyClasses() {
        return !empty($this->bodyClasses) ? ' class="' . implode(' ', $this->bodyClasses) . '"' : '';
    }
    
    /**
     * Start rendering the layout
     */
    public function start() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <?php $this->renderHead(); ?>
        <body<?php echo $this->getBodyClasses(); ?>>
        <?php
    }
    
    /**
     * End rendering the layout
     */
    public function end() {
        ?>
        <?php $this->renderJS(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render complete layout with content
     */
    public function render($contentCallback) {
        $this->start();
        
        if (is_callable($contentCallback)) {
            call_user_func($contentCallback);
        } else {
            echo $contentCallback;
        }
        
        $this->end();
    }
    
    /**
     * Render loading overlay component
     */
    protected function renderLoadingOverlay($id = 'loadingOverlay', $message = 'Loading...') {
        ?>
        <div id="<?php echo htmlspecialchars($id); ?>" class="loading-overlay hidden">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render error/success message containers
     */
    protected function renderMessageContainers() {
        ?>
        <div id="messageContainer" class="message-container">
            <!-- Dynamic messages will be inserted here -->
        </div>
        <?php
    }
    
    /**
     * Include a partial template
     */
    protected function includePartial($partialPath, $variables = []) {
        if (!empty($variables)) {
            extract($variables);
        }
        
        $fullPath = __DIR__ . '/../components/' . $partialPath . '.php';
        if (file_exists($fullPath)) {
            include $fullPath;
        } else {
            echo "<!-- Partial not found: {$partialPath} -->";
        }
    }
    
    /**
     * Escape HTML output
     */
    protected function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate CSRF token (placeholder for future implementation)
     */
    protected function csrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Render CSRF token as hidden input
     */
    protected function csrfField() {
        echo '<input type="hidden" name="csrf_token" value="' . $this->csrfToken() . '">';
    }
}