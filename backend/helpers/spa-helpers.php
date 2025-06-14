<?php
/**
 * SPA Helper Functions
 * Provides functionality for Single Page Application support
 */

/**
 * Get page content for SPA
 */
function getSPAPageContent($page) {
    global $pdo;
    
    $pageFile = __DIR__ . "/../../frontend/pages/{$page}.php";
    
    if (!file_exists($pageFile)) {
        throw new Exception("Page file not found: {$page}");
    }
    
    // Capture the page content
    ob_start();
    include $pageFile;
    $content = ob_get_clean();
    
    // Get sidebar content if needed
    $sidebar = '';
    $config = getSPAPageConfig($page);
    
    if ($config['sidebar']) {
        $sidebarFile = __DIR__ . "/../../frontend/layout/sidebar.php";
        if (file_exists($sidebarFile)) {
            ob_start();
            include $sidebarFile;
            $sidebar = ob_get_clean();
        }
    }
    
    return [
        'content' => $content,
        'sidebar' => $sidebar
    ];
}

/**
 * Get page configuration for SPA
 */
function getSPAPageConfig($page) {
    $configFile = __DIR__ . "/../../frontend/config/pages.php";
    
    if (!file_exists($configFile)) {
        throw new Exception("Page configuration file not found");
    }
    
    $config = include $configFile;
    
    if (!isset($config[$page])) {
        throw new Exception("Configuration not found for page: {$page}");
    }
    
    return $config[$page];
}

/**
 * Get dependencies configuration for SPA
 */
function getSPADependencies($requestedDeps) {
    $depsFile = __DIR__ . "/../../frontend/config/dependencies.php";
    
    if (!file_exists($depsFile)) {
        throw new Exception("Dependencies configuration file not found");
    }
    
    $allDeps = include $depsFile;
    $result = [];
    
    foreach ($requestedDeps as $dep) {
        if (isset($allDeps[$dep])) {
            $result[$dep] = $allDeps[$dep];
        }
    }
    
    return $result;
}