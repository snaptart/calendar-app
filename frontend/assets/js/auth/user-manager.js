/**
 * Manages user operations and selection with authentication
 * Location: frontend/js/auth/user-manager.js
 */
import { APIClient } from '../core/api-client.js';
import { EventBus } from '../core/event-bus.js';
import { AuthGuard } from './auth-guard.js';

export const UserManager = (() => {
    let allUsers = [];
    let selectedUserIds = new Set();
    
    const renderUserCheckboxes = () => {
        const container = document.getElementById('userCheckboxes');
        if (!container) return;
        
        container.innerHTML = '';
        
        allUsers.forEach(user => {
            const checkboxItem = document.createElement('div');
            checkboxItem.className = 'checkbox-item';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `user-${user.id}`;
            checkbox.value = user.id;
            checkbox.checked = selectedUserIds.has(user.id.toString());
            
            const colorDot = document.createElement('div');
            colorDot.className = 'user-color';
            colorDot.style.backgroundColor = user.color;
            
            const label = document.createElement('label');
            label.htmlFor = `user-${user.id}`;
            label.textContent = user.name;
            label.style.cursor = 'pointer';
            
            checkboxItem.appendChild(checkbox);
            checkboxItem.appendChild(colorDot);
            checkboxItem.appendChild(label);
            
            if (checkbox.checked) {
                checkboxItem.classList.add('checked');
            }
            
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    selectedUserIds.add(user.id.toString());
                    checkboxItem.classList.add('checked');
                } else {
                    selectedUserIds.delete(user.id.toString());
                    checkboxItem.classList.remove('checked');
                }
                
                EventBus.emit('users:selectionChanged', {
                    selectedUserIds: Array.from(selectedUserIds)
                });
            });
            
            container.appendChild(checkboxItem);
        });
    };
    
    const loadUsers = async () => {
        try {
            const users = await APIClient.getUsers();
            allUsers = users;
            
            // Auto-select current user's calendar
            const currentUser = AuthGuard.getCurrentUser();
            if (currentUser) {
                selectedUserIds.add(currentUser.id.toString());
            }
            
            renderUserCheckboxes();
            
            EventBus.emit('users:loaded', { users });
            
            // Load events after users are loaded and current user is selected
            if (currentUser) {
                EventBus.emit('users:selectionChanged', {
                    selectedUserIds: Array.from(selectedUserIds)
                });
            }
        } catch (error) {
            console.error('Error loading users:', error);
            EventBus.emit('users:error', { error });
        }
    };
    
    const getCurrentUser = () => AuthGuard.getCurrentUser();
    const getAllUsers = () => allUsers;
    const getSelectedUserIds = () => Array.from(selectedUserIds);
    
    const canUserEditEvent = (event) => {
        const currentUser = getCurrentUser();
        return currentUser && event.extendedProps.userId === currentUser.id;
    };
    
    // Event listeners
    EventBus.on('app:init', loadUsers);
    EventBus.on('auth:authenticated', ({ user }) => {
        // User name is already set server-side in header.php - no need to update
        EventBus.emit('user:set', { user });
    });
    
    return {
        getCurrentUser,
        getAllUsers,
        getSelectedUserIds,
        canUserEditEvent,
        loadUsers
    };
})();