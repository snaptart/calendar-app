<?php
// Users page - User management and overview
// This page is included by index.php, so all variables and authentication are already available
?>

<div class="stats-section" style="display: none;">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number" id="totalUsersCount">0</div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="activeUsersCount">0</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="newUsersCount">0</div>
            <div class="stat-label">New Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="adminUsersCount">0</div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>
</div>

<div class="calendar-events-section">
    <div class="section-header">
        <div class="section-info">
            <h3>User Management</h3>
            <p class="section-description">View and manage system users with advanced filtering and search</p>
        </div>
        <div class="section-controls">
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="userStatusFilter">STATUS:</label>
                    <select id="userStatusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="recent">Recent</option>
                        <option value="inactive">Inactive</option>
                        <option value="new">New</option>
                        <option value="guest">Guest</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="userRoleFilter">ROLE:</label>
                    <select id="userRoleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="Calendar User">Calendar User</option>
                        <option value="Admin">Administrator</option>
                        <option value="Manager">Manager</option>
                    </select>
                </div>
                <div class="action-buttons">
                    <button id="refreshUsersBtn" class="btn btn-outline">
                        🔄 Refresh Data
                    </button>
                    <button id="addUserBtn" class="btn btn-primary">
                        + Add User
                    </button>
                </div>
                <div class="connection-status">
                    <span id="connectionStatus" class="status">Connected</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <table id="usersTable" class="data-table">
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
                        <label for="userName">Name</label>
                        <input type="text" id="userName" placeholder="Enter user's full name" required>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email</label>
                        <input type="email" id="userEmail" placeholder="Enter user's email" required>
                    </div>
                    <div class="form-group" id="passwordGroup">
                        <label for="userPassword">Password</label>
                        <input type="password" id="userPassword" placeholder="Enter password (min 6 characters)">
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