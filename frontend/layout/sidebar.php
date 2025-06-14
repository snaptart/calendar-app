<?php
// Sidebar content is always dynamically populated by JavaScript
// This ensures consistent behavior between initial loads and SPA navigation
?>

<div id="sidebar-content">
    <!-- Content will be dynamically populated based on current page -->
</div>

<script>
// Function to update sidebar content based on current page
function updateSidebarContent(pageName) {
    const sidebarContent = document.getElementById('sidebar-content');
    if (!sidebarContent) return;
    
    if (pageName === 'events' || pageName === 'users' || pageName === 'import') {
        // Empty content with placeholder for events, users, and import pages
        sidebarContent.innerHTML = `
            <div class="sidebar-placeholder">
                <p>Sidebar content for ${pageName} page will be added here in the future.</p>
            </div>
        `;
    } else {
        // Default content for calendar and other pages
        sidebarContent.innerHTML = `
            <div class="user-filters">
                <h3>Show Calendars</h3>
                <div id="userCheckboxes" class="checkbox-group">
                    <!-- User checkboxes will be populated dynamically by JavaScript -->
                    <div class="loading-message">Loading users...</div>
                </div>
                <button id="refreshUsers" class="btn btn-small btn-outline">
                    <i data-lucide="refresh-cw"></i> Refresh Users
                </button>
            </div>

            <div class="calendar-actions">
                <button id="addEventBtn" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Add Event
                </button>
                <button id="viewEventsBtn" class="btn btn-outline" data-route="/events">
                    <i data-lucide="list-checks"></i>
                    View All Events
                </button>
                <button id="importEventsBtn" class="btn btn-outline" data-route="/import">
                    <i data-lucide="download"></i>
                    Import Events
                </button>
                <div class="connection-status">
                    <span id="connectionStatus" class="status disconnected">Connecting...</span>
                </div>
            </div>
        `;
        
        // Re-initialize Lucide icons after content update
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Listen for page changes
document.addEventListener('module:events:init', () => {
    updateSidebarContent('events');
});

document.addEventListener('module:calendar:init', () => {
    updateSidebarContent('calendar');
});

document.addEventListener('module:users:init', () => {
    updateSidebarContent('users');
});

document.addEventListener('module:import:init', () => {
    updateSidebarContent('import');
});

// Initial update - determine page from URL
document.addEventListener('DOMContentLoaded', () => {
    // Get the current page from the URL hash
    const hash = window.location.hash.substring(1); // Remove the #
    const path = hash.startsWith('/') ? hash.substring(1) : hash; // Remove leading /
    const currentPage = path || 'calendar'; // Default to calendar if no path
    
    updateSidebarContent(currentPage);
});
</script>