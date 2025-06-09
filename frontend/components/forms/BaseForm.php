<?php
/**
 * BaseForm Component - Fixed version
 * Location: frontend/components/forms/BaseForm.php
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
            'value' => null
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
    
    public function __construct($config = []) {
        $this->config = $this->mergeConfig($this->defaultConfig, $config);
        $this->formId = $this->config['formId'];
        $this->fields = [];
        $this->errors = [];
        $this->values = [];
        $this->rules = [];
        
        if ($this->config['csrf']['enabled'] && !$this->config['csrf']['value']) {
            $this->config['csrf']['value'] = $this->generateCSRFToken();
        }
    }
    
    /**
     * Safe config merging
     */
    private function mergeConfig($array1, $array2) {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeConfig($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
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
            'options' => [],
            'multiple' => false,
            'rows' => 3,
            'cols' => null,
            'accept' => null,
            'pattern' => null,
            'min' => null,
            'max' => null,
            'step' => null,
            'minlength' => null,
            'maxlength' => null
        ];
        
        $field = array_merge($defaultField, $config);
        
        if (!$field['id']) {
            $field['id'] = $this->formId . '_' . $field['name'];
        }
        
        if (!empty($field['validation'])) {
            $this->rules[$field['name']] = $field['validation'];
        }
        
        $this->fields[] = $field;
        return $this;
    }
    
    public function addText($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'text',
            'label' => $label
        ], $config));
    }
    
    public function addEmail($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'email',
            'label' => $label,
            'validation' => ['email']
        ], $config));
    }
    
    public function addPassword($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'password',
            'label' => $label,
            'autocomplete' => 'current-password'
        ], $config));
    }
    
    public function addCheckbox($name, $label, $config = []) {
        return $this->addField(array_merge([
            'name' => $name,
            'type' => 'checkbox',
            'label' => $label,
            'value' => '1'
        ], $config));
    }
    
    public function addHidden($name, $value) {
        return $this->addField([
            'name' => $name,
            'type' => 'hidden',
            'value' => $value
        ]);
    }
    
    public function addSubmit($text = 'Submit', $config = []) {
        return $this->addField(array_merge([
            'name' => 'submit',
            'type' => 'submit',
            'value' => $text,
            'class' => 'btn btn-primary'
        ], $config));
    }
    
    public function setValue($name, $value) {
        $this->values[$name] = $value;
        
        foreach ($this->fields as &$field) {
            if ($field['name'] === $name) {
                $field['value'] = $value;
                break;
            }
        }
        
        return $this;
    }
    
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }
    
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
    
    protected function renderCSRFField() {
        if (!$this->config['csrf']['enabled']) {
            return;
        }
        
        ?>
        <input type="hidden" 
               name="<?php echo htmlspecialchars($this->config['csrf']['field']); ?>" 
               value="<?php echo htmlspecialchars($this->config['csrf']['value']); ?>">
        <?php
    }
    
    protected function renderFields() {
        foreach ($this->fields as $field) {
            $this->renderField($field);
        }
    }
    
    protected function renderField($field) {
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
    
    private function renderInput($field) {
        switch ($field['type']) {
            case 'checkbox':
                $this->renderCheckbox($field);
                break;
            default:
                $this->renderStandardInput($field);
                break;
        }
    }
    
    private function renderStandardInput($field) {
        $attributes = $this->buildInputAttributes($field);
        
        ?>
        <input <?php echo $attributes; ?>>
        <?php
    }
    
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
    
    private function renderHelp($field) {
        if (empty($field['help'])) {
            return;
        }
        
        ?>
        <small class="form-help"><?php echo htmlspecialchars($field['help']); ?></small>
        <?php
    }
    
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
    
    private function buildInputAttributes($field) {
        $attributes = [
            'type' => $field['type'],
            'id' => $field['id'],
            'name' => $field['name'],
            'class' => $field['class']
        ];
        
        if ($field['type'] !== 'file') {
            $attributes['value'] = $this->getFieldValue($field);
        }
        
        if ($field['placeholder']) $attributes['placeholder'] = $field['placeholder'];
        if ($field['required']) $attributes['required'] = 'required';
        if ($field['disabled']) $attributes['disabled'] = 'disabled';
        if ($field['readonly']) $attributes['readonly'] = 'readonly';
        if ($field['minlength'] !== null) $attributes['minlength'] = $field['minlength'];
        if ($field['maxlength'] !== null) $attributes['maxlength'] = $field['maxlength'];
        
        foreach ($field['attributes'] as $attr => $value) {
            $attributes[$attr] = $value;
        }
        
        return $this->attributesToString($attributes);
    }
    
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
    
    private function getFieldValue($field) {
        if (isset($this->values[$field['name']])) {
            return $this->values[$field['name']];
        }
        
        return $field['value'];
    }
    
    private function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    private function generateFormJs() {
        $formId = $this->formId;
        $config = json_encode($this->config);
        
        return "
        // Initialize Form: {$formId}
        (function() {
            const form = document.getElementById('{$formId}');
            const config = {$config};
            
            if (!form) return;
            
            // Form submission
            form.addEventListener('submit', function(e) {
                if (config.submission.ajax) {
                    e.preventDefault();
                    // AJAX form submission would go here
                    console.log('AJAX form submission not implemented yet');
                }
            });
            
        })();
        ";
    }
}