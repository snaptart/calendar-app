<?php
session_start();

// Check authentication
require_once 'backend/database/config.php';
require_once 'backend/auth/auth.php';

$auth = new Auth($pdo);
$authCheck = $auth->checkAuth();

if (!$authCheck['authenticated']) {
    header('Location: frontend/pages/login.php');
    exit;
}

$currentUser = $authCheck['user'];

// For SPA: Always serve the shell regardless of the path
global $pdo;
include 'frontend/layout/html-head.php';
?>
<body>
    <!-- Header Section -->
    <?php include 'frontend/layout/header.php'; ?>
    
    <div class="container">
        <div class="layout-wrapper">
            <!-- Sidebar Section (will be shown/hidden by SPA) -->
            <aside class="sidebar">
                <?php include 'frontend/layout/sidebar.php'; ?>
            </aside>

            <!-- Main Content Section (will be populated by SPA) -->
            <main id="main-content">
                <div class="loading-message">Loading...</div>
            </main>
        </div>
    </div>

    <?php include 'frontend/layout/footer.php'; ?>
    
    <!-- SPA Core Scripts -->
    <script>
        // Pass server data to JavaScript
        window.currentUser = <?php echo json_encode($currentUser); ?>;
        window.currentPage = 'calendar'; // Default page
        window.urlParameters = {};
    </script>
    
    <!-- Load core dependencies first -->
    <script src="frontend/assets/js/core/event-bus.js" type="module"></script>
    <script src="frontend/assets/js/core/config.js" type="module"></script>
    <script src="frontend/assets/js/auth/auth-guard.js" type="module"></script>
    <script src="frontend/assets/js/auth/user-manager.js" type="module"></script>
    <script src="frontend/assets/js/ui/ui-manager.js" type="module"></script>
    <script src="frontend/assets/js/ui/modal-manager.js" type="module"></script>
    <script src="frontend/assets/js/realtime/sse-manager.js" type="module"></script>
    
    <!-- Load all page modules -->
    <script src="frontend/assets/js/modules/calendar-module.js" type="module"></script>
    <script src="frontend/assets/js/modules/events-module.js" type="module"></script>
    <script src="frontend/assets/js/modules/users-module.js" type="module"></script>
    <script src="frontend/assets/js/modules/import-module.js" type="module"></script>
    
    <!-- Load SPA infrastructure -->
    <script src="frontend/assets/js/core/router.js" type="module"></script>
    <script src="frontend/assets/js/spa-app.js" type="module"></script>
</body>
</html>
<?php
?>