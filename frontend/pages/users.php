<?php
// Users page - User management and overview
// This page is included by index.php, so all variables and authentication are already available
?>

<div class="page-header">
    <h2>User Management</h2>
    <div class="header-actions">
        <button class="btn btn-primary" id="addUserBtn">
            <span class="btn-icon">+</span>
            Add User
        </button>
    </div>
</div>

<div class="search-section">
    <div class="search-box">
        <input type="text" id="userSearch" placeholder="Search users by name or email..." class="search-input">
        <button class="btn btn-small btn-outline" id="searchBtn">Search</button>
    </div>
</div>

<div class="table-container">
    <table id="usersTable" class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Calendar Color</th>
                <th>Events Count</th>
                <th>Last Activity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="usersTableBody">
            <tr>
                <td colspan="7" class="loading-cell">Loading users...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Add User</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="userForm">
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
                    <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>