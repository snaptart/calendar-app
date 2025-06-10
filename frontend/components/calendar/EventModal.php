<?php
/**
 * Event Modal Component - REFACTORED (HTML Generation Only)
 * Location: frontend/components/calendar/EventModal.php
 * 
 * Generates semantic HTML with data attributes for JavaScript initialization
 * All behavior and modal logic handled by JavaScript
 */

class EventModal {
    private $config;
    private $modalId;
    private $formFields;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'modalId' => 'eventModal',
        'size' => 'medium',
        'backdrop' => 'static',
        'keyboard' => true,
        'closeOnEscape' => true,
        'autoShow' => false,
        'title' => [
            'create' => 'Add Event',
            'edit' => 'Edit Event',
            'view' => 'Event Details'
        ],
        'fields' => [
            'title' => [
                'type' => 'text',
                'label' => 'Event Title',
                'required' => true,
                'placeholder' => 'Enter event title...',
                'validation' => 'required|minlength:3'
            ],
            'start' => [
                'type' => 'datetime',
                'label' => 'Start Date & Time',
                'required' => true,
                'placeholder' => 'Select start date & time...',
                'validation' => 'required'
            ],
            'end' => [
                'type' => 'datetime',
                'label' => 'End Date & Time',
                'required' => true,
                'placeholder' => 'Select end date & time...',
                'validation' => 'required'
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Description',
                'required' => false,
                'placeholder' => 'Event description (optional)...',
                'rows' => 3
            ]
        ],
        'buttons' => [
            'save' => [
                'text' => 'Save Event',
                'class' => 'btn btn-primary',
                'type' => 'submit',
                'action' => 'save-event'
            ],
            'delete' => [
                'text' => 'Delete Event',
                'class' => 'btn btn-danger',
                'type' => 'button',
                'action' => 'delete-event',
                'showOnEdit' => true,
                'confirm' => 'Are you sure you want to delete this event?'
            ],
            'cancel' => [
                'text' => 'Cancel',
                'class' => 'btn btn-outline',
                'type' => 'button',
                'action' => 'close-modal'
            ]
        ],
        'permissions' => [
            'canCreate' => true,
            'canEdit' => true,
            'canDelete' => true,
            'editOwnOnly' => true
        ],
        'validation' => [
            'clientSide' => true,
            'serverSide' => true,
            'realTime' => false
        ],
        'classes' => [
            'modal' => 'modal event-modal',
            'content' => 'modal-content',
            'header' => 'modal-header',
            'body' => 'modal-body',
            'footer' => 'modal-footer',
            'form' => 'event-form',
            'viewMode' => 'modal-view-mode',
            'editMode' => 'modal-edit-mode'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->modalId = $this->config['modalId'];
        $this->formFields = $this->config['fields'];
    }
    
