/**
 * Users Page Configuration for CrudPageFactory
 * Location: frontend/js/config/users-page-config.js
 */

import { Utils } from '../core/utils.js';
import { APIClient } from '../core/api-client.js';

export const UsersPageConfig = {
    entityType: 'users',
    apiAction: 'users_with_stats',
    tableId: 'usersTable',
    
    columns: [
        { 
            data: 'name', 
            title: 'Name',
            name: 'name',
            render: (data, type, row) => data || row.email || 'N/A'
        },
        { 
            data: 'email', 
            title: 'Email',
            name: 'email'
        },
        { 
            data: 'role', 
            title: 'Role',
            name: 'role',
            render: (data) => {
                const roleClass = data === 'admin' ? 'badge-danger' : 
                                 data === 'manager' ? 'badge-warning' : 'badge-secondary';
                return `<span class="badge ${roleClass}">${data || 'user'}</span>`;
            }
        },
        { 
            data: 'status', 
            title: 'Status',
            name: 'status',
            render: (data) => {
                const statusClass = data === 'active' ? 'badge-success' : 'badge-secondary';
                return `<span class="badge ${statusClass}">${data || 'active'}</span>`;
            }
        },
        { 
            data: 'created_at', 
            title: 'Created',
            name: 'created',
            render: (data) => Utils.formatDateOnly(data)
        },
        { 
            data: 'last_login', 
            title: 'Last Login',
            name: 'last_login',
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
    
    defaultOrder: [[0, 'asc']],
    
    filters: {
        defaults: {
            status: '',
            role: '',
            search: ''
        },
        elements: {
            status: 'userStatusFilter',
            role: 'userRoleFilter',
            search: 'searchFilter',
            refresh: 'refreshUsersBtn',
            create: 'addUserBtn'
        }
    },
    
    api: {
        loadData: 'getUsersWithStats'
    },
    
    dataTransform: (user) => ({
        ...user,
        name: user.name || user.email || 'N/A',
        role: user.role || 'user',
        status: user.status || 'active',
        created_at: user.created_at,
        last_login: user.last_login || 'Never'
    }),
    
    customHandlers: {
        onButtonClick: (data) => {
            const { button, rowData } = data;
            const action = button.getAttribute('class').includes('view-user-btn') ? 'view' : 'edit';
            
            if (action === 'view') {
                // Handle view user
                window.ModalManager?.openModal('users', rowData, 'view');
            } else if (action === 'edit') {
                // Handle edit user
                window.ModalManager?.openModal('users', rowData, 'edit');
            }
        }
    }
};