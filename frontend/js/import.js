/**
 * Import Page - Refactored to use modular components
 * Location: frontend/js/import.js
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
import { UIManager } from './ui/ui-manager.js';
import { ModalManager } from './ui/modal-manager.js';

// =============================================================================
// PAGE-SPECIFIC CONFIGURATION
// =============================================================================

const ImportConfig = {
    limits: {
        maxFileSize: 5 * 1024 * 1024, // 5MB
        maxEvents: 20,
        supportedFormats: ['json', 'csv', 'ics']
    },
    fileTypes: {
        json: 'application/json',
        csv: 'text/csv',
        ics: 'text/calendar'
    }
};

// =============================================================================
// PAGE-SPECIFIC COMPONENTS
// =============================================================================

/**
 * File handling and upload management
 */
const FileManager = (() => {
    let currentFile = null;
    let parsedData = null;
    
    const initializeFileHandling = () => {
        const fileInput = document.getElementById('fileInput');
        const dropZone = document.getElementById('fileDropZone');
        
        if (fileInput) {
            fileInput.addEventListener('change', handleFileSelect);
        }
        
        if (dropZone) {
            setupDropZone(dropZone);
        }
    };
    
    const setupDropZone = (dropZone) => {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });
        
        dropZone.addEventListener('click', () => {
            document.getElementById('fileInput')?.click();
        });
    };
    
    const handleFileSelect = (e) => {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    };
    
    const handleFile = (file) => {
        // Validate file
        const validation = validateFile(file);
        if (!validation.valid) {
            UIManager.showError(validation.error);
            return;
        }
        
        currentFile = file;
        updateFileInfo(file);
        enableButtons(['validateBtn']);
        EventBus.emit('file:selected', { file });
    };
    
    const validateFile = (file) => {
        if (!file) {
            return { valid: false, error: 'No file selected' };
        }
        
        if (file.size > ImportConfig.limits.maxFileSize) {
            return { valid: false, error: `File size exceeds ${ImportConfig.limits.maxFileSize / 1024 / 1024}MB limit` };
        }
        
        const extension = file.name.split('.').pop().toLowerCase();
        if (!ImportConfig.limits.supportedFormats.includes(extension)) {
            return { valid: false, error: `Unsupported file format. Supported: ${ImportConfig.limits.supportedFormats.join(', ')}` };
        }
        
        return { valid: true };
    };
    
    const updateFileInfo = (file) => {
        const infoDiv = document.getElementById('fileInfo');
        if (infoDiv) {
            infoDiv.innerHTML = `
                <div class="file-details">
                    <strong>File:</strong> ${file.name}<br>
                    <strong>Size:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                    <strong>Type:</strong> ${file.type || 'Unknown'}
                </div>
            `;
            infoDiv.style.display = 'block';
        }
    };
    
    const enableButtons = (buttonIds) => {
        buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('disabled');
            }
        });
    };
    
    const disableButtons = (buttonIds) => {
        buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = true;
                btn.classList.add('disabled');
            }
        });
    };
    
    const getCurrentFile = () => currentFile;
    const getParsedData = () => parsedData;
    const setParsedData = (data) => { parsedData = data; };
    
    return {
        initializeFileHandling,
        getCurrentFile,
        getParsedData,
        setParsedData,
        enableButtons,
        disableButtons
    };
})();

/**
 * File validation and parsing
 */
