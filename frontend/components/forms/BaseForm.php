<?php
/**
 * BaseForm Component - Reusable form builder with validation
 * Location: frontend/components/forms/BaseForm.php
 * 
 * Flexible form component with validation, CSRF protection, and auto-generation
 */

class BaseForm {
    private $config;
    private $formId;
    private $fields;
    private $errors;
    private $values;
    private $rules;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'formId' => 'form',
        'method' => 'POST',
        'action' => '',
        'enctype' => 'application/x-www-form-urlencoded',
        'class' => 'form',
        'novalidate' => false,
        'autocomplete' => 'on',
        'validation' => [
            'clientSide' => true,
            'realTime' => false,
            'showErrors' => true,
            'errorClass' => 'error',
            'successClass' => 'success'
        ],
        'csrf' => [
            'enabled' => true,
            'field' => 'csrf_token',
            'value' => null // Auto-generated if null
        ],
        'submission' => [
            'ajax' => false,
            'url' => null,
            'beforeSubmit' => null,
            'onSuccess' => null,
            'onError' => null,
            'loadingText' => 'Processing...',
            'resetOnSuccess' => false
        ],
        'layout' => [
            'horizontal' => false,
            'labelWidth' => 'auto',
            'fieldWrapper' => 'form-group',
            'rowWrapper' => 'form-row',
            'actionsWrapper' => 'form-actions'
        ],
        'messages' => [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
            'url' => 'Please enter a valid URL',
            'number' => 'Please enter a valid number',
            'min' => 'Value must be at least {min}',
            'max' => 'Value must be no more than {max}',
            'minlength' => 'Must be at least {min} characters',
            'maxlength' => 'Must be no more than {max} characters',
            'pattern' => 'Please match the required format'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->formId = $this->config['formId'];
        $this->fields = [];
        $this->errors = [];
        $this->values = [];
        $this->rules = [];
        
        // Auto-generate CSRF token if needed
        if ($this->config['csrf']['enabled'] && !$this->config['csrf']['value']) {
            $this->config['csrf']['value'] = $this->generateCSRFToken();
        }
    }
    
    /**
     * Add form field
     */
    public function addField($config) {
        $defaultField = [
            'name' => '',
            'type' => 'text',
            'label' => '',
            'placeholder' => '',
            'value' => '',
            'required' => false,
            'disabled' => false,
            'readonly' => false,
            'class' => 'form-control',
            'id' => null,
            'attributes' => [],
            'validation' => [],
            'help' => '',
            'wrapper' => null,
            'labelClass' => 'form-label',
            'errorClass' => 'form-error',
            'options' => [], // For select, radio, checkbox groups
            'multiple' => false,
            'rows' => 3, // For textarea
            'cols' => null,
            'accept' => null, // For file inputs
            'pattern' => null,
            'min' => null,
            'max' => null,
            'step' => null,
            'minlength' => null,
            'maxlength' => null
        ];
        
        $field = array_merge($defaultField, $config);
        
        // Auto-generate ID if not provided
        if (!$field['id']) {
            $field['id'] = $this->formId . '_' . $field['name'];
        }
        
        // Add validation rules
        if (!empty($field['validation'])) {
            $this->rules[$field['name']] = $field['validation'];
        }
        
        $this->fields[] = $field;
        return $this;
    }
    
    /**
     * Add multiple fields
     */
    public function addFields($fields) {
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }
    
