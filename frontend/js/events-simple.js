/**
 * Simplified Events Management Page
 * Location: frontend/js/events-simple.js
 * 
 * A working version without complex module dependencies
 */

// Simple authentication check
const checkAuth = async () => {
    try {
        const response = await fetch('backend/api.php?action=check_auth', {
            credentials: 'include'
        });
        const result = await response.json();
        return result.authenticated;
    } catch (error) {
        console.error('Auth check failed:', error);
        return false;
    }
};

// Simple API client
const apiCall = async (action, data = {}) => {
    try {
        const url = data.method === 'GET' 
            ? `backend/api.php?action=${action}&${new URLSearchParams(data.params || {})}`
            : 'backend/api.php';
            
        const options = {
            method: data.method || 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data.method !== 'GET') {
            options.body = JSON.stringify({ action, ...data });
        }
        
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
};

// Simple notification system
const showNotification = (message, type = 'info') => {
    console.log(`[${type.toUpperCase()}] ${message}`);
    if (type === 'error') {
        alert(`Error: ${message}`);
    } else if (type === 'success') {
        alert(`Success: ${message}`);
    }
};

// DataTables manager
let eventsTable = null;

const initializeDataTable = () => {
    if (eventsTable) {
        eventsTable.destroy();
    }
    
    eventsTable = $('#eventsTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'backend/api.php',
            type: 'GET',
            data: function(d) {
                d.action = 'events_datatable';
                d.draw = d.draw;
                d.start = d.start;
                d.length = d.length;
                d.search = d.search.value;
                
                // Add filters
                const filters = {
                    view: $('#eventViewFilter').val() || 'all',
                    status: $('#eventStatusFilter').val() || '',
                    user: $('#eventUserFilter').val() || ''
                };
                d.filters = JSON.stringify(filters);
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                showNotification('Failed to load events data', 'error');
            }
        },
        columns: [
            { data: 'title', title: 'Title' },
            { 
                data: 'start', 
                title: 'Start',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { 
                data: 'end', 
                title: 'End',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            { 
                data: null, 
                title: 'Duration',
                render: function(data) {
                    const start = new Date(data.start);
                    const end = new Date(data.end);
                    const diff = end - start;
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    return `${hours}h ${minutes}m`;
                }
            },
            { data: 'owner_name', title: 'Owner' },
            { 
                data: null, 
                title: 'Status',
                render: function(data) {
                    const now = new Date();
                    const start = new Date(data.start);
                    const end = new Date(data.end);
                    
                    let status, badgeClass;
                    if (now < start) {
                        status = 'upcoming';
                        badgeClass = 'badge-primary';
                    } else if (now >= start && now <= end) {
                        status = 'ongoing';
                        badgeClass = 'badge-success';
                    } else {
                        status = 'past';
                        badgeClass = 'badge-secondary';
                    }
                    
                    return `<span class="badge ${badgeClass}">${status}</span>`;
                }
            },
            {
                data: null,
                title: 'Actions',
                orderable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-outline-primary" onclick="viewEvent('${data.id}')">
                            👁️ View
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editEvent('${data.id}')">
                            ✏️ Edit
                        </button>
                    `;
                }
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            'excel', 'pdf', 'print'
        ],
        pageLength: 25,
        order: [[1, 'desc']],
        responsive: true
    });
};

// Load users for filter
const loadUsers = async () => {
    try {
        const result = await apiCall('users', { method: 'GET' });
        if (result.success) {
            const userSelect = $('#eventUserFilter');
            userSelect.empty().append('<option value="">All Users</option>');
            
            result.users.forEach(user => {
                userSelect.append(`<option value="${user.id}">${user.name || user.email}</option>`);
            });
        }
    } catch (error) {
        console.error('Failed to load users:', error);
    }
};

// Event handlers
const setupEventHandlers = () => {
    // Filter change handlers
    $('#eventViewFilter, #eventStatusFilter, #eventUserFilter').on('change', function() {
        if (eventsTable) {
            eventsTable.ajax.reload();
        }
    });
    
    // Button handlers
    $('#addEventBtn').on('click', function() {
        alert('Add event functionality would go here');
    });
    
    $('#refreshEventsBtn').on('click', function() {
        if (eventsTable) {
            eventsTable.ajax.reload();
        }
    });
};

// Global functions for action buttons
window.viewEvent = function(eventId) {
    alert(`View event ${eventId} - functionality would go here`);
};

window.editEvent = function(eventId) {
    alert(`Edit event ${eventId} - functionality would go here`);
};

// Initialize the page
const initEventsPage = async () => {
    console.log('Initializing Events page...');
    
    // Check authentication
    const isAuthenticated = await checkAuth();
    if (!isAuthenticated) {
        window.location.href = 'login.php';
        return;
    }
    
    // Initialize components
    initializeDataTable();
    setupEventHandlers();
    await loadUsers();
    
    console.log('Events page initialized successfully');
};

// Start when DOM is ready
$(document).ready(function() {
    initEventsPage();
});