const ValidationManager = (() => {
    const validateFile = async () => {
        const file = FileManager.getCurrentFile();
        if (!file) {
            UIManager.showError('Please select a file first');
            return;
        }
        
        try {
            UIManager.showLoadingOverlay('Validating file...');
            
            const content = await readFileContent(file);
            const parsed = await parseFileContent(content, file);
            
            FileManager.setParsedData(parsed);
            
            UIManager.hideLoadingOverlay();
            UIManager.showSuccess(`File validated successfully! Found ${parsed.events.length} events.`);
            
            FileManager.enableButtons(['previewBtn']);
            
            if (parsed.events.length <= ImportConfig.limits.maxEvents) {
                FileManager.enableButtons(['importBtn']);
            } else {
                UIManager.showError(`Too many events (${parsed.events.length}). Maximum allowed: ${ImportConfig.limits.maxEvents}`);
            }
            
        } catch (error) {
            console.error('Validation error:', error);
            UIManager.hideLoadingOverlay();
            UIManager.showError('Validation failed: ' + error.message);
        }
    };
    
    const readFileContent = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    };
    
    const parseFileContent = async (content, file) => {
        const extension = file.name.split('.').pop().toLowerCase();
        
        switch (extension) {
            case 'json':
                return parseJSON(content);
            case 'csv':
                return parseCSV(content);
            case 'ics':
                return parseICS(content);
            default:
                throw new Error('Unsupported file format');
        }
    };
    
    const parseJSON = (content) => {
        try {
            const data = JSON.parse(content);
            const events = Array.isArray(data) ? data : (data.events || []);
            
            return {
                format: 'json',
                events: events.map(validateEventData)
            };
        } catch (error) {
            throw new Error('Invalid JSON format');
        }
    };
    
    const parseCSV = (content) => {
        const lines = content.split('\\n').filter(line => line.trim());
        if (lines.length < 2) throw new Error('CSV must have header row and at least one data row');
        
        const headers = lines[0].split(',').map(h => h.trim().replace(/\"/g, ''));
        const events = [];
        
        for (let i = 1; i < lines.length; i++) {
            const values = lines[i].split(',').map(v => v.trim().replace(/\"/g, ''));
            const event = {};
            
            headers.forEach((header, index) => {
                event[header.toLowerCase()] = values[index] || '';
            });
            
            events.push(validateEventData(event));
        }
        
        return {
            format: 'csv',
            events
        };
    };
    
    const parseICS = (content) => {
        const events = [];
        const eventBlocks = content.split('BEGIN:VEVENT');
        
        for (let i = 1; i < eventBlocks.length; i++) {
            const eventData = parseICSEvent(eventBlocks[i]);
            if (eventData) {
                events.push(validateEventData(eventData));
            }
        }
        
        return {
            format: 'ics',
            events
        };
    };
    
    const parseICSEvent = (eventBlock) => {
        const lines = eventBlock.split('\\n');
        const event = {};
        
        lines.forEach(line => {
            if (line.includes(':')) {
                const [key, ...valueParts] = line.split(':');
                const value = valueParts.join(':').trim();
                
                switch (key.trim()) {
                    case 'SUMMARY':
                        event.title = value;
                        break;
                    case 'DTSTART':
                        event.start = formatICSDateTime(value);
                        break;
                    case 'DTEND':
                        event.end = formatICSDateTime(value);
                        break;
                    case 'DESCRIPTION':
                        event.description = value;
                        break;
                }
            }
        });
        
        return event.title && event.start ? event : null;
    };
    
    const formatICSDateTime = (icsDateTime) => {
        // Convert ICS format (YYYYMMDDTHHMMSSZ) to MySQL format
        const match = icsDateTime.match(/(\\d{4})(\\d{2})(\\d{2})T(\\d{2})(\\d{2})(\\d{2})/);
        if (match) {
            return `${match[1]}-${match[2]}-${match[3]} ${match[4]}:${match[5]}:${match[6]}`;
        }
        return icsDateTime;
    };
    
    const validateEventData = (event) => {
        return {
            title: event.title || event.summary || 'Untitled Event',
            start: Utils.formatDateTimeForAPI(event.start || event.dtstart),
            end: Utils.formatDateTimeForAPI(event.end || event.dtend || event.start),
            description: event.description || event.desc || ''
        };
    };
    
    return {
        validateFile
    };
})();

/**
 * Preview management for parsed data
 */
const PreviewManager = (() => {
    const previewFile = () => {
        const data = FileManager.getParsedData();
        if (!data || !data.events.length) {
            UIManager.showError('No valid data to preview. Please validate the file first.');
            return;
        }
        
        showPreviewModal(data);
    };
    
    const showPreviewModal = (data) => {
        const modal = ModalManager.create({
            title: `Preview: ${data.events.length} Events (${data.format.toUpperCase()})`,
            size: 'large',
            body: generatePreviewTable(data.events),
            footer: `
                <button class="btn btn-secondary" onclick="ModalManager.closeActive()">Close</button>
                <button class="btn btn-primary" onclick="ImportManager.importEvents()">Import These Events</button>
            `
        });
        
        modal.show();
    };
    
    const generatePreviewTable = (events) => {
        if (!events.length) {
            return '<p>No events to preview.</p>';
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        events.forEach(event => {
            const duration = Utils.calculateDuration(event.start, event.end);
            html += `
                <tr>
                    <td>${escapeHtml(event.title)}</td>
                    <td>${Utils.formatDateTime(event.start)}</td>
                    <td>${Utils.formatDateTime(event.end)}</td>
                    <td>${duration}</td>
                    <td>${escapeHtml(event.description || 'N/A')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        return html;
    };
    
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    return {
        previewFile
    };
})();

/**
 * Import execution management
 */
const ImportManager = (() => {
    const importEvents = async () => {
        const data = FileManager.getParsedData();
        if (!data || !data.events.length) {
            UIManager.showError('No valid data to import. Please validate the file first.');
            return;
        }
        
        try {
            UIManager.showLoadingOverlay('Importing events...');
            
            const response = await APIClient.createEvent({
                action: 'import_events',
                events: data.events,
                format: data.format
            });
            
            UIManager.hideLoadingOverlay();
            ModalManager.closeActive(); // Close preview modal if open
            
            if (response.success) {
                UIManager.showSuccess(`Successfully imported ${response.imported || data.events.length} events!`);
                resetImportForm();
                
                // Redirect to events page to see imported events
                setTimeout(() => {
                    window.location.href = 'events.php';
                }, 2000);
            } else {
                throw new Error(response.error || 'Import failed');
            }
            
        } catch (error) {
            console.error('Import error:', error);
            UIManager.hideLoadingOverlay();
            UIManager.showError('Import failed: ' + error.message);
        }
    };
    
    const resetImportForm = () => {
        document.getElementById('fileInput').value = '';
        document.getElementById('fileInfo').style.display = 'none';
        FileManager.disableButtons(['validateBtn', 'previewBtn', 'importBtn']);
        FileManager.setParsedData(null);
    };
    
    return {
        importEvents,
        resetImportForm
    };
})();

/**
 * Format examples management
 */
const FormatExamplesManager = (() => {
    const initializeFormatTabs = () => {
        const tabs = document.querySelectorAll('.format-tab');
        const contents = document.querySelectorAll('.format-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const format = tab.dataset.format;
                showFormatExample(format, tabs, contents);
            });
        });
        
        // Show first tab by default
        if (tabs.length > 0) {
            tabs[0].click();
        }
    };
    
    const showFormatExample = (format, tabs, contents) => {
        // Update tab states
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        
        // Activate selected tab and content
        const activeTab = document.querySelector(`[data-format="${format}"]`);
        const activeContent = document.getElementById(`${format}Example`);
        
        if (activeTab) activeTab.classList.add('active');
        if (activeContent) activeContent.classList.add('active');
    };
    
    return {
        initializeFormatTabs
    };
})();

/**
 * Button event handlers
 */
const ButtonHandlers = (() => {
    const initializeButtons = () => {
        // Validation button
        const validateBtn = document.getElementById('validateBtn');
        validateBtn?.addEventListener('click', ValidationManager.validateFile);
        
        // Preview button
        const previewBtn = document.getElementById('previewBtn');
        previewBtn?.addEventListener('click', PreviewManager.previewFile);
        
        // Import button
        const importBtn = document.getElementById('importBtn');
        importBtn?.addEventListener('click', ImportManager.importEvents);
        
        // Reset button
        const resetBtn = document.getElementById('resetBtn');
        resetBtn?.addEventListener('click', ImportManager.resetImportForm);
    };
    
    return {
        initializeButtons
    };
})();

/**
 * Main import application controller
 */
const ImportApp = (() => {
    const init = async () => {
        console.log('Initializing Import Page...');
        
        // Check authentication first using modular AuthGuard
        const isAuthenticated = await AuthGuard.checkAuthentication();
        
        if (!isAuthenticated) {
            return; // AuthGuard will handle redirect
        }
        
        // Initialize UI components
        FileManager.initializeFileHandling();
        FormatExamplesManager.initializeFormatTabs();
        ButtonHandlers.initializeButtons();
        
        // Update connection status
        UIManager.updateConnectionStatus?.('Ready', 'connected');
        
        console.log('Import Page initialized successfully');
    };
    
    const destroy = () => {
        console.log('Import Page destroyed');
    };
    
    return {
        init,
        destroy
    };
})();

// Make ImportManager available globally for modal buttons
window.ImportManager = ImportManager;

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Import app...');
    ImportApp.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    ImportApp.destroy();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        console.log('Page loaded from cache, checking auth...');
        AuthGuard.checkAuthentication();
    }
});