    /**
     * Add text field
     */
    public function addText($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'text',
            'label' => $label
        ], $config));
    }
    
    /**
     * Add email field
     */
    public function addEmail($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'email',
            'label' => $label,
            'validation' => ['email']
        ], $config));
    }
    
    /**
     * Add password field
     */
    public function addPassword($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'password',
            'label' => $label,
            'autocomplete' => 'current-password'
        ], $config));
    }
    
    /**
     * Add textarea field
     */
    public function addTextarea($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'textarea',
            'label' => $label
        ], $config));
    }
    
    /**
     * Add select field
     */
    public function addSelect($name, $label, $options, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'select',
            'label' => $label,
            'options' => $options
        ], $config));
    }
    
    /**
     * Add checkbox field
     */
    public function addCheckbox($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'checkbox',
            'label' => $label,
            'value' => '1'
        ], $config));
    }
    
    /**
     * Add radio group
     */
    public function addRadio($name, $label, $options, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'radio',
            'label' => $label,
            'options' => $options
        ], $config));
    }
    
    /**
     * Add file upload field
     */
    public function addFile($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'file',
            'label' => $label
        ], $config));
    }
    
    /**
     * Add hidden field
     */
    public function addHidden($name, $value) {
        return $this->addField([
            'name' => $name,
            'type' => 'hidden',
            'value' => $value
        ]);
    }
    
    /**
     * Add submit button
     */
    public function addSubmit($text = 'Submit', $config = []) {
        return $this->addField(array_merge([
            'name' => 'submit',
            'type' => 'submit',
            'value' => $text,
            'class' => 'btn btn-primary'
        ], $config));
    }
    
    /**
     * Add button
     */
    public function addButton($text, $config = []) {
        return $this->addField(array_merge([
            'name' => 'button',
            'type' => 'button',
            'value' => $text,
            'class' => 'btn btn-secondary'
        ], $config));
    }
    
    /**
     * Set field value
     */
    public function setValue($name, $value) {
        $this->values[$name] = $value;
        
        // Update field value if field exists
        foreach ($this->fields as &$field) {
            if ($field['name'] === $name) {
                $field['value'] = $value;
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Set multiple values
     */
    public function setValues($values) {
        foreach ($values as $name => $value) {
            $this->setValue($name, $value);
        }
        return $this;
    }
    
    /**
     * Add error
     */
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }
    
    /**
     * Set errors
     */
    public function setErrors($errors) {
        $this->errors = $errors;
        return $this;
    }
    
    /**
     * Add validation rule
     */
    public function addRule($field, $rule, $value = null) {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        
        if ($value !== null) {
            $this->rules[$field][$rule] = $value;
        } else {
            $this->rules[$field][] = $rule;
        }
        
        return $this;
    }
    
    /**
     * Render the form
     */
    public function render() {
        ?>
        <form id="<?php echo htmlspecialchars($this->formId); ?>"
              method="<?php echo htmlspecialchars($this->config['method']); ?>"
              <?php if ($this->config['action']): ?>
                  action="<?php echo htmlspecialchars($this->config['action']); ?>"
              <?php endif; ?>
              <?php if ($this->config['enctype'] !== 'application/x-www-form-urlencoded'): ?>
                  enctype="<?php echo htmlspecialchars($this->config['enctype']); ?>"
              <?php endif; ?>
              class="<?php echo htmlspecialchars($this->config['class']); ?>"
              <?php echo $this->config['novalidate'] ? 'novalidate' : ''; ?>
              autocomplete="<?php echo htmlspecialchars($this->config['autocomplete']); ?>">
            
            <?php $this->renderCSRFField(); ?>
            <?php $this->renderFields(); ?>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateFormJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render CSRF field
     */
    private function renderCSRFField() {
        if (!$this->config['csrf']['enabled']) {
            return;
        }
        
        ?>
        <input type="hidden" 
               name="<?php echo htmlspecialchars($this->config['csrf']['field']); ?>" 
               value="<?php echo htmlspecialchars($this->config['csrf']['value']); ?>">
        <?php
    }
    
    /**
     * Render all fields
     */
    private function renderFields() {
        foreach ($this->fields as $field) {
            $this->renderField($field);
        }
    }
    
    /**
     * Render individual field
     */
    private function renderField($field) {
        // Skip hidden fields wrapper
        if ($field['type'] === 'hidden') {
            $this->renderInput($field);
            return;
        }
        
        $wrapperClass = $field['wrapper'] ?: $this->config['layout']['fieldWrapper'];
        $hasError = isset($this->errors[$field['name']]);
        
        ?>
        <div class="<?php echo htmlspecialchars($wrapperClass); ?><?php echo $hasError ? ' has-error' : ''; ?>">
            <?php $this->renderLabel($field); ?>
            <?php $this->renderInput($field); ?>
            <?php $this->renderHelp($field); ?>
            <?php $this->renderFieldErrors($field); ?>
        </div>
        <?php
    }
    
    /**
     * Render field label
     */
    private function renderLabel($field) {
        if ($field['type'] === 'hidden' || $field['type'] === 'submit' || $field['type'] === 'button') {
            return;
        }
        
        if (empty($field['label'])) {
            return;
        }
        
        $required = $field['required'] ? ' *' : '';
        
        ?>
        <label for="<?php echo htmlspecialchars($field['id']); ?>" 
               class="<?php echo htmlspecialchars($field['labelClass']); ?>">
            <?php echo htmlspecialchars($field['label']); ?><?php echo $required; ?>
        </label>
        <?php
    }
    
    /**
     * Render field input
     */
    private function renderInput($field) {
        switch ($field['type']) {
            case 'textarea':
                $this->renderTextarea($field);
                break;
            case 'select':
                $this->renderSelect($field);
                break;
            case 'radio':
                $this->renderRadioGroup($field);
                break;
            case 'checkbox':
                if (!empty($field['options'])) {
                    $this->renderCheckboxGroup($field);
                } else {
                    $this->renderCheckbox($field);
                }
                break;
            default:
                $this->renderStandardInput($field);
                break;
        }
    }
    
    /**
     * Render standard input field
     */
    private function renderStandardInput($field) {
        $attributes = $this->buildInputAttributes($field);
        
        ?>
        <input <?php echo $attributes; ?>>
        <?php
    }
    
    /**
     * Render textarea field
     */
    private function renderTextarea($field) {
        $attributes = $this->buildTextareaAttributes($field);
        $value = $this->getFieldValue($field);
        
        ?>
        <textarea <?php echo $attributes; ?>><?php echo htmlspecialchars($value); ?></textarea>
        <?php
    }
    
    /**
     * Render select field
     */
    private function renderSelect($field) {
        $attributes = $this->buildSelectAttributes($field);
        $value = $this->getFieldValue($field);
        
        ?>
        <select <?php echo $attributes; ?>>
            <?php if (isset($field['placeholder']) && $field['placeholder']): ?>
                <option value=""><?php echo htmlspecialchars($field['placeholder']); ?></option>
            <?php endif; ?>
            
            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                <?php $selected = ($value == $optionValue) ? 'selected' : ''; ?>
                <option value="<?php echo htmlspecialchars($optionValue); ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($optionLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render radio group
     */
    private function renderRadioGroup($field) {
        $value = $this->getFieldValue($field);
        
        ?>
        <div class="radio-group">
            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                <?php 
                $radioId = $field['id'] . '_' . $optionValue;
                $checked = ($value == $optionValue) ? 'checked' : '';
                ?>
                <div class="radio-item">
                    <input type="radio" 
                           id="<?php echo htmlspecialchars($radioId); ?>"
                           name="<?php echo htmlspecialchars($field['name']); ?>"
                           value="<?php echo htmlspecialchars($optionValue); ?>"
                           class="<?php echo htmlspecialchars($field['class']); ?>"
                           <?php echo $checked; ?>
                           <?php echo $field['required'] ? 'required' : ''; ?>
                           <?php echo $field['disabled'] ? 'disabled' : ''; ?>>
                    <label for="<?php echo htmlspecialchars($radioId); ?>">
                        <?php echo htmlspecialchars($optionLabel); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render checkbox group
     */
    private function renderCheckboxGroup($field) {
        $values = $this->getFieldValue($field);
        if (!is_array($values)) {
            $values = $values ? [$values] : [];
        }
        
        ?>
        <div class="checkbox-group">
            <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                <?php 
                $checkboxId = $field['id'] . '_' . $optionValue;
                $checked = in_array($optionValue, $values) ? 'checked' : '';
                ?>
                <div class="checkbox-item">
                    <input type="checkbox" 
                           id="<?php echo htmlspecialchars($checkboxId); ?>"
                           name="<?php echo htmlspecialchars($field['name']); ?>[]"
                           value="<?php echo htmlspecialchars($optionValue); ?>"
                           class="<?php echo htmlspecialchars($field['class']); ?>"
                           <?php echo $checked; ?>
                           <?php echo $field['disabled'] ? 'disabled' : ''; ?>>
                    <label for="<?php echo htmlspecialchars($checkboxId); ?>">
                        <?php echo htmlspecialchars($optionLabel); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render single checkbox
     */
    private function renderCheckbox($field) {
        $value = $this->getFieldValue($field);
        $checked = $value ? 'checked' : '';
        
        ?>
        <div class="checkbox-item">
            <input type="checkbox" 
                   id="<?php echo htmlspecialchars($field['id']); ?>"
                   name="<?php echo htmlspecialchars($field['name']); ?>"
                   value="<?php echo htmlspecialchars($field['value']); ?>"
                   class="<?php echo htmlspecialchars($field['class']); ?>"
                   <?php echo $checked; ?>
                   <?php echo $field['required'] ? 'required' : ''; ?>
                   <?php echo $field['disabled'] ? 'disabled' : ''; ?>
                   <?php echo $field['readonly'] ? 'readonly' : ''; ?>>
            <?php if ($field['label']): ?>
                <label for="<?php echo htmlspecialchars($field['id']); ?>">
                    <?php echo htmlspecialchars($field['label']); ?>
                </label>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render field help text
     */
    private function renderHelp($field) {
        if (empty($field['help'])) {
            return;
        }
        
        ?>
        <small class="form-help"><?php echo htmlspecialchars($field['help']); ?></small>
        <?php
    }
    
    /**
     * Render field errors
     */
    private function renderFieldErrors($field) {
        if (empty($this->errors[$field['name']])) {
            return;
        }
        
        ?>
        <div class="<?php echo htmlspecialchars($field['errorClass']); ?>">
            <?php foreach ($this->errors[$field['name']] as $error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Build input attributes
     */
    private function buildInputAttributes($field) {
        $attributes = [
            'type' => $field['type'],
            'id' => $field['id'],
            'name' => $field['name'],
            'class' => $field['class']
        ];
        
        // Add value for non-file inputs
        if ($field['type'] !== 'file') {
            $attributes['value'] = $this->getFieldValue($field);
        }
        
        // Add standard attributes
        if ($field['placeholder']) $attributes['placeholder'] = $field['placeholder'];
        if ($field['required']) $attributes['required'] = 'required';
        if ($field['disabled']) $attributes['disabled'] = 'disabled';
        if ($field['readonly']) $attributes['readonly'] = 'readonly';
        if ($field['pattern']) $attributes['pattern'] = $field['pattern'];
        if ($field['min'] !== null) $attributes['min'] = $field['min'];
        if ($field['max'] !== null) $attributes['max'] = $field['max'];
        if ($field['step'] !== null) $attributes['step'] = $field['step'];
        if ($field['minlength'] !== null) $attributes['minlength'] = $field['minlength'];
        if ($field['maxlength'] !== null) $attributes['maxlength'] = $field['maxlength'];
        if ($field['accept']) $attributes['accept'] = $field['accept'];
        if ($field['multiple']) $attributes['multiple'] = 'multiple';
        
        // Add custom attributes
        foreach ($field['attributes'] as $attr => $value) {
            $attributes[$attr] = $value;
        }
        
        return $this->attributesToString($attributes);
    }
    
    /**
     * Build textarea attributes
     */
    private function buildTextareaAttributes($field) {
        $attributes = [
            'id' => $field['id'],
            'name' => $field['name'],
            'class' => $field['class']
        ];
        
        if ($field['placeholder']) $attributes['placeholder'] = $field['placeholder'];
        if ($field['required']) $attributes['required'] = 'required';
        if ($field['disabled']) $attributes['disabled'] = 'disabled';
        if ($field['readonly']) $attributes['readonly'] = 'readonly';
        if ($field['rows']) $attributes['rows'] = $field['rows'];
        if ($field['cols']) $attributes['cols'] = $field['cols'];
        if ($field['minlength'] !== null) $attributes['minlength'] = $field['minlength'];
        if ($field['maxlength'] !== null) $attributes['maxlength'] = $field['maxlength'];
        
        // Add custom attributes
        foreach ($field['attributes'] as $attr => $value) {
            $attributes[$attr] = $value;
        }
        
        return $this->attributesToString($attributes);
    }
    
    /**
     * Build select attributes
     */
    private function buildSelectAttributes($field) {
        $attributes = [
            'id' => $field['id'],
            'name' => $field['name'],
            'class' => $field['class']
        ];
        
        if ($field['required']) $attributes['required'] = 'required';
        if ($field['disabled']) $attributes['disabled'] = 'disabled';
        if ($field['multiple']) $attributes['multiple'] = 'multiple';
        
        // Add custom attributes
        foreach ($field['attributes'] as $attr => $value) {
            $attributes[$attr] = $value;
        }
        
        return $this->attributesToString($attributes);
    }
    
    /**
     * Convert attributes array to string
     */
    private function attributesToString($attributes) {
        $string = '';
        foreach ($attributes as $attr => $value) {
            if ($value === true || $value === $attr) {
                $string .= ' ' . htmlspecialchars($attr);
            } else {
                $string .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
        }
        return $string;
    }
    
    /**
     * Get field value
     */
    private function getFieldValue($field) {
        // Check for explicitly set values first
        if (isset($this->values[$field['name']])) {
            return $this->values[$field['name']];
        }
        
        // Return default field value
        return $field['value'];
    }
    
    /**
     * Generate CSRF token
     */
    private function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Generate form JavaScript
     */
    private function generateFormJs() {
        $formId = $this->formId;
        $config = json_encode($this->config);
        $rules = json_encode($this->rules);
        $messages = json_encode($this->config['messages']);
        
        return "
        // Initialize Form: {$formId}
        (function() {
            const form = document.getElementById('{$formId}');
            const config = {$config};
            const rules = {$rules};
            const messages = {$messages};
            
            if (!form) return;
            
            let isSubmitting = false;
            
            // Form validation
            function validateField(field) {
                if (!config.validation.clientSide) return true;
                
                const fieldName = field.name;
                const fieldRules = rules[fieldName] || [];
                const value = getFieldValue(field);
                const errors = [];
                
                // Check each validation rule
                fieldRules.forEach(rule => {
                    if (typeof rule === 'string') {
                        switch (rule) {
                            case 'required':
                                if (!value.trim()) {
                                    errors.push(messages.required);
                                }
                                break;
                            case 'email':
                                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                                    errors.push(messages.email);
                                }
                                break;
                            case 'url':
                                if (value && !/^https?:\/\/.+/.test(value)) {
                                    errors.push(messages.url);
                                }
                                break;
                            case 'number':
                                if (value && isNaN(value)) {
                                    errors.push(messages.number);
                                }
                                break;
                        }
                    } else if (typeof rule === 'object') {
                        Object.keys(rule).forEach(ruleName => {
                            const ruleValue = rule[ruleName];
                            
                            switch (ruleName) {
                                case 'min':
                                    if (value && parseFloat(value) < ruleValue) {
                                        errors.push(messages.min.replace('{min}', ruleValue));
                                    }
                                    break;
                                case 'max':
                                    if (value && parseFloat(value) > ruleValue) {
                                        errors.push(messages.max.replace('{max}', ruleValue));
                                    }
                                    break;
                                case 'minlength':
                                    if (value && value.length < ruleValue) {
                                        errors.push(messages.minlength.replace('{min}', ruleValue));
                                    }
                                    break;
                                case 'maxlength':
                                    if (value && value.length > ruleValue) {
                                        errors.push(messages.maxlength.replace('{max}', ruleValue));
                                    }
                                    break;
                                case 'pattern':
                                    if (value && !new RegExp(ruleValue).test(value)) {
                                        errors.push(messages.pattern);
                                    }
                                    break;
                            }
                        });
                    }
                });
                
                // Check HTML5 validation
                if (field.validity && !field.validity.valid) {
                    if (field.validity.valueMissing) {
                        errors.push(messages.required);
                    } else if (field.validity.typeMismatch) {
                        if (field.type === 'email') {
                            errors.push(messages.email);
                        } else if (field.type === 'url') {
                            errors.push(messages.url);
                        }
                    } else if (field.validity.patternMismatch) {
                        errors.push(messages.pattern);
                    }
                }
                
                // Display errors
                displayFieldErrors(field, errors);
                
                return errors.length === 0;
            }
            
            function validateForm() {
                if (!config.validation.clientSide) return true;
                
                let isValid = true;
                const fields = form.querySelectorAll('input, textarea, select');
                
                fields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            function getFieldValue(field) {
                if (field.type === 'checkbox') {
                    return field.checked ? field.value : '';
                } else if (field.type === 'radio') {
                    const checked = form.querySelector(`input[name=\"${field.name}\"]:checked`);
                    return checked ? checked.value : '';
                } else {
                    return field.value;
                }
            }
            
            function displayFieldErrors(field, errors) {
                if (!config.validation.showErrors) return;
                
                const fieldGroup = field.closest('.form-group');
                if (!fieldGroup) return;
                
                // Remove existing errors
                const existingErrors = fieldGroup.querySelectorAll('.form-error');
                existingErrors.forEach(el => el.remove());
                
                // Remove error classes
                fieldGroup.classList.remove('has-error');
                field.classList.remove(config.validation.errorClass);
                
                if (errors.length > 0) {
                    // Add error classes
                    fieldGroup.classList.add('has-error');
                    field.classList.add(config.validation.errorClass);
                    
                    // Add error messages
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'form-error';
                    
                    errors.forEach(error => {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = error;
                        errorDiv.appendChild(errorMsg);
                    });
                    
                    field.parentNode.appendChild(errorDiv);
                } else {
                    // Add success class if validation passed
                    field.classList.add(config.validation.successClass);
                }
            }
            
            // Real-time validation
            if (config.validation.realTime) {
                const fields = form.querySelectorAll('input, textarea, select');
                fields.forEach(field => {
                    field.addEventListener('blur', () => validateField(field));
                    field.addEventListener('input', debounce(() => validateField(field), 500));
                });
            }
            
            // Form submission
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }
                
                // Validate form
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }
                
                // Handle AJAX submission
                if (config.submission.ajax) {
                    e.preventDefault();
                    submitFormAjax();
                } else {
                    // Allow normal form submission
                    isSubmitting = true;
                    setSubmitButtonLoading(true);
                }
            });
            
            function submitFormAjax() {
                if (isSubmitting) return;
                
                isSubmitting = true;
                setSubmitButtonLoading(true);
                
                // Call beforeSubmit callback
                if (config.submission.beforeSubmit) {
                    const result = config.submission.beforeSubmit(form);
                    if (result === false) {
                        isSubmitting = false;
                        setSubmitButtonLoading(false);
                        return;
                    }
                }
                
                const formData = new FormData(form);
                const url = config.submission.url || form.action || window.location.href;
                
                fetch(url, {
                    method: form.method || 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    isSubmitting = false;
                    setSubmitButtonLoading(false);
                    
                    if (data.success) {
                        // Success callback
                        if (config.submission.onSuccess) {
                            config.submission.onSuccess(data, form);
                        }
                        
                        // Reset form if configured
                        if (config.submission.resetOnSuccess) {
                            form.reset();
                            clearValidation();
                        }
                        
                        // Emit success event
                        const successEvent = new CustomEvent('formSuccess', {
                            detail: { formId: '{$formId}', data: data }
                        });
                        document.dispatchEvent(successEvent);
                        
                    } else {
                        // Handle validation errors
                        if (data.errors) {
                            displayFormErrors(data.errors);
                        }
                        
                        // Error callback
                        if (config.submission.onError) {
                            config.submission.onError(data, form);
                        }
                        
                        // Emit error event
                        const errorEvent = new CustomEvent('formError', {
                            detail: { formId: '{$formId}', data: data }
                        });
                        document.dispatchEvent(errorEvent);
                    }
                })
                .catch(error => {
                    isSubmitting = false;
                    setSubmitButtonLoading(false);
                    
                    console.error('Form submission error:', error);
                    
                    // Error callback
                    if (config.submission.onError) {
                        config.submission.onError({ error: error.message }, form);
                    }
                    
                    // Emit error event
                    const errorEvent = new CustomEvent('formError', {
                        detail: { formId: '{$formId}', error: error.message }
                    });
                    document.dispatchEvent(errorEvent);
                });
            }
            
            function displayFormErrors(errors) {
                // Clear existing errors first
                clearValidation();
                
                Object.keys(errors).forEach(fieldName => {
                    const field = form.querySelector(`[name=\"${fieldName}\"]`);
                    if (field) {
                        displayFieldErrors(field, Array.isArray(errors[fieldName]) ? errors[fieldName] : [errors[fieldName]]);
                    }
                });
            }
            
            function clearValidation() {
                const fieldGroups = form.querySelectorAll('.form-group');
                fieldGroups.forEach(group => {
                    group.classList.remove('has-error');
                    const errorDivs = group.querySelectorAll('.form-error');
                    errorDivs.forEach(div => div.remove());
                });
                
                const fields = form.querySelectorAll('input, textarea, select');
                fields.forEach(field => {
                    field.classList.remove(config.validation.errorClass, config.validation.successClass);
                });
            }
            
            function setSubmitButtonLoading(loading) {
                const submitBtn = form.querySelector('[type=\"submit\"]');
                if (!submitBtn) return;
                
                if (loading) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.originalText = submitBtn.textContent;
                    submitBtn.textContent = config.submission.loadingText;
                } else {
                    submitBtn.disabled = false;
                    if (submitBtn.dataset.originalText) {
                        submitBtn.textContent = submitBtn.dataset.originalText;
                        delete submitBtn.dataset.originalText;
                    }
                }
            }
            
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
            
            // Expose form API
            window['{$formId}'] = {
                validate: validateForm,
                submit: submitFormAjax,
                reset: function() {
                    form.reset();
                    clearValidation();
                },
                setValues: function(values) {
                    Object.keys(values).forEach(name => {
                        const field = form.querySelector(`[name=\"${name}\"]`);
                        if (field) {
                            if (field.type === 'checkbox') {
                                field.checked = !!values[name];
                            } else {
                                field.value = values[name];
                            }
                        }
                    });
                },
                getValues: function() {
                    const formData = new FormData(form);
                    const values = {};
                    for (const [key, value] of formData.entries()) {
                        values[key] = value;
                    }
                    return values;
                }
            };
            
        })();
        ";
    }
    
    /**
     * Create a form with configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Create a login form
     */
    public static function createLoginForm($config = []) {
        $form = new self(array_merge([
            'formId' => 'loginForm',
            'class' => 'auth-form',
            'validation' => ['clientSide' => true, 'realTime' => true]
        ], $config));
        
        $form->addEmail('email', 'Email Address', ['required' => true, 'placeholder' => 'Enter your email...'])
             ->addPassword('password', 'Password', ['required' => true, 'placeholder' => 'Enter your password...'])
             ->addCheckbox('remember_me', 'Remember me')
             ->addSubmit('Sign In', ['class' => 'btn btn-primary btn-block']);
        
        return $form;
    }
    
    /**
     * Create a registration form
     */
    public static function createRegistrationForm($config = []) {
        $form = new self(array_merge([
            'formId' => 'registerForm',
            'class' => 'auth-form',
            'validation' => ['clientSide' => true, 'realTime' => true]
        ], $config));
        
        $form->addText('name', 'Full Name', ['required' => true, 'placeholder' => 'Enter your full name...'])
             ->addEmail('email', 'Email Address', ['required' => true, 'placeholder' => 'Enter your email...'])
             ->addPassword('password', 'Password', ['required' => true, 'placeholder' => 'Create a password...', 'minlength' => 6])
             ->addPassword('password_confirmation', 'Confirm Password', ['required' => true, 'placeholder' => 'Confirm your password...'])
             ->addSubmit('Create Account', ['class' => 'btn btn-primary btn-block']);
        
        return $form;
    }
}