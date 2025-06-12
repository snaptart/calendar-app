/**
 * Users Management Page - Refactored to use modular components
 * Location: frontend/js/users.js
 * 
 * This file has been refactored to eliminate code duplication by using
 * the existing modular components instead of recreating them.
 */

// Import modular components
import { EventBus } from './core/event-bus.js';
import { Config } from './core/config.js';
import { Utils } from './core/utils.js';
import { APIClient } from './core/api-client.js';
import { AuthGuard } from './auth/auth-guard.js';
import { UserManager } from './auth/user-manager.js';
import { UIManager } from './ui/ui-manager.js';
import { ModalManager } from './ui/modal-manager.js';
import { SSEManager } from './realtime/sse-manager.js';

// =============================================================================
// PAGE-SPECIFIC COMPONENTS
// =============================================================================

/**
 * Users data management with filtering and caching
 */
const UsersDataManager = (() => {
    let usersData = [];
    let lastFetchTime = 0;
    let currentFilters = {
        status: '',
        role: '',
        search: ''
    };
    
    const loadUsersData = async () => {
        try {
            EventBus.emit('users:loading');
            
            console.log('Loading users data...');
            
            // Use the modular APIClient
            const users = await APIClient.getUsers();
            console.log('Successfully loaded users:', users);
            
            if (!Array.isArray(users)) {
                throw new Error('Invalid users data format received from server');
            }
            
            usersData = users.map(user => ({
                ...user,
                status: user.status || 'active',
                role: user.role || 'user',
                created_at: user.created_at || 'Unknown',
                last_login: user.last_login || 'Never'
            }));
            
            lastFetchTime = Date.now();
            
            EventBus.emit('users:loaded', { users: usersData });
            return usersData;
            
        } catch (error) {
            console.error('Error loading users data:', error);
            EventBus.emit('users:error', { error });
            throw error;
        }
    };
    
    const getFilteredUsers = () => {
        let filtered = [...usersData];
        
        // Apply filters
        if (currentFilters.status) {
            filtered = filtered.filter(user => 
                user.status === currentFilters.status
            );
        }
        
        if (currentFilters.role) {
            filtered = filtered.filter(user => 
                user.role === currentFilters.role
            );
        }
        
        if (currentFilters.search) {
            const searchTerm = currentFilters.search.toLowerCase();
            filtered = filtered.filter(user => 
                (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                (user.email && user.email.toLowerCase().includes(searchTerm))
            );
        }
        
        return filtered;
    };
    
    const updateFilters = (filters) => {
        currentFilters = { ...currentFilters, ...filters };
        EventBus.emit('users:filters-changed', { filters: currentFilters });
    };
    
    const refreshData = async () => {
        await loadUsersData();
    };
    
    const getUserById = (userId) => {
        return usersData.find(user => user.id == userId);
    };
    
    return {
        loadUsersData,
        getFilteredUsers,
        updateFilters,
        refreshData,
        getUserById,
        getUsersData: () => [...usersData],
        getCurrentFilters: () => ({ ...currentFilters })
    };
})();

/**
 * DataTables management for users
 */
const DataTablesManager = (() => {
    let dataTable = null;
    
    const initializeDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
        }
        
        dataTable = $('#usersTable').DataTable({
            data: [],
            columns: [
                { 
                    data: 'name', 
                    title: 'Name',
                    render: (data, type, row) => data || row.email || 'N/A'
                },
                { data: 'email', title: 'Email' },
                { 
                    data: 'role', 
                    title: 'Role',
                    render: (data) => {
                        const roleClass = data === 'admin' ? 'badge-danger' : 
                                         data === 'manager' ? 'badge-warning' : 'badge-secondary';
                        return `<span class="badge ${roleClass}">${data || 'user'}</span>`;
                    }
                },
                { 
                    data: 'status', 
                    title: 'Status',
                    render: (data) => {
                        const statusClass = data === 'active' ? 'badge-success' : 'badge-secondary';
                        return `<span class="badge ${statusClass}">${data || 'active'}</span>`;
                    }
                },
                { 
                    data: 'created_at', 
                    title: 'Created',
                    render: (data) => Utils.formatDateOnly(data)
                },
                { 
                    data: 'last_login', 
                    title: 'Last Login',
                    render: (data) => data === 'Never' ? data : Utils.formatDateTime(data)
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-outline-primary view-user-btn" data-user-id="${row.id}">
                            👁️ View
                        </button>
                        <button class="btn btn-sm btn-outline-secondary edit-user-btn" data-user-id="${row.id}">
                            ✏️ Edit
                        </button>
                    `
                }
            ],
            pageLength: 25,
            order: [[0, 'asc']],
            responsive: true,
            searching: true,
            paging: true,
            info: true
        });
        
        return dataTable;
    };
    
    const loadData = (users) => {
        if (dataTable) {
            dataTable.clear().rows.add(users).draw();
        }
    };
    
    const refreshTable = () => {
        const filteredUsers = UsersDataManager.getFilteredUsers();
        loadData(filteredUsers);
    };
    
    const getDataTable = () => dataTable;
    
    return {
        initializeDataTable,
        loadData,
        refreshTable,
        getDataTable
    };
})();

/**
 * Filter management for users
 */
const FilterManager = (() => {
    const initializeFilters = () => {
        // Status filter
        $('#statusFilter').on('change', function() {
            UsersDataManager.updateFilters({ status: this.value });
            DataTablesManager.refreshTable();
        });
        
        // Role filter
        $('#roleFilter').on('change', function() {
            UsersDataManager.updateFilters({ role: this.value });
            DataTablesManager.refreshTable();
        });
        
        // Search filter with debounce
        let searchTimeout;
        $('#searchFilter').on('input', function() {
            const searchTerm = this.value;
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                UsersDataManager.updateFilters({ search: searchTerm });
                DataTablesManager.refreshTable();
            }, 300);
        });
    };
    
    const resetFilters = () => {
        $('#statusFilter').val('');
        $('#roleFilter').val('');
        $('#searchFilter').val('');
        UsersDataManager.updateFilters({ status: '', role: '', search: '' });
        DataTablesManager.refreshTable();
    };
    
    return {
        initializeFilters,
        resetFilters
    };
})();

/**
 * User modal management
 */
const UserModalManager = (() => {
    let currentUserId = null;
    let currentMode = 'view';
    
    const openModal = async (userId, mode = 'view') => {
        currentUserId = userId;
        currentMode = mode;
        
        try {
            if (userId && mode !== 'create') {
                const user = UsersDataManager.getUserById(userId);
                
                if (user) {
                    showUserModal(user, mode);
                } else {
                    UIManager.showError('User not found');
                }
            } else {
                showUserModal(null, mode);
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            UIManager.showError('Failed to load user data');
        }
    };
    
    const showUserModal = (user, mode) => {
        const modal = ModalManager.create({
            title: mode === 'create' ? 'Create User' : mode === 'edit' ? 'Edit User' : 'View User',
            size: 'large',
            body: generateModalContent(user, mode),
            footer: generateModalFooter(mode)
        });
        
        modal.show();
    };
    
    const generateModalContent = (user, mode) => {
        const isReadonly = mode === 'view';
        const name = user?.name || '';
        const email = user?.email || '';
        const role = user?.role || 'user';
        const status = user?.status || 'active';
        const password = '';
        
        return `
            <form id="userForm">
                <div class="form-group">
                    <label for="userName">Name</label>
                    <input type="text" class="form-control" id="userName" value="${name}" ${isReadonly ? 'readonly' : ''} required>
                </div>
                <div class="form-group">
                    <label for="userEmail">Email</label>
                    <input type="email" class="form-control" id="userEmail" value="${email}" ${isReadonly ? 'readonly' : ''} required>
                </div>
                <div class="form-group">
                    <label for="userRole">Role</label>
                    <select class="form-control" id="userRole" ${isReadonly ? 'disabled' : ''}>
                        <option value="user" ${role === 'user' ? 'selected' : ''}>User</option>
                        <option value="manager" ${role === 'manager' ? 'selected' : ''}>Manager</option>
                        <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="userStatus">Status</label>
                    <select class="form-control" id="userStatus" ${isReadonly ? 'disabled' : ''}>
                        <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
                ${mode === 'create' ? `
                    <div class="form-group">
                        <label for="userPassword">Password</label>
                        <input type="password" class="form-control" id="userPassword" value="${password}" required>
                    </div>
                ` : ''}
                ${user ? `
                    <div class="form-group">
                        <label>Created</label>
                        <span class="form-control-plaintext">${Utils.formatDateTime(user.created_at)}</span>
                    </div>
                    <div class="form-group">
                        <label>Last Login</label>
                        <span class="form-control-plaintext">${user.last_login === 'Never' ? 'Never' : Utils.formatDateTime(user.last_login)}</span>
                    </div>
                ` : ''}
            </form>
        `;
    };
    
    const generateModalFooter = (mode) => {
        if (mode === 'view') {
            return `
                <button class="btn btn-primary" onclick="UserModalManager.switchToEditMode()">✏️ Edit User</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Close</button>
            `;
        } else if (mode === 'edit') {
            return `
                <button class="btn btn-success" onclick="UserModalManager.saveUser()">💾 Save Changes</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Cancel</button>
            `;
        } else {
            return `
                <button class="btn btn-success" onclick="UserModalManager.createUser()">➕ Create User</button>
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Cancel</button>
            `;
        }
    };
    
    const switchToEditMode = () => {
        if (currentUserId) {
            openModal(currentUserId, 'edit');
        }
    };
    
    const saveUser = async () => {
        try {
            const formData = getFormData();
            if (!formData) return;
            
            const userData = {
                ...formData,
                id: currentUserId,
                action: 'update_user'
            };
            
            // Note: This would require implementing user update in APIClient
            await fetch(Config.apiEndpoints.api, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(userData)
            });
            
            ModalManager.closeActive();
            await UsersDataManager.refreshData();
            DataTablesManager.refreshTable();
            UIManager.showSuccess('User updated successfully');
            
        } catch (error) {
            console.error('Error saving user:', error);
            UIManager.showError('Failed to save user: ' + error.message);
        }
    };
    
    const createUser = async () => {
        try {
            const formData = getFormData(true);
            if (!formData) return;
            
            const userData = {
                ...formData,
                action: 'create_user'
            };
            
            // Note: This would require implementing user creation in APIClient
            await fetch(Config.apiEndpoints.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(userData)
            });
            
            ModalManager.closeActive();
            await UsersDataManager.refreshData();
            DataTablesManager.refreshTable();
            UIManager.showSuccess('User created successfully');
            
        } catch (error) {
            console.error('Error creating user:', error);
            UIManager.showError('Failed to create user: ' + error.message);
        }
    };
    
    const getFormData = (includePassword = false) => {
        const name = $('#userName').val().trim();
        const email = $('#userEmail').val().trim();
        const role = $('#userRole').val();
        const status = $('#userStatus').val();
        const password = includePassword ? $('#userPassword').val() : undefined;
        
        if (!name || !email) {
            UIManager.showError('Please fill in all required fields');
            return null;
        }
        
        if (!email.includes('@')) {
            UIManager.showError('Please enter a valid email address');
            return null;
        }
        
        if (includePassword && (!password || password.length < 6)) {
            UIManager.showError('Password must be at least 6 characters long');
            return null;
        }
        
        const formData = {
            name,
            email,
            role,
            status
        };
        
        if (includePassword) {
            formData.password = password;
        }
        
        return formData;
    };
    
    // Make functions available globally for onclick handlers
    window.UserModalManager = {
        openModal,
        switchToEditMode,
        saveUser,
        createUser
    };
    
    return {
        openModal,
        switchToEditMode,
        saveUser,
        createUser
    };
})();

/**
 * Main users application controller
 */
const UsersApp = (() => {
    const setupEventListeners = () => {
        // Data refresh events
        EventBus.on('users:refresh', () => {
            console.log('Event: users:refresh triggered');
            DataTablesManager.refreshTable();
        });
        
        // UI events
        EventBus.on('users:loading', () => {
            UIManager.showLoadingOverlay();
        });
        
        EventBus.on('users:loaded', () => {
            UIManager.hideLoadingOverlay();
        });
        
        EventBus.on('users:error', ({ error }) => {
            UIManager.hideLoadingOverlay();
            UIManager.showError(error.message || 'Failed to load users data');
        });
        
        // Filter change events
        EventBus.on('users:filters-changed', () => {
            DataTablesManager.refreshTable();
        });
        
        // Refresh button
        const refreshBtn = document.getElementById('refreshUsersBtn');
        refreshBtn?.addEventListener('click', async () => {
            try {
                console.log('Manual refresh triggered');
                await UsersDataManager.refreshData();
                
                // Show success feedback
                const originalText = refreshBtn.textContent;
                refreshBtn.textContent = '✓ Refreshed';
                refreshBtn.style.background = '#48bb78';
                refreshBtn.style.color = 'white';
                
                setTimeout(() => {
                    refreshBtn.textContent = originalText;
                    refreshBtn.style.background = '';
                    refreshBtn.style.color = '';
                }, 2000);
                
            } catch (error) {
                console.error('Error refreshing data:', error);
                UIManager.showError('Failed to refresh data. Please try again.');
            }
        });
        
        // Add user button
        const addUserBtn = document.getElementById('addUserBtn');
        addUserBtn?.addEventListener('click', () => {
            UserModalManager.openModal(null, 'create');
        });
        
        // Table row action handlers
        $(document).on('click', '.view-user-btn', function() {
            const userId = $(this).data('user-id');
            UserModalManager.openModal(userId, 'view');
        });
        
        $(document).on('click', '.edit-user-btn', function() {
            const userId = $(this).data('user-id');
            UserModalManager.openModal(userId, 'edit');
        });
    };
    
    const init = async () => {
        console.log('Initializing Users Management Page...');
        
        // Check authentication first using modular AuthGuard
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            return; // AuthGuard will handle redirect
        }
        
        // Initialize components
        DataTablesManager.initializeDataTable();
        FilterManager.initializeFilters();
        UserModalManager; // Initialize the modal manager
        setupEventListeners();
        
        // Load initial data
        await UsersDataManager.loadUsersData();
        
        // Start SSE connection using modular SSEManager
        SSEManager.connect();
        
        console.log('Users Management Page initialized successfully');
    };
    
    const destroy = () => {
        SSEManager.disconnect();
        
        if (DataTablesManager.getDataTable()) {
            DataTablesManager.getDataTable().destroy();
        }
        
        console.log('Users Management Page destroyed');
    };
    
    return {
        init,
        destroy
    };
})();

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Users app...');
    UsersApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    UsersApp.destroy();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        console.log('Page loaded from cache, checking auth...');
        AuthGuard.checkAuthentication();
    }
});