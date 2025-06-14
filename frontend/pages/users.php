<?php
// Users page - User management and overview
// This page is included by index.php, so all variables and authentication are already available

// Include the shared data section header component
require_once __DIR__ . '/../components/data-section-header.php';

// Calculate user stats server-side
function getUserStats($pdo) {
    try {
        // Total users
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        
        // Active users (users with events in last 30 days)
        $activeUsers = $pdo->query("
            SELECT COUNT(DISTINCT u.id) 
            FROM user u 
            JOIN episode e ON u.id = e.user_id 
            WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchColumn();
        
        // New users (registered in last 7 days)
        $newUsers = $pdo->query("
            SELECT COUNT(*) 
            FROM user 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();
        
        // Admin users
        $adminUsers = $pdo->query("
            SELECT COUNT(*) 
            FROM user 
            WHERE role = 'Admin'
        ")->fetchColumn();
        
        return [
            'total' => $totalUsers ?: 0,
            'active' => $activeUsers ?: 0,
            'new' => $newUsers ?: 0,
            'admin' => $adminUsers ?: 0
        ];
    } catch (Exception $e) {
        error_log("Error calculating user stats: " . $e->getMessage());
        return ['total' => 0, 'active' => 0, 'new' => 0, 'admin' => 0];
    }
}

$userStats = getUserStats($pdo);
?>

<script>
// Real-time user statistics updates
document.addEventListener('DOMContentLoaded', function() {
    // Function to update user stats in real-time
    const updateUserStats = async () => {
        try {
            const response = await fetch('backend/api.php?action=users_with_stats');
            const data = await response.json();
            
            if (data.success && data.stats) {
                document.querySelector('.stat-item:nth-child(1) .stat-number').textContent = data.stats.total || 0;
                document.querySelector('.stat-item:nth-child(2) .stat-number').textContent = data.stats.active || 0;
                document.querySelector('.stat-item:nth-child(3) .stat-number').textContent = data.stats.new || 0;
                document.querySelector('.stat-item:nth-child(4) .stat-number').textContent = data.stats.admin || 0;
            }
        } catch (error) {
            console.error('Error updating user stats:', error);
        }
    };
    
    // Listen for user-related events to update stats
    if (window.EventBus) {
        EventBus.on('users:refresh', updateUserStats);
        EventBus.on('users:updated', updateUserStats);
        EventBus.on('user:created', updateUserStats);
        EventBus.on('user:deleted', updateUserStats);
    }
});
</script>

<div class="stats-section" style="display: none;">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?php echo $userStats['total']; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $userStats['active']; ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $userStats['new']; ?></div>
            <div class="stat-label">New Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $userStats['admin']; ?></div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>
</div>

<?php renderUsersPageHeader(); ?>

<div class="table-container">
    <table id="usersTable" class="display">
        <thead>
            <tr>
                <th>Color</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Events Count</th>
                <th>Member Since</th>
                <th>Last Activity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="usersTableBody">
            <!-- Data will be populated by JavaScript -->
        </tbody>
    </table>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">User Details</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <!-- User Details View -->
            <div id="userDetails">
                <!-- Content populated by JavaScript -->
            </div>
            
            <!-- User Edit Form -->
            <div id="userForm" style="display: none;">
                <form id="editUserForm">
                    <input type="hidden" id="userId">
                    <div class="form-group">
                        <label for="editUserName">Name</label>
                        <input type="text" id="editUserName" placeholder="Enter user's full name" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email</label>
                        <input type="email" id="userEmail" placeholder="Enter user's email" required>
                    </div>
                    <div class="form-group" id="passwordGroup">
                        <label for="userPassword">Password</label>
                        <input type="password" id="userPassword" placeholder="Enter password (min 6 characters)" autocomplete="new-password">
                        <small class="form-hint">Leave blank to keep existing password</small>
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role</label>
                        <select id="userRole" required>
                            <option value="">Select a role</option>
                            <option value="Calendar User">Calendar User</option>
                            <option value="Admin">Administrator</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="userColor">Calendar Color</label>
                        <div class="color-picker">
                            <input type="color" id="userColor" value="#3788d8">
                            <span class="color-preview" id="colorPreview"></span>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save User</button>
                        <button type="button" class="btn btn-outline" id="cancelEditBtn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>