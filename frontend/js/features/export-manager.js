/**
 * Shared Export Manager for data export functionality
 * Location: frontend/js/features/export-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';
import { NotificationManager } from '../ui/notification-manager.js';

export const ExportManager = (() => {
    
    /**
     * Export data to CSV format
     */
    const exportToCSV = (data, filename = null, columns = null) => {
        try {
            if (!Array.isArray(data) || data.length === 0) {
                NotificationManager.showWarning('No data available to export');
                return false;
            }
            
            // Generate filename if not provided
            if (!filename) {
                const timestamp = new Date().toISOString().split('T')[0];
                filename = `export_${timestamp}.csv`;
            }
            
            // Ensure filename has .csv extension
            if (!filename.endsWith('.csv')) {
                filename += '.csv';
            }
            
            // Determine columns to export
            const exportColumns = columns || Object.keys(data[0]);
            
            // Create CSV header
            const headers = exportColumns.map(col => {
                if (typeof col === 'object') {
                    return col.title || col.header || col.key;
                }
                return col;
            }).join(',');
            
            // Create CSV rows
            const rows = data.map(row => {
                return exportColumns.map(col => {
                    let value;
                    
                    if (typeof col === 'object') {
                        // Handle column objects with key and formatter
                        const key = col.key || col.data;
                        value = row[key];
                        
                        // Apply formatter if provided
                        if (col.formatter && typeof col.formatter === 'function') {
                            value = col.formatter(value, row);
                        }
                    } else {
                        // Simple column name
                        value = row[col];
                    }
                    
                    // Handle different data types
                    if (value === null || value === undefined) {
                        value = '';
                    } else if (typeof value === 'object') {
                        value = JSON.stringify(value);
                    } else {
                        value = String(value);
                    }
                    
                    // Escape CSV special characters
                    return escapeCsvValue(value);
                }).join(',');
            });
            
            // Combine and download
            const csv = [headers, ...rows].join('\n');
            downloadFile(csv, filename, 'text/csv');
            
            NotificationManager.showSuccess(`Successfully exported ${data.length} records to ${filename}`);
            
            EventBus.emit('export:completed', {
                format: 'csv',
                filename,
                recordCount: data.length
            });
            
            return true;
            
        } catch (error) {
            console.error('CSV export error:', error);
            NotificationManager.showError('Failed to export data to CSV');
            return false;
        }
    };
    
    /**
     * Export data to JSON format
     */
    const exportToJSON = (data, filename = null, pretty = true) => {
        try {
            if (!data) {
                NotificationManager.showWarning('No data available to export');
                return false;
            }
            
            // Generate filename if not provided
            if (!filename) {
                const timestamp = new Date().toISOString().split('T')[0];
                filename = `export_${timestamp}.json`;
            }
            
            // Ensure filename has .json extension
            if (!filename.endsWith('.json')) {
                filename += '.json';
            }
            
            // Convert to JSON
            const json = pretty ? JSON.stringify(data, null, 2) : JSON.stringify(data);
            
            downloadFile(json, filename, 'application/json');
            
            const recordCount = Array.isArray(data) ? data.length : 1;
            NotificationManager.showSuccess(`Successfully exported ${recordCount} records to ${filename}`);
            
            EventBus.emit('export:completed', {
                format: 'json',
                filename,
                recordCount
            });
            
            return true;
            
        } catch (error) {
            console.error('JSON export error:', error);
            NotificationManager.showError('Failed to export data to JSON');
            return false;
        }
    };
    
    /**
     * Export data to Excel format (using SheetJS if available)
     */
    const exportToExcel = (data, filename = null, worksheetName = 'Sheet1') => {
        try {
            // Check if SheetJS is available
            if (typeof XLSX === 'undefined') {
                NotificationManager.showWarning('Excel export requires SheetJS library. Falling back to CSV export.');
                return exportToCSV(data, filename?.replace('.xlsx', '.csv'));
            }
            
            if (!Array.isArray(data) || data.length === 0) {
                NotificationManager.showWarning('No data available to export');
                return false;
            }
            
            // Generate filename if not provided
            if (!filename) {
                const timestamp = new Date().toISOString().split('T')[0];
                filename = `export_${timestamp}.xlsx`;
            }
            
            // Ensure filename has .xlsx extension
            if (!filename.endsWith('.xlsx')) {
                filename += '.xlsx';
            }
            
            // Create workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.json_to_sheet(data);
            
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(wb, ws, worksheetName);
            
            // Save file
            XLSX.writeFile(wb, filename);
            
            NotificationManager.showSuccess(`Successfully exported ${data.length} records to ${filename}`);
            
            EventBus.emit('export:completed', {
                format: 'excel',
                filename,
                recordCount: data.length
            });
            
            return true;
            
        } catch (error) {
            console.error('Excel export error:', error);
            NotificationManager.showError('Failed to export data to Excel');
            return false;
        }
    };
    
    /**
     * Export data to XML format
     */
    const exportToXML = (data, filename = null, rootElement = 'data', itemElement = 'item') => {
        try {
            if (!data) {
                NotificationManager.showWarning('No data available to export');
                return false;
            }
            
            // Generate filename if not provided
            if (!filename) {
                const timestamp = new Date().toISOString().split('T')[0];
                filename = `export_${timestamp}.xml`;
            }
            
            // Ensure filename has .xml extension
            if (!filename.endsWith('.xml')) {
                filename += '.xml';
            }
            
            // Convert to XML
            let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
            xml += `<${rootElement}>\n`;
            
            if (Array.isArray(data)) {
                data.forEach(item => {
                    xml += `  <${itemElement}>\n`;
                    Object.entries(item).forEach(([key, value]) => {
                        const escapedValue = escapeXmlValue(value);
                        xml += `    <${key}>${escapedValue}</${key}>\n`;
                    });
                    xml += `  </${itemElement}>\n`;
                });
            } else {
                Object.entries(data).forEach(([key, value]) => {
                    const escapedValue = escapeXmlValue(value);
                    xml += `  <${key}>${escapedValue}</${key}>\n`;
                });
            }
            
            xml += `</${rootElement}>`;
            
            downloadFile(xml, filename, 'text/xml');
            
            const recordCount = Array.isArray(data) ? data.length : 1;
            NotificationManager.showSuccess(`Successfully exported ${recordCount} records to ${filename}`);
            
            EventBus.emit('export:completed', {
                format: 'xml',
                filename,
                recordCount
            });
            
            return true;
            
        } catch (error) {
            console.error('XML export error:', error);
            NotificationManager.showError('Failed to export data to XML');
            return false;
        }
    };
    
    /**
     * Export table data directly from DataTable instance
     */
    const exportTableData = (tableId, format = 'csv', filename = null, options = {}) => {
        try {
            // Get DataTable instance
            const table = $(`#${tableId}`).DataTable();
            if (!table) {
                NotificationManager.showError('Table not found');
                return false;
            }
            
            // Get visible data
            const data = table.rows({ search: 'applied' }).data().toArray();
            
            if (data.length === 0) {
                NotificationManager.showWarning('No data available to export');
                return false;
            }
            
            // Generate filename if not provided
            if (!filename) {
                const timestamp = new Date().toISOString().split('T')[0];
                filename = `${tableId}_export_${timestamp}`;
            }
            
            // Export based on format
            switch (format.toLowerCase()) {
                case 'csv':
                    return exportToCSV(data, filename, options.columns);
                case 'json':
                    return exportToJSON(data, filename, options.pretty);
                case 'excel':
                case 'xlsx':
                    return exportToExcel(data, filename, options.worksheetName);
                case 'xml':
                    return exportToXML(data, filename, options.rootElement, options.itemElement);
                default:
                    NotificationManager.showError(`Unsupported export format: ${format}`);
                    return false;
            }
            
        } catch (error) {
            console.error('Table export error:', error);
            NotificationManager.showError('Failed to export table data');
            return false;
        }
    };
    
    /**
     * Create export buttons for a table
     */
    const createExportButtons = (containerId, tableId, options = {}) => {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Export button container not found: ${containerId}`);
            return;
        }
        
        const buttonConfig = {
            csv: { text: '📊 CSV', className: 'btn btn-sm btn-outline-success' },
            json: { text: '📄 JSON', className: 'btn btn-sm btn-outline-info' },
            excel: { text: '📗 Excel', className: 'btn btn-sm btn-outline-primary' },
            xml: { text: '🗂️ XML', className: 'btn btn-sm btn-outline-warning' },
            ...options.buttons
        };
        
        const formats = options.formats || ['csv', 'json', 'excel'];
        
        // Create button group
        const buttonGroup = document.createElement('div');
        buttonGroup.className = 'btn-group export-buttons';
        buttonGroup.setAttribute('role', 'group');
        buttonGroup.setAttribute('aria-label', 'Export options');
        
        formats.forEach(format => {
            if (buttonConfig[format]) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = buttonConfig[format].className;
                button.textContent = buttonConfig[format].text;
                button.title = `Export to ${format.toUpperCase()}`;
                
                button.addEventListener('click', () => {
                    exportTableData(tableId, format, null, options);
                });
                
                buttonGroup.appendChild(button);
            }
        });
        
        container.appendChild(buttonGroup);
        
        EventBus.emit('export:buttons-created', {
            containerId,
            tableId,
            formats
        });
    };
    
    /**
     * Escape CSV special characters
     */
    const escapeCsvValue = (value) => {
        const stringValue = String(value);
        
        // If value contains comma, quote, or newline, wrap in quotes and escape quotes
        if (/[",\n\r]/.test(stringValue)) {
            return `"${stringValue.replace(/"/g, '""')}"`;
        }
        
        return stringValue;
    };
    
    /**
     * Escape XML special characters
     */
    const escapeXmlValue = (value) => {
        if (value === null || value === undefined) {
            return '';
        }
        
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    };
    
    /**
     * Download file helper
     */
    const downloadFile = (content, filename, mimeType) => {
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Clean up
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 100);
    };
    
    /**
     * Get export formats with their configurations
     */
    const getAvailableFormats = () => {
        return {
            csv: {
                name: 'CSV',
                extension: '.csv',
                mimeType: 'text/csv',
                description: 'Comma-separated values'
            },
            json: {
                name: 'JSON',
                extension: '.json',
                mimeType: 'application/json',
                description: 'JavaScript Object Notation'
            },
            excel: {
                name: 'Excel',
                extension: '.xlsx',
                mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                description: 'Microsoft Excel',
                requiresLibrary: 'SheetJS (XLSX)'
            },
            xml: {
                name: 'XML',
                extension: '.xml',
                mimeType: 'text/xml',
                description: 'Extensible Markup Language'
            }
        };
    };
    
    return {
        exportToCSV,
        exportToJSON,
        exportToExcel,
        exportToXML,
        exportTableData,
        createExportButtons,
        getAvailableFormats
    };
})();