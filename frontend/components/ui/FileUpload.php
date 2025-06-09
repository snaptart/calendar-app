<?php
/**
 * FileUpload Component - Drag & drop file upload with validation
 * Location: frontend/components/ui/FileUpload.php
 * 
 * Configurable file upload component with drag & drop, validation, and preview
 */

class FileUpload {
    private $config;
    private $uploadId;
    private $validation;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'uploadId' => 'fileUpload',
        'name' => 'file',
        'multiple' => false,
        'maxFileSize' => 5 * 1024 * 1024, // 5MB
        'maxFiles' => 1,
        'acceptedTypes' => [], // e.g., ['image/*', '.pdf', '.doc']
        'acceptedExtensions' => [], // e.g., ['.jpg', '.png', '.pdf']
        'dragDrop' => true,
        'preview' => true,
        'thumbnails' => false,
        'progressBar' => true,
        'validation' => [
            'clientSide' => true,
            'serverSide' => true,
            'showErrors' => true
        ],
        'upload' => [
            'url' => null,
            'method' => 'POST',
            'headers' => [],
            'data' => [],
            'auto' => false, // Auto-upload on file select
            'chunked' => false,
            'chunkSize' => 1024 * 1024 // 1MB chunks
        ],
        'messages' => [
            'dragHere' => 'Drag and drop your files here',
            'clickBrowse' => 'or click to browse files',
            'selectFiles' => 'Select Files',
            'uploading' => 'Uploading...',
            'success' => 'Upload successful',
            'error' => 'Upload failed',
            'invalidType' => 'Invalid file type',
            'tooLarge' => 'File is too large',
            'tooManyFiles' => 'Too many files selected'
        ],
        'classes' => [
            'container' => 'file-upload-container',
            'dropZone' => 'drop-zone',
            'dropZoneActive' => 'drag-over',
            'fileInput' => 'file-input',
            'browseButton' => 'btn btn-outline',
            'fileList' => 'file-list',
            'fileItem' => 'file-item',
            'progressBar' => 'progress-bar',
            'error' => 'upload-error',
            'success' => 'upload-success'
        ],
        'icons' => [
            'upload' => '📎',
            'file' => '📄',
            'image' => '🖼️',
            'pdf' => '📑',
            'doc' => '📄',
            'excel' => '📊',
            'video' => '🎥',
            'audio' => '🎵',
            'archive' => '📦',
            'success' => '✅',
            'error' => '❌',
            'loading' => '⏳'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->uploadId = $this->config['uploadId'];
        $this->validation = $this->config['validation'];
    }
    
    /**
     * Set accepted file types
     */
    public function setAcceptedTypes($types) {
        $this->config['acceptedTypes'] = $types;
        return $this;
    }
    
    /**
     * Set accepted file extensions
     */
    public function setAcceptedExtensions($extensions) {
        $this->config['acceptedExtensions'] = $extensions;
        return $this;
    }
    
    /**
     * Set maximum file size
     */
    public function setMaxFileSize($size) {
        $this->config['maxFileSize'] = $size;
        return $this;
    }
    
    /**
     * Set maximum number of files
     */
    public function setMaxFiles($count) {
        $this->config['maxFiles'] = $count;
        if ($count > 1) {
            $this->config['multiple'] = true;
        }
        return $this;
    }
    
    /**
     * Set upload URL
     */
    public function setUploadUrl($url) {
        $this->config['upload']['url'] = $url;
        return $this;
    }
    
    /**
     * Enable auto-upload
     */
    public function setAutoUpload($enabled = true) {
        $this->config['upload']['auto'] = $enabled;
        return $this;
    }
    
    /**
     * Add upload header
     */
    public function addUploadHeader($name, $value) {
        $this->config['upload']['headers'][$name] = $value;
        return $this;
    }
    
    /**
     * Add upload data
     */
    public function addUploadData($name, $value) {
        $this->config['upload']['data'][$name] = $value;
        return $this;
    }
    
