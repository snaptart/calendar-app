<?php
session_start();

// Check authentication
require_once 'backend/database/config.php';
require_once 'backend/auth/auth.php';

$auth = new Auth($pdo);
$authCheck = $auth->checkAuth();

if (!$authCheck['authenticated']) {
    header('Location: login.php');
    exit;
}

$currentUser = $authCheck['user'];

// Page configuration
$pageConfig = [
    'calendar' => [
        'title' => 'Calendar',
        'styles' => ['calendar.css'],
        'scripts' => ['script.js'],
        'requires' => ['jquery-datetimepicker', 'fullcalendar'],
        'sidebar' => true
    ],
    'events' => [
        'title' => 'Events',
        'styles' => ['events.css', 'table.css'],
        'scripts' => ['events.js'],
        'requires' => ['datatables'],
        'sidebar' => false
    ],
    'users' => [
        'title' => 'Users',
        'styles' => ['events.css', 'table.css'],
        'scripts' => ['users.js'],
        'requires' => ['datatables'],
        'sidebar' => false
    ],
    'import' => [
        'title' => 'Import',
        'styles' => ['import.css'],
        'scripts' => ['import.js'],
        'requires' => [],
        'sidebar' => false
    ]
];

// Determine which page to display
$page = isset($_GET['page']) && isset($pageConfig[$_GET['page']]) ? $_GET['page'] : 'calendar';
$config = $pageConfig[$page];

// External dependencies configuration
$externalDeps = [
    'jquery-datetimepicker' => [
        'css' => ['https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css'],
        'js' => ['https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js']
    ],
    'fullcalendar' => [
        'css' => [],
        'js' => ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js']
    ],
    'datatables' => [
        'css' => [
            'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css',
            'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css'
        ],
        'js' => [
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentUser['name']); ?>'s <?php echo $config['title']; ?></title>
    
    <!-- Google Fonts - Noto Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery (base requirement) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <?php 
    // Load external dependencies CSS
    foreach ($config['requires'] as $dep) {
        if (isset($externalDeps[$dep]['css'])) {
            foreach ($externalDeps[$dep]['css'] as $css) {
                echo "<link rel=\"stylesheet\" href=\"{$css}\">\n    ";
            }
        }
    }
    ?>
    
    <!-- Application Styles -->
    <link rel="stylesheet" href="frontend/css/style.css">
    <?php 
    // Load page-specific styles
    foreach ($config['styles'] as $style) {
        echo "<link rel=\"stylesheet\" href=\"frontend/css/{$style}\">\n    ";
    }
    ?>
</head>
<body>
    <div class="container">
        <?php include 'frontend/layout/header.php'; ?>
        
        <?php if ($config['sidebar']): ?>
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
    
    <?php 
    // Load external dependencies JS
    foreach ($config['requires'] as $dep) {
        if (isset($externalDeps[$dep]['js'])) {
            foreach ($externalDeps[$dep]['js'] as $js) {
                echo "<script src=\"{$js}\"></script>\n    ";
            }
        }
    }
    ?>
    
    <!-- Pass data to JavaScript -->
    <script>
        window.currentUser = <?php echo json_encode($currentUser); ?>;
        window.currentPage = '<?php echo $page; ?>';
    </script>
    
    <!-- Application JavaScript -->
    <?php 
    // Load page-specific scripts
    foreach ($config['scripts'] as $script) {
        // Load script.js as a module for ES6 imports
        if ($script === 'script.js') {
            echo "<script type=\"module\" src=\"frontend/js/{$script}\"></script>\n    ";
        } else {
            echo "<script src=\"frontend/js/{$script}\"></script>\n    ";
        }
    }
    ?>
</body>
</html>