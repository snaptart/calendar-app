/**
 * Shared DataTable Manager for reusable table functionality
 * Location: frontend/js/ui/table-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Config } from '../core/config.js';
import { Utils } from '../core/utils.js';

export const TableManager = (() => {
    const instances = new Map();
    
    /**
     * Create a new DataTable instance with standard configuration
     */
    const create = (tableId, options = {}) => {
        const defaultOptions = {
            pageLength: 5,
            responsive: true,
            searching: true,
            paging: true,
            info: true,
            order: [[0, 'asc']],
            language: {
                emptyTable: "No data available",
                processing: "Loading...",
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _TOTAL_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        };
        
        // Merge options
        const config = {
            ...defaultOptions,
            ...options,
            // Ensure columns are properly configured
            columns: options.columns || [],
            // Merge language options
            language: {
                ...defaultOptions.language,
                ...(options.language || {})
            }
        };
        
        // Destroy existing instance if it exists
        if (instances.has(tableId)) {
            destroy(tableId);
        }
        
        // Create new DataTable
        const table = $(`#${tableId}`).DataTable(config);
        instances.set(tableId, {
            table,
            config,
            tableId
        });
        
        // Set up event listeners
        setupTableEvents(tableId, table);
        
        return table;
    };
    
    /**
     * Create a server-side DataTable for large datasets
     */
    const createServerSide = (tableId, options = {}) => {
        const serverOptions = {
            serverSide: true,
            processing: true,
            ajax: {
                url: options.ajaxUrl || Config.apiEndpoints.api,
                type: 'GET',
                data: function(d) {
                    // Standard DataTables parameters
                    const params = {
                        action: options.action || 'get_data',
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        search: d.search.value,
                        order_column: d.columns[d.order[0].column].data,
                        order_dir: d.order[0].dir
                    };
                    
                    // Add custom parameters if provided
                    if (options.extraParams) {
                        Object.assign(params, options.extraParams(d));
                    }
                    
                    return params;
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                    EventBus.emit('table:error', {
                        tableId,
                        error: 'Failed to load data',
                        details: { xhr, error, thrown }
                    });
                }
            },
            ...options
        };
        
        return create(tableId, serverOptions);
    };
    
    /**
     * Set up common table event listeners
     */
    const setupTableEvents = (tableId, table) => {
        // Row click events
        $(`#${tableId} tbody`).off('click', 'tr').on('click', 'tr', function() {
            const data = table.row(this).data();
            if (data) {
                EventBus.emit('table:row-click', {
                    tableId,
                    rowData: data,
                    rowElement: this
                });
            }
        });
        
        // Button click events within table
        $(`#${tableId} tbody`).off('click', 'button').on('click', 'button', function(e) {
            e.stopPropagation(); // Prevent row click
            
            const data = table.row($(this).closest('tr')).data();
            const action = $(this).data('action') || $(this).attr('onclick');
            
            EventBus.emit('table:button-click', {
                tableId,
                button: this,
                action,
                rowData: data
            });
        });
        
        // Search events with debouncing
        let searchTimeout;
        $(`#${tableId}_filter input`).off('input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                EventBus.emit('table:search', {
                    tableId,
                    searchTerm: this.value
                });
            }, 300);
        });
        
        // Page change events
        table.off('page.dt').on('page.dt', function() {
            EventBus.emit('table:page-change', {
                tableId,
                pageInfo: table.page.info()
            });
        });
        
        // Draw events (after data load/refresh)
        table.off('draw.dt').on('draw.dt', function() {
            EventBus.emit('table:draw', {
                tableId,
                data: table.data().toArray()
            });
        });
    };
    
    /**
     * Load data into a client-side table
     */
    const loadData = (tableId, data) => {
        const instance = instances.get(tableId);
        if (instance && instance.table) {
            instance.table.clear().rows.add(data).draw();
            
            EventBus.emit('table:data-loaded', {
                tableId,
                recordCount: data.length
            });
        }
    };
    
    /**
     * Refresh table data
     */
    const refresh = (tableId) => {
        const instance = instances.get(tableId);
        if (instance && instance.table) {
            if (instance.config.serverSide) {
                instance.table.ajax.reload();
            } else {
                instance.table.draw();
            }
            
            EventBus.emit('table:refreshed', { tableId });
        }
    };
    
    /**
     * Get table instance
     */
    const getInstance = (tableId) => {
        const instance = instances.get(tableId);
        return instance ? instance.table : null;
    };
    
    /**
     * Get table data
     */
    const getData = (tableId) => {
        const table = getInstance(tableId);
        return table ? table.data().toArray() : [];
    };
    
    /**
     * Get selected rows (if selection is enabled)
     */
    const getSelected = (tableId) => {
        const table = getInstance(tableId);
        if (table) {
            return table.rows('.selected').data().toArray();
        }
        return [];
    };
    
    /**
     * Add export buttons to table
     */
    const addExportButtons = (tableId, customButtons = []) => {
        const instance = instances.get(tableId);
        if (instance && instance.table) {
            const defaultButtons = [
                {
                    extend: 'excel',
                    text: '📊 Excel',
                    className: 'btn btn-sm btn-outline-success'
                },
                {
                    extend: 'pdf',
                    text: '📄 PDF',
                    className: 'btn btn-sm btn-outline-danger'
                },
                {
                    extend: 'print',
                    text: '🖨️ Print',
                    className: 'btn btn-sm btn-outline-secondary'
                },
                {
                    text: '📋 CSV',
                    className: 'btn btn-sm btn-outline-info',
                    action: function() {
                        exportToCSV(tableId);
                    }
                }
            ];
            
            const buttons = [...defaultButtons, ...customButtons];
            
            // Add buttons extension if not already present
            if (!instance.table.buttons) {
                instance.table.buttons().container().appendTo($(`#${tableId}_wrapper .col-md-6:eq(0)`));
            }
            
            // Update buttons
            instance.table.buttons(0, null).remove();
            instance.table.buttons().add(0, buttons);
        }
    };
    
    /**
     * Export table data to CSV
     */
    const exportToCSV = (tableId, filename = null) => {
        const table = getInstance(tableId);
        if (!table) return;
        
        const data = table.data();
        const columns = table.settings()[0].aoColumns;
        
        // Generate filename if not provided
        if (!filename) {
            const timestamp = new Date().toISOString().split('T')[0];
            filename = `${tableId}_export_${timestamp}.csv`;
        }
        
        // Create CSV header
        const headers = columns
            .filter(col => col.bVisible !== false)
            .map(col => col.sTitle || col.title || col.data)
            .join(',');
        
        // Create CSV rows
        const rows = [];
        data.each(function(row) {
            const csvRow = columns
                .filter(col => col.bVisible !== false)
                .map(col => {
                    const value = row[col.data] || '';
                    // Escape quotes and wrap in quotes if contains comma or quote
                    const escaped = String(value).replace(/"/g, '""');
                    return /[",\n\r]/.test(escaped) ? `"${escaped}"` : escaped;
                })
                .join(',');
            rows.push(csvRow);
        });
        
        // Combine and download
        const csv = [headers, ...rows].join('\n');
        downloadFile(csv, filename, 'text/csv');
        
        EventBus.emit('table:exported', {
            tableId,
            format: 'csv',
            filename,
            recordCount: data.length
        });
    };
    
    /**
     * Helper function to download file
     */
    const downloadFile = (content, filename, type) => {
        const blob = new Blob([content], { type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    };
    
    /**
     * Apply filters to table
     */
    const applyFilters = (tableId, filters) => {
        const table = getInstance(tableId);
        if (!table) return;
        
        // Clear existing search
        table.search('');
        
        // Apply column-specific filters
        Object.entries(filters).forEach(([column, value]) => {
            if (value && value !== '') {
                table.column(`${column}:name`).search(value);
            } else {
                table.column(`${column}:name`).search('');
            }
        });
        
        table.draw();
        
        EventBus.emit('table:filters-applied', {
            tableId,
            filters
        });
    };
    
    /**
     * Clear all filters
     */
    const clearFilters = (tableId) => {
        const table = getInstance(tableId);
        if (table) {
            table.search('').columns().search('').draw();
            
            EventBus.emit('table:filters-cleared', { tableId });
        }
    };
    
    /**
     * Destroy table instance
     */
    const destroy = (tableId) => {
        const instance = instances.get(tableId);
        if (instance && instance.table) {
            instance.table.destroy();
            instances.delete(tableId);
            
            EventBus.emit('table:destroyed', { tableId });
        }
    };
    
    /**
     * Destroy all table instances
     */
    const destroyAll = () => {
        instances.forEach((instance, tableId) => {
            destroy(tableId);
        });
    };
    
    /**
     * Get configuration for common table types
     */
    const getPresetConfig = (type) => {
        const presets = {
            events: {
                columns: [
                    { data: 'title', title: 'Title', name: 'title' },
                    { 
                        data: 'start', 
                        title: 'Start',
                        name: 'start',
                        render: (data) => Utils.formatDateTime(data)
                    },
                    { 
                        data: 'end', 
                        title: 'End',
                        name: 'end',
                        render: (data) => Utils.formatDateTime(data)
                    },
                    { 
                        data: null, 
                        title: 'Duration',
                        name: 'duration',
                        render: (data) => Utils.calculateDuration(data.start, data.end)
                    },
                    { data: 'owner_name', title: 'Owner', name: 'owner' },
                    { 
                        data: null, 
                        title: 'Status',
                        name: 'status',
                        render: (data) => {
                            const status = Utils.getEventStatus(data.start, data.end);
                            const badgeClass = status === 'upcoming' ? 'badge-primary' : 
                                             status === 'ongoing' ? 'badge-success' : 'badge-secondary';
                            return `<span class="badge ${badgeClass}">${status}</span>`;
                        }
                    }
                ],
                order: [[1, 'desc']] // Sort by start date
            },
            
            users: {
                columns: [
                    { data: 'name', title: 'Name', name: 'name' },
                    { data: 'email', title: 'Email', name: 'email' },
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
                    }
                ],
                order: [[0, 'asc']] // Sort by name
            }
        };
        
        return presets[type] || {};
    };
    
    return {
        create,
        createServerSide,
        loadData,
        refresh,
        getInstance,
        getData,
        getSelected,
        addExportButtons,
        exportToCSV,
        applyFilters,
        clearFilters,
        destroy,
        destroyAll,
        getPresetConfig
    };
})();