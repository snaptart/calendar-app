<?php
/**
 * LoginForm Component - Fixed version
 * Location: frontend/components/forms/LoginForm.php
 */

require_once __DIR__ . '/BaseForm.php';

class LoginForm extends BaseForm {
    
    public function __construct($config = []) {
        $loginConfig = [
            'formId' => 'loginForm',
            'method' => 'POST',
            'action' => '../../backend/api.php',
            'class' => 'auth-form active',
            'validation' => [
                'clientSide' => true,
                'realTime' => false,
                'showErrors' => true
            ],
            'submission' => [
                'ajax' => true,
                'url' => '../../backend/api.php',
                'loadingText' => 'Signing in...',
                'resetOnSuccess' => false
            ],
            'csrf' => [
                'enabled' => false
            ]
        ];
        
        $mergedConfig = $this->mergeArrays($loginConfig, $config);
        
        parent::__construct($mergedConfig);
        
        $this->setupLoginFields();
        $this->addLoginFormJs();
    }
    
    /**
     * Safe array merging
     */
    private function mergeArrays($array1, $array2) {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeArrays($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    private function setupLoginFields() {
        $this->addHidden('action', 'login');
        
        // Use specific IDs that match JavaScript expectations
        $this->addField([
            'name' => 'email',
            'type' => 'email',
            'id' => 'loginEmail', // Specific ID for JavaScript
            'label' => 'Email Address',
            'required' => true,
            'placeholder' => 'Enter your email...',
            'autocomplete' => 'email'
        ]);
        
        $this->addField([
            'name' => 'password',
            'type' => 'password',
            'id' => 'loginPassword', // Specific ID for JavaScript
            'label' => 'Password',
            'required' => true,
            'placeholder' => 'Enter your password...',
            'autocomplete' => 'current-password'
        ]);
        
        $this->addField([
            'name' => 'rememberMe',
            'type' => 'checkbox',
            'id' => 'rememberMe', // Specific ID for JavaScript
            'label' => 'Remember me for 30 days',
            'value' => '1'
        ]);
        
        $this->addSubmit('Sign In', [
            'class' => 'auth-button'
        ]);
    }
    
    private function addLoginFormJs() {
        // Simple JavaScript for now - will be enhanced later
        $this->addInlineJS('
            console.log("Login form initialized");
        ');
    }
    
    /**
     * Add inline JavaScript (placeholder method)
     */
    private function addInlineJS($js) {
        // This would add to the form's inline JS
        // For now, just store it
    }
    
    public function renderWithTabs() {
        ?>
        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">Sign In</button>
            <button class="auth-tab" data-tab="register">Sign Up</button>
        </div>
        
        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>
        
        <?php
        $this->render();
        
        // Create registration form
        $registerForm = self::createRegistrationForm();
        $registerForm->render();
        ?>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tab switching functionality
            const tabs = document.querySelectorAll(".auth-tab");
            const forms = document.querySelectorAll(".auth-form");
            
            tabs.forEach(function(tab) {
                tab.addEventListener("click", function() {
                    const targetTab = tab.dataset.tab;
                    
                    // Update tab states
                    tabs.forEach(function(t) {
                        t.classList.remove("active");
                    });
                    tab.classList.add("active");
                    
                    // Update form states
                    forms.forEach(function(form) {
                        form.classList.remove("active");
                        if (form.id === targetTab + "Form") {
                            form.classList.add("active");
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }
    
    public static function createRegistrationForm($config = []) {
        $regConfig = [
            'formId' => 'registerForm',
            'class' => 'auth-form'
        ];
        
        $form = new self($regConfig);
        
        // Clear fields and add registration fields
        $form->fields = [];
        $form->setupRegistrationFields();
        
        return $form;
    }
    
    private function setupRegistrationFields() {
        $this->addHidden('action', 'register');
        
        // Use specific IDs that match JavaScript expectations
        $this->addField([
            'name' => 'name',
            'type' => 'text',
            'id' => 'registerName', // Specific ID for JavaScript
            'label' => 'Full Name',
            'required' => true,
            'placeholder' => 'Enter your full name...'
        ]);
        
        $this->addField([
            'name' => 'email',
            'type' => 'email',
            'id' => 'registerEmail', // Specific ID for JavaScript
            'label' => 'Email Address',
            'required' => true,
            'placeholder' => 'Enter your email...'
        ]);
        
        $this->addField([
            'name' => 'password',
            'type' => 'password',
            'id' => 'registerPassword', // Specific ID for JavaScript
            'label' => 'Password',
            'required' => true,
            'placeholder' => 'Create a password...',
            'minlength' => 6
        ]);
        
        $this->addField([
            'name' => 'confirmPassword',
            'type' => 'password',
            'id' => 'confirmPassword', // Specific ID for JavaScript
            'label' => 'Confirm Password',
            'required' => true,
            'placeholder' => 'Confirm your password...',
            'minlength' => 6
        ]);
        
        $this->addSubmit('Create Account', [
            'class' => 'auth-button'
        ]);
    }
    
    public static function renderLoginForm($config = []) {
        $form = new self($config);
        $form->render();
    }
    
    public static function renderLoginWithTabs($config = []) {
        $form = new self($config);
        $form->renderWithTabs();
    }
}