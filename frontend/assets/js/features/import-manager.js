/**
 * Shared Import Manager for data import functionality
 * Location: frontend/js/features/import-manager.js
 */
import { EventBus } from '../core/event-bus.js';
import { Utils } from '../core/utils.js';
import { NotificationManager } from '../ui/notification-manager.js';
import { ModalManager } from '../ui/modal-manager.js';

export const ImportManager = (() => {
    const defaultConfig = {
        maxFileSize: 5 * 1024 * 1024, // 5MB
        maxRecords: 1000,
        supportedFormats: ['json', 'csv', 'ics', 'xlsx'],
        validateOnLoad: true,
        showPreview: true
    };
    
    let currentFile = null;
    let parsedData = null;
    let importConfig = { ...defaultConfig };
    
    /**
     * Initialize file upload functionality
     */
    const initializeFileUpload = (inputId, dropZoneId = null, config = {}) => {
        importConfig = { ...defaultConfig, ...config };
        
        const fileInput = document.getElementById(inputId);
        if (!fileInput) {
            console.error(`File input not found: ${inputId}`);
            return false;
        }
        
        // Set up file input event
        fileInput.addEventListener('change', handleFileSelect);
        
        // Set up drop zone if provided
        if (dropZoneId) {
            const dropZone = document.getElementById(dropZoneId);
            if (dropZone) {
                setupDropZone(dropZone, fileInput);
            }
        }
        
        EventBus.emit('import:initialized', {
            inputId,
            dropZoneId,
            config: importConfig
        });
        
        return true;
    };
    
    /**
     * Set up drag and drop functionality
     */
    const setupDropZone = (dropZone, fileInput) => {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
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
            fileInput.click();
        });
    };
    
    /**
     * Handle file selection from input
     */
    const handleFileSelect = (e) => {
        const file = e.target.files[0];
        if (file) {
            handleFile(file);
        }
    };
    
    /**
     * Handle file processing
     */
    const handleFile = async (file) => {
        try {
            // Validate file
            const validation = validateFile(file);
            if (!validation.valid) {
                NotificationManager.showError(validation.error);
                return false;
            }
            
            currentFile = file;
            
            // Show file info
            updateFileInfo(file);
            
            // Auto-parse if enabled
            if (importConfig.validateOnLoad) {
                await parseFile(file);
            }
            
            EventBus.emit('import:file-selected', {
                file,
                size: file.size,
                type: file.type
            });
            
            return true;
            
        } catch (error) {
            console.error('File handling error:', error);
            NotificationManager.showError('Failed to process file');
            return false;
        }
    };
    
    /**
     * Validate file before processing
     */
    const validateFile = (file) => {
        if (!file) {
            return { valid: false, error: 'No file selected' };
        }
        
        // Check file size
        if (file.size > importConfig.maxFileSize) {
            const maxSizeMB = (importConfig.maxFileSize / 1024 / 1024).toFixed(1);
            return { valid: false, error: `File size exceeds ${maxSizeMB}MB limit` };
        }
        
        // Check file format
        const extension = getFileExtension(file.name);
        if (!importConfig.supportedFormats.includes(extension)) {
            return { 
                valid: false, 
                error: `Unsupported file format. Supported: ${importConfig.supportedFormats.join(', ')}` 
            };
        }
        
        return { valid: true };
    };
    
    /**
     * Parse file content based on format
     */
    const parseFile = async (file) => {
        try {
            NotificationManager.showLoadingOverlay('Parsing file...');
            
            const extension = getFileExtension(file.name);
            const content = await readFileContent(file);
            
            let parsed;
            switch (extension) {
                case 'json':
                    parsed = parseJSON(content);
                    break;
                case 'csv':
                    parsed = parseCSV(content);
                    break;
                case 'ics':
                    parsed = parseICS(content);
                    break;
                case 'xlsx':
                    parsed = await parseExcel(file);
                    break;
                default:
                    throw new Error(`Unsupported file format: ${extension}`);
            }
            
            // Validate record count
            if (parsed.records.length > importConfig.maxRecords) {
                throw new Error(`Too many records (${parsed.records.length}). Maximum allowed: ${importConfig.maxRecords}`);
            }
            
            parsedData = {
                ...parsed,
                file: {
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    lastModified: file.lastModified
                }
            };
            
            NotificationManager.hideLoadingOverlay();
            NotificationManager.showSuccess(`Successfully parsed ${parsed.records.length} records`);
            
            // Show preview if enabled
            if (importConfig.showPreview) {
                showPreviewModal();
            }
            
            EventBus.emit('import:file-parsed', {
                data: parsedData,
                recordCount: parsed.records.length
            });
            
            return parsedData;
            
        } catch (error) {
            console.error('File parsing error:', error);
            NotificationManager.hideLoadingOverlay();
            NotificationManager.showError(`Failed to parse file: ${error.message}`);
            return null;
        }
    };
    
    /**
     * Read file content as text
     */
    const readFileContent = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    };
    
    /**
     * Parse JSON file
     */
    const parseJSON = (content) => {
        try {
            const data = JSON.parse(content);
            const records = Array.isArray(data) ? data : (data.records || data.data || [data]);
            
            return {
                format: 'json',
                records: records.map(validateRecord),
                metadata: {
                    totalRecords: records.length,
                    validRecords: records.length
                }
            };
        } catch (error) {
            throw new Error('Invalid JSON format');
        }
    };
    
    /**
     * Parse CSV file
     */
    const parseCSV = (content) => {
        const lines = content.split('\\n').filter(line => line.trim());
        if (lines.length < 2) {
            throw new Error('CSV must have header row and at least one data row');
        }
        
        const headers = parseCSVLine(lines[0]);
        const records = [];
        const errors = [];
        
        for (let i = 1; i < lines.length; i++) {
            try {
                const values = parseCSVLine(lines[i]);
                const record = {};
                
                headers.forEach((header, index) => {
                    record[header.toLowerCase().replace(/[^a-z0-9]/g, '_')] = values[index] || '';
                });
                
                records.push(validateRecord(record));
            } catch (error) {
                errors.push({ line: i + 1, error: error.message });
            }
        }
        
        return {
            format: 'csv',
            records,
            metadata: {
                totalRecords: lines.length - 1,
                validRecords: records.length,
                errors
            }
        };
    };
    
    /**
     * Parse a single CSV line handling quoted values
     */
    const parseCSVLine = (line) => {
        const result = [];
        let current = '';
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === '"') {
                if (inQuotes && line[i + 1] === '"') {
                    current += '"';
                    i++; // Skip next quote
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        
        result.push(current.trim());
        return result;
    };
    
    /**
     * Parse ICS/iCal file
     */
    const parseICS = (content) => {
        const events = [];
        const eventBlocks = content.split('BEGIN:VEVENT');
        
        for (let i = 1; i < eventBlocks.length; i++) {
            const eventData = parseICSEvent(eventBlocks[i]);
            if (eventData) {
                events.push(validateRecord(eventData));
            }
        }
        
        return {
            format: 'ics',
            records: events,
            metadata: {
                totalRecords: eventBlocks.length - 1,
                validRecords: events.length
            }
        };
    };
    
    /**
     * Parse single ICS event
     */
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
                    case 'LOCATION':
                        event.location = value;
                        break;
                }
            }
        });
        
        return event.title && event.start ? event : null;
    };
    
    /**
     * Parse Excel file (requires SheetJS)
     */
    const parseExcel = async (file) => {
        if (typeof XLSX === 'undefined') {
            throw new Error('Excel import requires SheetJS library');
        }
        
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    
                    // Use first worksheet
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    
                    // Convert to JSON
                    const jsonData = XLSX.utils.sheet_to_json(worksheet);
                    
                    const records = jsonData.map(validateRecord);
                    
                    resolve({
                        format: 'excel',
                        records,
                        metadata: {
                            totalRecords: jsonData.length,
                            validRecords: records.length,
                            worksheetName: firstSheetName
                        }
                    });
                } catch (error) {
                    reject(new Error(`Excel parsing error: ${error.message}`));
                }
            };
            reader.onerror = () => reject(new Error('Failed to read Excel file'));
            reader.readAsArrayBuffer(file);
        });
    };
    
    /**
     * Format ICS datetime to standard format
     */
    const formatICSDateTime = (icsDateTime) => {
        const match = icsDateTime.match(/(\\d{4})(\\d{2})(\\d{2})T(\\d{2})(\\d{2})(\\d{2})/);
        if (match) {
            return `${match[1]}-${match[2]}-${match[3]} ${match[4]}:${match[5]}:${match[6]}`;
        }
        return icsDateTime;
    };
    
    /**
     * Validate and normalize record data
     */
    const validateRecord = (record) => {
        // Apply custom validation if provided
        if (importConfig.validateRecord && typeof importConfig.validateRecord === 'function') {
            return importConfig.validateRecord(record);
        }
        
        // Default validation - just return the record
        return record;
    };
    
    /**
     * Show preview modal
     */
    const showPreviewModal = () => {
        if (!parsedData || !parsedData.records.length) {
            NotificationManager.showWarning('No data to preview');
            return;
        }
        
        const modal = ModalManager.create({
            title: `Preview Import: ${parsedData.records.length} Records (${parsedData.format.toUpperCase()})`,
            size: 'large',
            body: generatePreviewTable(parsedData.records),
            footer: `
                <button type="button" class="btn btn-secondary" onclick="ModalManager.closeActive()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="ImportManager.confirmImport()">Import Data</button>
            `
        });
        
        modal.show();
    };
    
    /**
     * Generate preview table HTML
     */
    const generatePreviewTable = (records) => {
        if (!records.length) {
            return '<p>No records to preview.</p>';
        }
        
        // Get columns from first record
        const columns = Object.keys(records[0]);
        const previewRecords = records.slice(0, 10); // Show max 10 records
        
        let html = `
            <div class="import-preview">
                <div class="preview-info mb-3">
                    <span class="badge badge-info">${records.length} total records</span>
                    ${records.length > 10 ? '<span class="text-muted ml-2">(showing first 10)</span>' : ''}
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                ${columns.map(col => `<th>${escapeHtml(col)}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        previewRecords.forEach(record => {
            html += '<tr>';
            columns.forEach(col => {
                const value = record[col] || '';
                html += `<td>${escapeHtml(String(value))}</td>`;
            });
            html += '</tr>';
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        return html;
    };
    
    /**
     * Confirm and execute import
     */
    const confirmImport = async () => {
        if (!parsedData || !parsedData.records.length) {
            NotificationManager.showError('No data to import');
            return false;
        }
        
        try {
            // Close preview modal
            ModalManager.closeActive();
            
            // Show progress
            const progressId = NotificationManager.showProgress('Importing data...', 0, parsedData.records.length);
            
            // Execute import
            const result = await executeImport(parsedData.records, (current, total) => {
                NotificationManager.updateProgress(progressId, current, total);
            });
            
            if (result.success) {
                NotificationManager.showSuccess(`Successfully imported ${result.imported} of ${parsedData.records.length} records`);
                
                // Reset state
                resetImport();
                
                EventBus.emit('import:completed', {
                    imported: result.imported,
                    total: parsedData.records.length,
                    errors: result.errors || []
                });
            } else {
                throw new Error(result.error || 'Import failed');
            }
            
            return true;
            
        } catch (error) {
            console.error('Import error:', error);
            NotificationManager.showError(`Import failed: ${error.message}`);
            return false;
        }
    };
    
    /**
     * Execute the actual import (override this for specific implementations)
     */
    const executeImport = async (records, progressCallback = null) => {
        // This should be overridden by the specific implementation
        if (importConfig.importFunction && typeof importConfig.importFunction === 'function') {
            return await importConfig.importFunction(records, progressCallback);
        }
        
        // Default mock implementation
        let imported = 0;
        for (let i = 0; i < records.length; i++) {
            // Simulate import delay
            await new Promise(resolve => setTimeout(resolve, 50));
            
            imported++;
            if (progressCallback) {
                progressCallback(imported, records.length);
            }
        }
        
        return {
            success: true,
            imported,
            errors: []
        };
    };
    
    /**
     * Reset import state
     */
    const resetImport = () => {
        currentFile = null;
        parsedData = null;
        
        // Clear file input
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.value = '';
        });
        
        // Hide file info
        const fileInfos = document.querySelectorAll('.file-info');
        fileInfos.forEach(info => {
            info.style.display = 'none';
        });
        
        EventBus.emit('import:reset');
    };
    
    /**
     * Update file info display
     */
    const updateFileInfo = (file) => {
        const fileInfo = document.querySelector('.file-info') || createFileInfoElement();
        
        fileInfo.innerHTML = `
            <div class="file-details">
                <strong>File:</strong> ${file.name}<br>
                <strong>Size:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                <strong>Type:</strong> ${file.type || 'Unknown'}<br>
                <strong>Last Modified:</strong> ${new Date(file.lastModified).toLocaleString()}
            </div>
        `;
        fileInfo.style.display = 'block';
    };
    
    /**
     * Create file info element if it doesn't exist
     */
    const createFileInfoElement = () => {
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.style.cssText = `
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            display: none;
        `;
        
        // Insert after first file input found
        const fileInput = document.querySelector('input[type="file"]');
        if (fileInput && fileInput.parentNode) {
            fileInput.parentNode.insertBefore(fileInfo, fileInput.nextSibling);
        }
        
        return fileInfo;
    };
    
    /**
     * Get file extension
     */
    const getFileExtension = (filename) => {
        return filename.split('.').pop().toLowerCase();
    };
    
    /**
     * Escape HTML for security
     */
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    /**
     * Get supported formats with descriptions
     */
    const getSupportedFormats = () => {
        return {
            json: {
                name: 'JSON',
                description: 'JavaScript Object Notation',
                example: '[{"name": "John", "email": "john@example.com"}]'
            },
            csv: {
                name: 'CSV',
                description: 'Comma-separated values',
                example: 'name,email\\nJohn,john@example.com'
            },
            ics: {
                name: 'ICS/iCal',
                description: 'Calendar format',
                example: 'BEGIN:VEVENT\\nSUMMARY:Meeting\\nEND:VEVENT'
            },
            xlsx: {
                name: 'Excel',
                description: 'Microsoft Excel format',
                requiresLibrary: 'SheetJS (XLSX)'
            }
        };
    };
    
    // Make confirmImport available globally for modal buttons
    window.ImportManager = { confirmImport };
    
    return {
        initializeFileUpload,
        handleFile,
        parseFile,
        confirmImport,
        resetImport,
        getCurrentFile: () => currentFile,
        getParsedData: () => parsedData,
        getSupportedFormats,
        setConfig: (config) => { importConfig = { ...importConfig, ...config }; }
    };
})();