    /**
     * Render the modal HTML with data attributes
     */
    public function render() {
        $modalConfig = [
            'size' => $this->config['size'],
            'backdrop' => $this->config['backdrop'],
            'keyboard' => $this->config['keyboard'],
            'closeOnEscape' => $this->config['closeOnEscape'],
            'autoShow' => $this->config['autoShow']
        ];
        
        ?>
        <div id="<?php echo htmlspecialchars($this->modalId); ?>" 
             class="<?php echo htmlspecialchars($this->config['classes']['modal']); ?>"
             data-component="modal"
             data-component-id="<?php echo htmlspecialchars($this->modalId); ?>"
             data-modal-type="event"
             data-config='<?php echo json_encode($modalConfig, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-permissions='<?php echo json_encode($this->config['permissions'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-validation='<?php echo json_encode($this->config['validation'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-auto-init="true">
            
            <div class="<?php echo htmlspecialchars($this->config['classes']['content']); ?>">
                <?php $this->renderHeader(); ?>
                
                <div class="<?php echo htmlspecialchars($this->config['classes']['body']); ?>">
                    <?php $this->renderViewMode(); ?>
                    <?php $this->renderEditMode(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render modal header
     */
    private function renderHeader() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['header']); ?>">
            <h2 id="<?php echo $this->modalId; ?>Title"
                data-component="modal-title"
                data-titles='<?php echo json_encode($this->config['title'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                <?php echo htmlspecialchars($this->config['title']['create']); ?>
            </h2>
            <span class="close modal-close"
                  data-action="close-modal"
                  data-target="#<?php echo htmlspecialchars($this->modalId); ?>">&times;</span>
        </div>
        <?php
    }
    
    /**
     * Render view mode (read-only event details)
     */
    private function renderViewMode() {
        ?>
        <div id="<?php echo $this->modalId; ?>ViewMode" 
             class="<?php echo htmlspecialchars($this->config['classes']['viewMode']); ?>" 
             style="display: none;"
             data-component="modal-view"
             data-component-id="<?php echo $this->modalId; ?>-view">
            
            <div class="event-details">
                <div class="detail-grid">
                    <div class="detail-row">
                        <label>Title:</label>
                        <span id="<?php echo $this->modalId; ?>ViewTitle" 
                              data-field="title">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Start:</label>
                        <span id="<?php echo $this->modalId; ?>ViewStart" 
                              data-field="start"
                              data-format="datetime">-</span>
                    </div>
                    <div class="detail-row">
                        <label>End:</label>
                        <span id="<?php echo $this->modalId; ?>ViewEnd" 
                              data-field="end"
                              data-format="datetime">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Duration:</label>
                        <span id="<?php echo $this->modalId; ?>ViewDuration" 
                              data-field="duration"
                              data-format="duration">-</span>
                    </div>
                    <div class="detail-row">
                        <label>Owner:</label>
                        <span id="<?php echo $this->modalId; ?>ViewOwner" 
                              data-field="owner">-</span>
                    </div>
                    <?php if (isset($this->formFields['description'])): ?>
                        <div class="detail-row">
                            <label>Description:</label>
                            <span id="<?php echo $this->modalId; ?>ViewDescription" 
                                  data-field="description">-</span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <label>Status:</label>
                        <span id="<?php echo $this->modalId; ?>ViewStatus" 
                              data-field="status"
                              data-format="status">-</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <?php if ($this->config['permissions']['canEdit']): ?>
                    <button type="button" 
                            id="<?php echo $this->modalId; ?>EditBtn" 
                            class="btn btn-primary"
                            data-component="button"
                            data-action="switch-to-edit"
                            data-target="#<?php echo $this->modalId; ?>">
                        ✏️ Edit Event
                    </button>
                <?php endif; ?>
                
                <?php if ($this->config['permissions']['canDelete']): ?>
                    <button type="button" 
                            id="<?php echo $this->modalId; ?>DeleteFromViewBtn" 
                            class="btn btn-danger"
                            data-component="button"
                            data-action="delete-event"
                            data-confirm="Are you sure you want to delete this event?">
                        🗑️ Delete Event
                    </button>
                <?php endif; ?>
                
                <button type="button" 
                        class="btn btn-outline modal-close"
                        data-action="close-modal"
                        data-target="#<?php echo htmlspecialchars($this->modalId); ?>">
                    Close
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render edit mode (form for creating/editing)
     */
    private function renderEditMode() {
        ?>
        <div id="<?php echo $this->modalId; ?>EditMode" 
             class="<?php echo htmlspecialchars($this->config['classes']['editMode']); ?>"
             data-component="modal-edit"
             data-component-id="<?php echo $this->modalId; ?>-edit">
            
            <form id="<?php echo $this->modalId; ?>Form"
                  class="<?php echo htmlspecialchars($this->config['classes']['form']); ?>"
                  data-component="form"
                  data-component-id="<?php echo $this->modalId; ?>-form"
                  data-validation='<?php echo json_encode($this->config['validation'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                  data-submit-method="POST"
                  data-auto-init="true"
                  novalidate>
                
                <?php $this->renderFormFields(); ?>
                <?php $this->renderFormActions(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render form fields
     */
    private function renderFormFields() {
        foreach ($this->formFields as $fieldName => $fieldConfig) {
            $this->renderFormField($fieldName, $fieldConfig);
        }
    }
    
    /**
     * Render individual form field
     */
    private function renderFormField($fieldName, $fieldConfig) {
        $fieldId = $this->modalId . ucfirst($fieldName);
        $required = $fieldConfig['required'] ?? false;
        $requiredAttr = $required ? 'required' : '';
        $requiredMark = $required ? ' *' : '';
        
        // Build validation attributes
        $validationAttr = '';
        if (!empty($fieldConfig['validation'])) {
            $validationAttr = 'data-validate="' . htmlspecialchars($fieldConfig['validation']) . '"';
        }
        
        ?>
        <div class="form-group">
            <label for="<?php echo htmlspecialchars($fieldId); ?>">
                <?php echo htmlspecialchars($fieldConfig['label']); ?><?php echo $requiredMark; ?>
            </label>
            
            <?php
            switch ($fieldConfig['type']) {
                case 'textarea':
                    $this->renderTextarea($fieldId, $fieldConfig, $requiredAttr, $validationAttr);
                    break;
                case 'datetime':
                    $this->renderDateTimeInput($fieldId, $fieldConfig, $requiredAttr, $validationAttr);
                    break;
                case 'select':
                    $this->renderSelect($fieldId, $fieldConfig, $requiredAttr, $validationAttr);
                    break;
                default:
                    $this->renderTextInput($fieldId, $fieldConfig, $requiredAttr, $validationAttr);
                    break;
            }
            ?>
            
            <?php if (isset($fieldConfig['help'])): ?>
                <small class="form-help"><?php echo htmlspecialchars($fieldConfig['help']); ?></small>
            <?php endif; ?>
            
            <div class="form-error" 
                 id="<?php echo htmlspecialchars($fieldId); ?>Error"
                 style="display: none;"></div>
        </div>
        <?php
    }
    
    /**
     * Render text input
     */
    private function renderTextInput($fieldId, $fieldConfig, $requiredAttr, $validationAttr) {
        ?>
        <input 
            type="<?php echo htmlspecialchars($fieldConfig['inputType'] ?? 'text'); ?>"
            id="<?php echo htmlspecialchars($fieldId); ?>"
            name="<?php echo htmlspecialchars(strtolower(str_replace($this->modalId, '', $fieldId))); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control"
            <?php echo $requiredAttr; ?>
            <?php echo $validationAttr; ?>
            <?php if (isset($fieldConfig['maxlength'])): ?>
                maxlength="<?php echo intval($fieldConfig['maxlength']); ?>"
            <?php endif; ?>
            data-field-type="text"
        >
        <?php
    }
    
    /**
     * Render textarea
     */
    private function renderTextarea($fieldId, $fieldConfig, $requiredAttr, $validationAttr) {
        ?>
        <textarea 
            id="<?php echo htmlspecialchars($fieldId); ?>"
            name="<?php echo htmlspecialchars(strtolower(str_replace($this->modalId, '', $fieldId))); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control"
            rows="<?php echo intval($fieldConfig['rows'] ?? 3); ?>"
            <?php echo $requiredAttr; ?>
            <?php echo $validationAttr; ?>
            data-field-type="textarea"
        ></textarea>
        <?php
    }
    
    /**
     * Render datetime input
     */
    private function renderDateTimeInput($fieldId, $fieldConfig, $requiredAttr, $validationAttr) {
        ?>
        <input 
            type="text"
            id="<?php echo htmlspecialchars($fieldId); ?>"
            name="<?php echo htmlspecialchars(strtolower(str_replace($this->modalId, '', $fieldId))); ?>"
            placeholder="<?php echo htmlspecialchars($fieldConfig['placeholder'] ?? ''); ?>"
            class="form-control datetime-picker"
            <?php echo $requiredAttr; ?>
            <?php echo $validationAttr; ?>
            data-field-type="datetime"
            data-datetime-config='{"format": "Y-m-d H:i", "step": 15}'
            readonly
        >
        <?php
    }
    
    /**
     * Render select dropdown
     */
    private function renderSelect($fieldId, $fieldConfig, $requiredAttr, $validationAttr) {
        ?>
        <select 
            id="<?php echo htmlspecialchars($fieldId); ?>"
            name="<?php echo htmlspecialchars(strtolower(str_replace($this->modalId, '', $fieldId))); ?>"
            class="form-control"
            <?php echo $requiredAttr; ?>
            <?php echo $validationAttr; ?>
            data-field-type="select"
        >
            <?php if (isset($fieldConfig['placeholder'])): ?>
                <option value=""><?php echo htmlspecialchars($fieldConfig['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php if (isset($fieldConfig['options'])): ?>
                <?php foreach ($fieldConfig['options'] as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
    }
    
    /**
     * Render form actions
     */
    private function renderFormActions() {
        ?>
        <div class="form-actions">
            <?php foreach ($this->config['buttons'] as $buttonName => $buttonConfig): ?>
                <?php $this->renderButton($buttonName, $buttonConfig); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render individual button
     */
    private function renderButton($buttonName, $buttonConfig) {
        $buttonId = $this->modalId . ucfirst($buttonName) . 'Btn';
        $style = '';
        
        if ($buttonName === 'delete' && ($buttonConfig['showOnEdit'] ?? false)) {
            $style = 'style="display: none;"';
        }
        
        ?>
        <button 
            type="<?php echo htmlspecialchars($buttonConfig['type']); ?>"
            id="<?php echo htmlspecialchars($buttonId); ?>"
            class="<?php echo htmlspecialchars($buttonConfig['class']); ?>"
            data-component="button"
            data-action="<?php echo htmlspecialchars($buttonConfig['action']); ?>"
            data-target="#<?php echo htmlspecialchars($this->modalId); ?>"
            <?php if (isset($buttonConfig['confirm'])): ?>
                data-confirm="<?php echo htmlspecialchars($buttonConfig['confirm']); ?>"
            <?php endif; ?>
            <?php echo $style; ?>
        >
            <?php echo htmlspecialchars($buttonConfig['text']); ?>
        </button>
        <?php
    }
    
    /**
     * Set modal configuration
     */
    public function setConfig($config) {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }
    
    /**
     * Set form fields
     */
    public function setFields($fields) {
        $this->formFields = array_merge($this->formFields, $fields);
        $this->config['fields'] = $this->formFields;
        return $this;
    }
    
    /**
     * Add form field
     */
    public function addField($name, $config) {
        $this->formFields[$name] = $config;
        $this->config['fields'][$name] = $config;
        return $this;
    }
    
    /**
     * Set permissions
     */
    public function setPermissions($permissions) {
        $this->config['permissions'] = array_merge($this->config['permissions'], $permissions);
        return $this;
    }
    
    /**
     * Set validation rules
     */
    public function setValidation($validation) {
        $this->config['validation'] = array_merge($this->config['validation'], $validation);
        return $this;
    }
    
    /**
     * Set button configuration
     */
    public function setButtons($buttons) {
        $this->config['buttons'] = array_merge($this->config['buttons'], $buttons);
        return $this;
    }
    
    /**
     * Create a modal with configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method
     */
    public static function render($config = []) {
        $modal = new self($config);
        $modal->render();
    }
    
    /**
     * Render minimal modal (just structure, no form)
     */
    public static function renderMinimal($modalId, $title = 'Modal', $content = '') {
        $config = [
            'modalId' => $modalId,
            'title' => ['create' => $title],
            'fields' => []
        ];
        
        ?>
        <div id="<?php echo htmlspecialchars($modalId); ?>" 
             class="modal"
             data-component="modal"
             data-component-id="<?php echo htmlspecialchars($modalId); ?>"
             data-auto-init="true">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php echo htmlspecialchars($title); ?></h2>
                    <span class="close" data-action="close-modal">&times;</span>
                </div>
                
                <div class="modal-body">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
        <?php
    }
}