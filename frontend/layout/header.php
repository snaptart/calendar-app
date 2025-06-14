<?php
// Ensure we have current user and page variables
$currentUser = $currentUser ?? null;
$page = $page ?? 'calendar';

// Navigation menu configuration
$navItems = [
    'calendar' => ['icon' => 'calendar', 'label' => 'Calendar'],
    'events' => ['icon' => 'list-checks', 'label' => 'Events'],
    'users' => ['icon' => 'users', 'label' => 'Users'],
    'import' => ['icon' => 'download', 'label' => 'Import']
];
?>

<header>
    <div class="header-content">
        <div class="header-left">
            <nav class="main-navigation">
                <?php foreach ($navItems as $navPage => $navConfig): ?>
                    <?php
                    $route = ($navPage === 'calendar') ? '#/' : '#/' . $navPage;
                    ?>
                    <a href="<?php echo $route; ?>" 
                       data-route="<?php echo ($navPage === 'calendar') ? '/' : '/' . $navPage; ?>"
                       class="nav-link <?php echo ($navPage === 'calendar') ? 'active' : ''; ?>">
                        <i data-lucide="<?php echo $navConfig['icon']; ?>"></i> <?php echo $navConfig['label']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="user-section">
            <label for="userName">
                Logged in as monsieur
            </label>
            <input type="text" 
                   id="userName" 
                   value="<?php echo htmlspecialchars($currentUser['name'] ?? 'Loading...'); ?>" 
                   disabled />
            <button id="logoutBtn" class="btn btn-small btn-outline" onclick="handleLogout()">
                Logout
            </button>
        </div>
    </div>
</header>

<script>
async function handleLogout() {
    // Check if ModalManager is available (from the modular system)
    if (window.ModalManager && window.ModalManager.confirm) {
        const confirmed = await window.ModalManager.confirm({
            title: 'Confirm Logout',
            message: 'Are you sure you want to logout?',
            confirmText: 'Logout',
            cancelText: 'Cancel',
            confirmClass: 'btn-danger'
        });
        
        if (!confirmed) return;
    } else {
        // Fallback to browser confirm if modal system not available
        if (!confirm('Are you sure you want to logout?')) return;
    }
    
    // Proceed with logout
    fetch('backend/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'logout' })
    })
    .then(() => {
        window.location.href = 'frontend/pages/login.php';
    })
    .catch(error => {
        console.error('Logout error:', error);
        window.location.href = 'frontend/pages/login.php';
    });
}

// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>