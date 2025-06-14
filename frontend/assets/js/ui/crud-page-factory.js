/**
 * CRUD Page Factory - Creates standardized CRUD pages to eliminate code duplication
 * Location: frontend/js/ui/crud-page-factory.js
 * 
 * This factory creates full-featured CRUD pages with consistent patterns for:
 * - Data management with filtering and caching
 * - DataTable integration with search and pagination
 * - Modal-based entity management (view/edit/create)
 * - Filter management with debounced search
 * - Real-time updates via SSE
 */

import { EventBus } from '../core/event-bus.js';
import { Config } from '../core/config.js';
import { Utils } from '../core/utils.js';
import { APIClient } from '../core/api-client.js';
import { AuthGuard } from '../auth/auth-guard.js';
import { UserManager } from '../auth/user-manager.js';
import { UIManager } from '../ui/ui-manager.js';
import { ModalManager } from '../ui/modal-manager.js';
import { TableManager } from '../ui/table-manager.js';
import { SSEManager } from '../realtime/sse-manager.js';

export const CrudPageFactory = (() => {
    
    /**
     * Creates a complete CRUD page application
     * @param {Object} config - Page configuration
     * @param {string} config.entityType - Type of entity (e.g., 'users', 'events')
     * @param {string} config.apiAction - API action for fetching data
     * @param {string} config.tableId - HTML table element ID
     * @param {Array} config.columns - DataTable column definitions
     * @param {Object} config.filters - Filter configuration
     * @param {Object} config.modal - Modal configuration
     * @param {Object} config.api - API method mappings
     * @param {Function} config.dataTransform - Optional data transformation function
     * @param {Object} config.customHandlers - Optional custom event handlers
     */
    const create = (config) => {
        // Validate required config
        if (!config.entityType || !config.apiAction || !config.tableId) {
            throw new Error('CrudPageFactory requires entityType, apiAction, and tableId');
        }

        const {
            entityType,
            apiAction,
            tableId,
            columns = [],
            filters = {},
            modal = {},
            api = {},
            dataTransform = null,
            customHandlers = {}
        } = config;

        // =============================================================================
        // DATA MANAGER
        // =============================================================================
        const DataManager = (() => {
            let entityData = [];
            let lastFetchTime = 0;
            let currentFilters = { ...filters.defaults };
            
            const loadData = async () => {
                try {
                    EventBus.emit(`${entityType}:loading`);
                    
                    console.log(`Loading ${entityType} data...`);
                    
                    // Use the specified API method or default to generic action
                    let data;
                    if (api.loadData && typeof APIClient[api.loadData] === 'function') {
                        data = await APIClient[api.loadData]();
                    } else {
                        data = await APIClient.makeRequest({ action: apiAction });
                    }
                    
                    console.log(`Successfully loaded ${entityType}:`, data);
                    
                    // Handle DataTables format response
                    let actualData = data;
                    if (data && typeof data === 'object' && data.data && Array.isArray(data.data)) {
                        // DataTables format: {draw: 1, recordsTotal: X, recordsFiltered: Y, data: [...]}
                        actualData = data.data;
                    } else if (!Array.isArray(data)) {
                        throw new Error(`Invalid ${entityType} data format received from server`);
                    }
                    
                    // Apply data transformation if provided
                    entityData = dataTransform ? actualData.map(dataTransform) : actualData;
                    lastFetchTime = Date.now();
                    
                    EventBus.emit(`${entityType}:loaded`, { entities: entityData });
                    
                    return entityData;
                } catch (error) {
                    console.error(`Error loading ${entityType}:`, error);
                    UIManager.showNotification(`Error loading ${entityType}: ${error.message}`, 'error');
                    EventBus.emit(`${entityType}:error`, { error });
                    throw error;
                }
            };
            
            const getFilteredData = () => {
                let filtered = [...entityData];
                
                // Apply filters
                Object.entries(currentFilters).forEach(([key, value]) => {
                    if (value && value !== '') {
                        filtered = filtered.filter(item => {
                            const itemValue = item[key];
                            if (typeof itemValue === 'string') {
                                return itemValue.toLowerCase().includes(value.toLowerCase());
                            }
                            return itemValue === value;
                        });
                    }
                });
                
                return filtered;
            };
            
            const updateFilters = (newFilters) => {
                currentFilters = { ...currentFilters, ...newFilters };
                EventBus.emit(`${entityType}:filters-changed`, { filters: currentFilters });
            };
            
            const refreshData = async () => {
                try {
                    await loadData();
                    EventBus.emit(`${entityType}:refreshed`);
                } catch (error) {
                    console.error(`Error refreshing ${entityType}:`, error);
                }
            };
            
            const updateSingleRecord = (updatedRecord) => {
                const index = entityData.findIndex(item => item.id === updatedRecord.id);
                if (index !== -1) {
                    // Apply data transformation if provided
                    const transformedRecord = dataTransform ? dataTransform(updatedRecord) : updatedRecord;
                    entityData[index] = transformedRecord;
                    console.log(`Updated single ${entityType} record with ID: ${updatedRecord.id}`);
                    EventBus.emit(`${entityType}:single-updated`, { entity: transformedRecord });
                    return true;
                } else {
                    console.log(`${entityType} record with ID ${updatedRecord.id} not found in cache, performing full refresh`);
                    return false;
                }
            };
            
            const addSingleRecord = (newRecord) => {
                // Apply data transformation if provided
                const transformedRecord = dataTransform ? dataTransform(newRecord) : newRecord;
                entityData.push(transformedRecord);
                console.log(`Added single ${entityType} record with ID: ${newRecord.id}`);
                EventBus.emit(`${entityType}:single-added`, { entity: transformedRecord });
            };
            
            const removeSingleRecord = (recordId) => {
                const index = entityData.findIndex(item => item.id === recordId);
                if (index !== -1) {
                    const removedRecord = entityData[index];
                    entityData.splice(index, 1);
                    console.log(`Removed single ${entityType} record with ID: ${recordId}`);
                    EventBus.emit(`${entityType}:single-removed`, { entity: removedRecord });
                    return true;
                } else {
                    console.log(`${entityType} record with ID ${recordId} not found in cache`);
                    return false;
                }
            };
            
            const clearData = () => {
                entityData = [];
                lastFetchTime = 0;
                currentFilters = { ...filters.defaults };
            };
            
            return {
                loadData,
                getFilteredData,
                updateFilters,
                refreshData,
                updateSingleRecord,
                addSingleRecord,
                removeSingleRecord,
                clearData,
                getData: () => entityData,
                getFilters: () => ({ ...currentFilters }),
                getLastFetchTime: () => lastFetchTime
            };
        })();

        // =============================================================================
        // DATATABLE MANAGER
        // =============================================================================
        const DataTablesManager = (() => {
            let dataTable = null;
            
            const initializeDataTable = () => {
                if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                    $(`#${tableId}`).DataTable().destroy();
                }
                
                dataTable = TableManager.create(tableId, {
                    data: [],
                    columns: columns,
                    pageLength: 25,
                    order: config.defaultOrder || [[0, 'asc']],
                    responsive: true,
                    searching: true,
                    paging: true,
                    info: true,
                    language: {
                        emptyTable: `No ${entityType} found`,
                        processing: "Loading...",
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: `(filtered from _TOTAL_ total ${entityType})`
                    }
                });
                
                setupTableEvents();
                console.log(`DataTable initialized for ${entityType}`);
            };
            
            const setupTableEvents = () => {
                // Row click handler
                EventHandler.on('table:' + entityType + ':row-click', (data) => {
                    if (data.tableId === tableId && data.rowData) {
                        if (customHandlers.onRowClick) {
                            customHandlers.onRowClick(data.rowData);
                        } else {
                            ModalManager.openModal(entityType, data.rowData, 'view');
                        }
                    }
                });
                
                // Button click handler
                EventHandler.on('table:' + entityType + ':button-click', (data) => {
                    if (data.tableId === tableId) {
                        if (customHandlers.onButtonClick) {
                            customHandlers.onButtonClick(data);
                        }
                    }
                });
            };
            
            const refreshTable = () => {
                if (dataTable) {
                    const filteredData = DataManager.getFilteredData();
                    TableManager.loadData(tableId, filteredData);
                    console.log(`Table refreshed with ${filteredData.length} ${entityType}`);
                }
            };
            
            const updateSingleRow = (updatedRecord) => {
                if (dataTable) {
                    // Find the row by ID
                    const rowIndex = dataTable.data().toArray().findIndex(row => row.id === updatedRecord.id);
                    if (rowIndex !== -1) {
                        // Update the row data
                        dataTable.row(rowIndex).data(updatedRecord).draw(false);
                        console.log(`Updated single table row for ${entityType} ID: ${updatedRecord.id}`);
                        return true;
                    }
                }
                return false;
            };
            
            const addSingleRow = (newRecord) => {
                if (dataTable) {
                    dataTable.row.add(newRecord).draw(false);
                    console.log(`Added single table row for ${entityType} ID: ${newRecord.id}`);
                }
            };
            
            const removeSingleRow = (recordId) => {
                if (dataTable) {
                    const rowIndex = dataTable.data().toArray().findIndex(row => row.id === recordId);
                    if (rowIndex !== -1) {
                        dataTable.row(rowIndex).remove().draw(false);
                        console.log(`Removed single table row for ${entityType} ID: ${recordId}`);
                        return true;
                    }
                }
                return false;
            };
            
            return {
                initializeDataTable,
                refreshTable,
                updateSingleRow,
                addSingleRow,
                removeSingleRow,
                getDataTable: () => dataTable
            };
        })();

        // =============================================================================
        // FILTER MANAGER
        // =============================================================================
        const FilterManager = (() => {
            let searchTimeout;
            
            const initializeFilters = () => {
                // Initialize all filter elements using element mappings
                const elementMappings = filters.elements || {};
                
                Object.keys(filters.defaults).forEach(filterKey => {
                    const elementId = elementMappings[filterKey] || `${filterKey}Filter`;
                    const element = $(`#${elementId}`);
                    if (element.length) {
                        element.on('change', function() {
                            DataManager.updateFilters({ [filterKey]: this.value });
                            DataTablesManager.refreshTable();
                        });
                    }
                });
                
                // Search filter with debouncing
                const searchElementId = elementMappings.search || 'searchFilter';
                const searchElement = $(`#${searchElementId}`);
                if (searchElement.length) {
                    searchElement.on('input', function() {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            DataManager.updateFilters({ search: this.value });
                            DataTablesManager.refreshTable();
                        }, 300);
                    });
                }
                
                // Reset filters button
                const resetButton = $('#resetFilters');
                if (resetButton.length) {
                    resetButton.on('click', () => {
                        resetFilters();
                    });
                }
                
                console.log(`Filters initialized for ${entityType}`);
            };
            
            const resetFilters = () => {
                // Reset all filter form elements using element mappings
                const elementMappings = filters.elements || {};
                
                Object.keys(filters.defaults).forEach(filterKey => {
                    const elementId = elementMappings[filterKey] || `${filterKey}Filter`;
                    const element = $(`#${elementId}`);
                    if (element.length) {
                        element.val('').trigger('change');
                    }
                });
                
                const searchElementId = elementMappings.search || 'searchFilter';
                $(`#${searchElementId}`).val('');
                
                // Reset data manager filters
                DataManager.updateFilters(filters.defaults);
                DataTablesManager.refreshTable();
                
                UIManager.showNotification('Filters reset', 'success');
            };
            
            return {
                initializeFilters,
                resetFilters
            };
        })();

        // =============================================================================
        // MODAL MANAGER INTEGRATION
        // =============================================================================
        const ModalHandler = (() => {
            const initializeModalHandlers = () => {
                // Create new entity button using element mapping
                const elementMappings = filters.elements || {};
                const createButtonId = elementMappings.create || `create${entityType.charAt(0).toUpperCase() + entityType.slice(1)}Btn`;
                const createButton = $(`#${createButtonId}`);
                if (createButton.length) {
                    createButton.on('click', () => {
                        ModalManager.openModal(entityType, null, 'create');
                    });
                }
                
                // Listen for entity updates
                EventHandler.on(`${entityType}:created`, () => {
                    DataManager.refreshData().then(() => {
                        DataTablesManager.refreshTable();
                    });
                });
                
                EventHandler.on(`${entityType}:updated`, () => {
                    DataManager.refreshData().then(() => {
                        DataTablesManager.refreshTable();
                    });
                });
                
                EventHandler.on(`${entityType}:deleted`, () => {
                    DataManager.refreshData().then(() => {
                        DataTablesManager.refreshTable();
                    });
                });
            };
            
            return {
                initializeModalHandlers
            };
        })();

        // =============================================================================
        // EVENT HANDLER MANAGER
        // =============================================================================
        const EventHandler = (() => {
            const listeners = [];
            
            const on = (event, callback) => {
                EventBus.on(event, callback);
                listeners.push({ event, callback });
            };
            
            const cleanup = () => {
                console.log(`Cleaning up ${listeners.length} event listeners for ${entityType}`);
                listeners.forEach(({ event, callback }) => {
                    EventBus.off(event, callback);
                });
                listeners.length = 0;
            };
            
            return { on, cleanup };
        })();

        // =============================================================================
        // MAIN APP CONTROLLER
        // =============================================================================
        const App = (() => {
            const init = async () => {
                try {
                    // Check authentication
                    const isAuthenticated = await AuthGuard.checkAuthentication();
                    if (!isAuthenticated) {
                        return; // AuthGuard handles redirect
                    }
                    
                    console.log(`Initializing ${entityType} page...`);
                    
                    // Initialize components in sequence
                    DataTablesManager.initializeDataTable();
                    FilterManager.initializeFilters();
                    ModalHandler.initializeModalHandlers();
                    
                    // Set up refresh button
                    setupRefreshButton();
                    
                    // Set up event listeners
                    setupEventListeners();
                    
                    // Load initial data
                    await DataManager.loadData();
                    // Table refresh is handled by the events:loaded listener
                    
                    // Initialize SSE for real-time updates
                    SSEManager.connect();
                    
                    console.log(`${entityType} page initialized successfully`);
                } catch (error) {
                    console.error(`Error initializing ${entityType} page:`, error);
                    UIManager.showNotification(`Error initializing page: ${error.message}`, 'error');
                }
            };
            
            const setupRefreshButton = () => {
                const elementMappings = filters.elements || {};
                const refreshButtonId = elementMappings.refresh || 'refreshData';
                const refreshBtn = $(`#${refreshButtonId}`);
                if (refreshBtn.length) {
                    refreshBtn.on('click', async function() {
                        const originalText = this.textContent;
                        
                        try {
                            this.textContent = 'Refreshing...';
                            this.disabled = true;
                            
                            await DataManager.refreshData();
                            DataTablesManager.refreshTable();
                            
                            // Show success feedback
                            this.textContent = '✓ Refreshed';
                            this.style.background = '#48bb78';
                            this.style.color = 'white';
                            
                            setTimeout(() => {
                                this.textContent = originalText;
                                this.style.background = '';
                                this.style.color = '';
                                this.disabled = false;
                            }, 1500);
                            
                        } catch (error) {
                            this.textContent = originalText;
                            this.disabled = false;
                            UIManager.showNotification('Error refreshing data', 'error');
                        }
                    });
                }
            };
            
            const setupEventListeners = () => {
                // Listen for data loaded events
                EventHandler.on(`${entityType}:loaded`, () => {
                    DataTablesManager.refreshTable();
                });
                
                EventHandler.on(`${entityType}:filters-changed`, () => {
                    DataTablesManager.refreshTable();
                });
                
                // Listen for single record updates
                EventHandler.on(`${entityType}:single-updated`, (data) => {
                    DataTablesManager.updateSingleRow(data.entity);
                });
                
                EventHandler.on(`${entityType}:single-added`, (data) => {
                    DataTablesManager.addSingleRow(data.entity);
                });
                
                EventHandler.on(`${entityType}:single-removed`, (data) => {
                    DataTablesManager.removeSingleRow(data.entity.id);
                });
                
                // Listen for specific SSE events based on entity type
                if (entityType === 'events') {
                    // Listen for event-specific SSE events
                    EventHandler.on('sse:eventCreate', (data) => {
                        console.log('SSE: Event created, updating single record');
                        if (customHandlers.onSSEMessage) {
                            customHandlers.onSSEMessage(data);
                        }
                        if (data.eventData) {
                            DataManager.addSingleRecord(data.eventData);
                        } else {
                            console.warn('SSE event create data missing eventData, falling back to full refresh');
                            DataManager.refreshData();
                        }
                    });
                    
                    EventHandler.on('sse:eventUpdate', (data) => {
                        console.log('SSE: Event updated, updating single record');
                        if (customHandlers.onSSEMessage) {
                            customHandlers.onSSEMessage(data);
                        }
                        if (data.eventData) {
                            const updated = DataManager.updateSingleRecord(data.eventData);
                            if (!updated) {
                                console.warn('Record not found in cache, falling back to full refresh');
                                DataManager.refreshData();
                            }
                        } else {
                            console.warn('SSE event update data missing eventData, falling back to full refresh');
                            DataManager.refreshData();
                        }
                    });
                    
                    EventHandler.on('sse:eventDelete', (data) => {
                        console.log('SSE: Event deleted, removing single record');
                        if (customHandlers.onSSEMessage) {
                            customHandlers.onSSEMessage(data);
                        }
                        if (data.eventId) {
                            const removed = DataManager.removeSingleRecord(data.eventId);
                            if (!removed) {
                                console.warn('Record not found in cache, falling back to full refresh');
                                DataManager.refreshData();
                            }
                        } else {
                            console.warn('SSE event delete data missing eventId, falling back to full refresh');
                            DataManager.refreshData();
                        }
                    });
                } else if (entityType === 'users') {
                    // Listen for user-specific SSE events
                    EventHandler.on('users:refresh', () => {
                        console.log('SSE: Users refresh requested, refreshing table');
                        DataManager.refreshData();
                        // Note: DataTablesManager.refreshTable() is called by the ${entityType}:loaded event handler
                    });
                }
                
                // Listen for generic SSE events if configured
                if (customHandlers.onSSEMessage) {
                    EventHandler.on('sse:message', customHandlers.onSSEMessage);
                }
                
                // Listen for cleanup on page unload
                window.addEventListener('beforeunload', cleanup);
            };
            
            const cleanup = () => {
                console.log(`Cleaning up ${entityType} data manager...`);
                DataManager.clearData();
                EventHandler.cleanup();
                TableManager.destroyAll();
                SSEManager.disconnect();
            };
            
            return {
                init,
                cleanup,
                // Expose managers for custom extensions
                DataManager,
                DataTablesManager,
                FilterManager,
                ModalHandler
            };
        })();

        return App;
    };
    
    return {
        create
    };
})();