    /**
     * Render the file upload component
     */
    public function render() {
        ?>
        <div id="<?php echo htmlspecialchars($this->uploadId); ?>Container" 
             class="<?php echo htmlspecialchars($this->config['classes']['container']); ?>">
            
            <?php $this->renderDropZone(); ?>
            <?php $this->renderFileInput(); ?>
            <?php $this->renderFileList(); ?>
            <?php $this->renderProgressArea(); ?>
            <?php $this->renderMessages(); ?>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateFileUploadJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render drop zone
     */
    private function renderDropZone() {
        if (!$this->config['dragDrop']) {
            return;
        }
        
        ?>
        <div id="<?php echo htmlspecialchars($this->uploadId); ?>DropZone" 
             class="<?php echo htmlspecialchars($this->config['classes']['dropZone']); ?>">
            
            <div class="drop-zone-content">
                <div class="drop-zone-icon">
                    <?php echo $this->config['icons']['upload']; ?>
                </div>
                <div class="drop-zone-text">
                    <h4><?php echo htmlspecialchars($this->config['messages']['dragHere']); ?></h4>
                    <p><?php echo htmlspecialchars($this->config['messages']['clickBrowse']); ?></p>
                </div>
                <button type="button" 
                        class="<?php echo htmlspecialchars($this->config['classes']['browseButton']); ?>"
                        onclick="document.getElementById('<?php echo htmlspecialchars($this->uploadId); ?>Input').click()">
                    <?php echo htmlspecialchars($this->config['messages']['selectFiles']); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render file input
     */
    private function renderFileInput() {
        $accept = '';
        if (!empty($this->config['acceptedTypes'])) {
            $accept = ' accept="' . htmlspecialchars(implode(',', $this->config['acceptedTypes'])) . '"';
        }
        
        $multiple = $this->config['multiple'] ? ' multiple' : '';
        
        ?>
        <input type="file" 
               id="<?php echo htmlspecialchars($this->uploadId); ?>Input"
               name="<?php echo htmlspecialchars($this->config['name']); ?><?php echo $this->config['multiple'] ? '[]' : ''; ?>"
               class="<?php echo htmlspecialchars($this->config['classes']['fileInput']); ?>"
               <?php echo $accept; ?>
               <?php echo $multiple; ?>
               style="display: none;">
        <?php
    }
    
    /**
     * Render file list
     */
    private function renderFileList() {
        ?>
        <div id="<?php echo htmlspecialchars($this->uploadId); ?>FileList" 
             class="<?php echo htmlspecialchars($this->config['classes']['fileList']); ?>"
             style="display: none;">
            <!-- Selected files will be displayed here -->
        </div>
        <?php
    }
    
    /**
     * Render progress area
     */
    private function renderProgressArea() {
        if (!$this->config['progressBar']) {
            return;
        }
        
        ?>
        <div id="<?php echo htmlspecialchars($this->uploadId); ?>Progress" 
             class="upload-progress"
             style="display: none;">
            <div class="progress-info">
                <span class="progress-text"><?php echo htmlspecialchars($this->config['messages']['uploading']); ?></span>
                <span class="progress-percentage">0%</span>
            </div>
            <div class="<?php echo htmlspecialchars($this->config['classes']['progressBar']); ?>">
                <div class="progress-fill" style="width: 0%;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render message area
     */
    private function renderMessages() {
        ?>
        <div id="<?php echo htmlspecialchars($this->uploadId); ?>Messages" class="upload-messages">
            <!-- Messages will be displayed here -->
        </div>
        <?php
    }
    
    /**
     * Generate file upload JavaScript
     */
    private function generateFileUploadJs() {
        $uploadId = $this->uploadId;
        $config = json_encode($this->config);
        
        return "
        // Initialize FileUpload: {$uploadId}
        (function() {
            const config = {$config};
            const uploadId = '{$uploadId}';
            
            // Elements
            const container = document.getElementById(uploadId + 'Container');
            const dropZone = document.getElementById(uploadId + 'DropZone');
            const fileInput = document.getElementById(uploadId + 'Input');
            const fileList = document.getElementById(uploadId + 'FileList');
            const progressArea = document.getElementById(uploadId + 'Progress');
            const messagesArea = document.getElementById(uploadId + 'Messages');
            
            // State
            let selectedFiles = [];
            let isUploading = false;
            
            // Initialize
            if (!container) return;
            
            // File input change handler
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    handleFileSelect(e.target.files);
                });
            }
            
            // Drag and drop handlers
            if (dropZone && config.dragDrop) {
                dropZone.addEventListener('dragover', handleDragOver);
                dropZone.addEventListener('dragleave', handleDragLeave);
                dropZone.addEventListener('drop', handleDrop);
                dropZone.addEventListener('click', function() {
                    if (fileInput) fileInput.click();
                });
            }
            
            function handleDragOver(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add(config.classes.dropZoneActive);
            }
            
            function handleDragLeave(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Only remove class if we're leaving the drop zone entirely
                if (!dropZone.contains(e.relatedTarget)) {
                    dropZone.classList.remove(config.classes.dropZoneActive);
                }
            }
            
            function handleDrop(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove(config.classes.dropZoneActive);
                
                const files = e.dataTransfer.files;
                handleFileSelect(files);
            }
            
            function handleFileSelect(files) {
                if (!files || files.length === 0) return;
                
                // Convert FileList to Array
                const fileArray = Array.from(files);
                
                // Validate files
                const validFiles = validateFiles(fileArray);
                
                if (validFiles.length === 0) return;
                
                // Check max files limit
                if (selectedFiles.length + validFiles.length > config.maxFiles) {
                    showMessage(config.messages.tooManyFiles, 'error');
                    return;
                }
                
                // Add valid files
                selectedFiles = selectedFiles.concat(validFiles);
                
                // Display files
                displayFiles();
                
                // Auto-upload if enabled
                if (config.upload.auto && config.upload.url) {
                    uploadFiles();
                }
                
                // Emit file select event
                const selectEvent = new CustomEvent('fileSelect', {
                    detail: { 
                        uploadId: uploadId, 
                        files: validFiles,
                        allFiles: selectedFiles 
                    }
                });
                document.dispatchEvent(selectEvent);
            }
            
            function validateFiles(files) {
                const validFiles = [];
                
                for (const file of files) {
                    const validation = validateFile(file);
                    
                    if (validation.valid) {
                        validFiles.push(file);
                    } else if (config.validation.showErrors) {
                        showMessage(validation.error, 'error');
                    }
                }
                
                return validFiles;
            }
            
            function validateFile(file) {
                // Check file size
                if (file.size > config.maxFileSize) {
                    return {
                        valid: false,
                        error: config.messages.tooLarge + ' (max: ' + formatFileSize(config.maxFileSize) + ')'
                    };
                }
                
                // Check file type/extension
                if (config.acceptedExtensions.length > 0) {
                    const fileExt = '.' + file.name.split('.').pop().toLowerCase();
                    if (!config.acceptedExtensions.includes(fileExt)) {
                        return {
                            valid: false,
                            error: config.messages.invalidType + ' (allowed: ' + config.acceptedExtensions.join(', ') + ')'
                        };
                    }
                }
                
                if (config.acceptedTypes.length > 0) {
                    const isValidType = config.acceptedTypes.some(type => {
                        if (type.includes('*')) {
                            const baseType = type.split('/')[0];
                            return file.type.startsWith(baseType);
                        }
                        return file.type === type;
                    });
                    
                    if (!isValidType) {
                        return {
                            valid: false,
                            error: config.messages.invalidType
                        };
                    }
                }
                
                return { valid: true };
            }
            
            function displayFiles() {
                if (!fileList) return;
                
                fileList.innerHTML = '';
                fileList.style.display = selectedFiles.length > 0 ? 'block' : 'none';
                
                selectedFiles.forEach((file, index) => {
                    const fileItem = createFileItem(file, index);
                    fileList.appendChild(fileItem);
                });
            }
            
            function createFileItem(file, index) {
                const item = document.createElement('div');
                item.className = config.classes.fileItem;
                item.dataset.index = index;
                
                const icon = getFileIcon(file);
                const size = formatFileSize(file.size);
                
                item.innerHTML = `
                    <div class='file-info'>
                        <span class='file-icon'>${icon}</span>
                        <div class='file-details'>
                            <div class='file-name'>${escapeHtml(file.name)}</div>
                            <div class='file-size'>${size}</div>
                        </div>
                    </div>
                    <div class='file-actions'>
                        <button type='button' class='btn btn-small btn-outline' onclick='removeFile(${index})'>
                            Remove
                        </button>
                    </div>
                `;
                
                return item;
            }
            
            function getFileIcon(file) {
                const type = file.type.toLowerCase();
                const ext = file.name.split('.').pop().toLowerCase();
                
                if (type.startsWith('image/')) return config.icons.image;
                if (type.includes('pdf')) return config.icons.pdf;
                if (type.includes('word') || ext === 'doc' || ext === 'docx') return config.icons.doc;
                if (type.includes('excel') || type.includes('spreadsheet') || ext === 'xls' || ext === 'xlsx') return config.icons.excel;
                if (type.startsWith('video/')) return config.icons.video;
                if (type.startsWith('audio/')) return config.icons.audio;
                if (type.includes('zip') || type.includes('rar') || type.includes('archive')) return config.icons.archive;
                
                return config.icons.file;
            }
            
            function removeFile(index) {
                selectedFiles.splice(index, 1);
                displayFiles();
                
                // Clear file input if no files selected
                if (selectedFiles.length === 0 && fileInput) {
                    fileInput.value = '';
                }
                
                // Emit file remove event
                const removeEvent = new CustomEvent('fileRemove', {
                    detail: { 
                        uploadId: uploadId, 
                        index: index,
                        remainingFiles: selectedFiles 
                    }
                });
                document.dispatchEvent(removeEvent);
            }
            
            function uploadFiles() {
                if (!config.upload.url || selectedFiles.length === 0 || isUploading) {
                    return;
                }
                
                isUploading = true;
                showProgress(true);
                showMessage(config.messages.uploading, 'info');
                
                const formData = new FormData();
                
                // Add files
                selectedFiles.forEach((file, index) => {
                    const fieldName = config.multiple ? config.name + '[' + index + ']' : config.name;
                    formData.append(fieldName, file);
                });
                
                // Add additional data
                Object.keys(config.upload.data).forEach(key => {
                    formData.append(key, config.upload.data[key]);
                });
                
                // Create request
                const xhr = new XMLHttpRequest();
                
                // Progress handler
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentage = Math.round((e.loaded / e.total) * 100);
                        updateProgress(percentage);
                    }
                });
                
                // Success handler
                xhr.addEventListener('load', function() {
                    isUploading = false;
                    showProgress(false);
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        let response;
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            response = { success: true, message: config.messages.success };
                        }
                        
                        showMessage(response.message || config.messages.success, 'success');
                        
                        // Emit upload success event
                        const successEvent = new CustomEvent('uploadSuccess', {
                            detail: { 
                                uploadId: uploadId, 
                                response: response,
                                files: selectedFiles 
                            }
                        });
                        document.dispatchEvent(successEvent);
                        
                        // Clear files on success
                        clearFiles();
                        
                    } else {
                        let errorMessage = config.messages.error;
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.error || errorResponse.message || errorMessage;
                        } catch (e) {
                            errorMessage += ' (HTTP ' + xhr.status + ')';
                        }
                        
                        showMessage(errorMessage, 'error');
                        
                        // Emit upload error event
                        const errorEvent = new CustomEvent('uploadError', {
                            detail: { 
                                uploadId: uploadId, 
                                error: errorMessage,
                                status: xhr.status 
                            }
                        });
                        document.dispatchEvent(errorEvent);
                    }
                });
                
                // Error handler
                xhr.addEventListener('error', function() {
                    isUploading = false;
                    showProgress(false);
                    showMessage(config.messages.error, 'error');
                    
                    const errorEvent = new CustomEvent('uploadError', {
                        detail: { 
                            uploadId: uploadId, 
                            error: 'Network error' 
                        }
                    });
                    document.dispatchEvent(errorEvent);
                });
                
                // Send request
                xhr.open(config.upload.method, config.upload.url);
                
                // Add headers
                Object.keys(config.upload.headers).forEach(key => {
                    xhr.setRequestHeader(key, config.upload.headers[key]);
                });
                
                xhr.send(formData);
            }
            
            function showProgress(show) {
                if (!progressArea) return;
                
                progressArea.style.display = show ? 'block' : 'none';
                if (!show) {
                    updateProgress(0);
                }
            }
            
            function updateProgress(percentage) {
                if (!progressArea) return;
                
                const progressFill = progressArea.querySelector('.progress-fill');
                const progressText = progressArea.querySelector('.progress-percentage');
                
                if (progressFill) {
                    progressFill.style.width = percentage + '%';
                }
                
                if (progressText) {
                    progressText.textContent = percentage + '%';
                }
            }
            
            function showMessage(message, type = 'info') {
                if (!messagesArea) return;
                
                const messageEl = document.createElement('div');
                messageEl.className = 'upload-message ' + type;
                messageEl.textContent = message;
                
                messagesArea.appendChild(messageEl);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (messageEl.parentNode) {
                        messageEl.parentNode.removeChild(messageEl);
                    }
                }, 5000);
            }
            
            function clearFiles() {
                selectedFiles = [];
                if (fileInput) fileInput.value = '';
                displayFiles();
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Global functions
            window.removeFile = removeFile;
            
            // Expose upload API
            window[uploadId] = {
                upload: uploadFiles,
                clear: clearFiles,
                getFiles: function() { return selectedFiles; },
                addFile: function(file) { handleFileSelect([file]); },
                isUploading: function() { return isUploading; },
                setUploadUrl: function(url) { config.upload.url = url; },
                showMessage: showMessage
            };
            
        })();
        ";
    }
    
    /**
     * Render with default styling
     */
    public function renderWithStyles() {
        ?>
        <style>
        .file-upload-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .drop-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 48px 24px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f7fafc;
            margin-bottom: 24px;
        }
        
        .drop-zone:hover,
        .drop-zone.drag-over {
            border-color: #4299e1;
            background: #ebf8ff;
            transform: scale(1.02);
        }
        
        .drop-zone-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        
        .drop-zone-icon {
            font-size: 3rem;
            color: #a0aec0;
        }
        
        .drop-zone.drag-over .drop-zone-icon {
            color: #4299e1;
        }
        
        .drop-zone-text h4 {
            margin: 0 0 8px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .drop-zone-text p {
            margin: 0;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .file-list {
            margin: 20px 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .file-icon {
            font-size: 1.5rem;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #2d3748;
            font-size: 0.875rem;
        }
        
        .file-size {
            color: #718096;
            font-size: 0.75rem;
        }
        
        .upload-progress {
            margin: 20px 0;
            padding: 16px;
            background: #f7fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #4299e1;
            transition: width 0.3s ease;
        }
        
        .upload-messages {
            margin-top: 16px;
        }
        
        .upload-message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }
        
        .upload-message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .upload-message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .upload-message.info {
            background: #bee3f8;
            color: #2a4365;
            border: 1px solid #90cdf4;
        }
        
        @media (max-width: 480px) {
            .drop-zone {
                padding: 32px 16px;
            }
            
            .file-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .file-actions {
                width: 100%;
                display: flex;
                justify-content: flex-end;
            }
        }
        </style>
        <?php
        
        $this->render();
    }
    
    /**
     * Create a file upload component
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Create an image upload component
     */
    public static function createImageUpload($config = []) {
        $imageConfig = array_merge([
            'acceptedTypes' => ['image/*'],
            'acceptedExtensions' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
            'thumbnails' => true,
            'preview' => true
        ], $config);
        
        return new self($imageConfig);
    }
    
    /**
     * Create a document upload component
     */
    public static function createDocumentUpload($config = []) {
        $docConfig = array_merge([
            'acceptedTypes' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'acceptedExtensions' => ['.pdf', '.doc', '.docx'],
            'maxFileSize' => 10 * 1024 * 1024 // 10MB
        ], $config);
        
        return new self($docConfig);
    }
}