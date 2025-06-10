<?php
/**
 * Login Page - Updated with Component Architecture
 * Location: frontend/pages/login.php
 * 
 * Authentication page using the new component-based architecture
 */

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../layouts/AuthLayout.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = new AuthService();

// Check if already authenticated
if ($auth->isAuthenticated()) {
    $auth->redirectAfterLogin('./calendar.php');
    exit;
}

// Get page configuration
$pageConfig = $config->forLayout('login', [
    'title' => 'Login - Collaborative Calendar',
    'showNavigation' => false,
    'formTitle' => '📅 Calendar',
    'formSubtitle' => 'Ice time management system for arenas and skating programs',
    'showDemoNotice' => true
]);

// Create the layout using component architecture
$layout = new AuthLayout($pageConfig);

// Render the page with component support
$layout->renderAuthPage(function() use ($config) {
    ?>
    <!-- Authentication Tabs -->
    <div class="auth-tabs"
         data-component="tabs"
         data-component-id="auth-tabs"
         data-auto-init="true">
        <button class="auth-tab active" data-tab="login">Sign In</button>
        <button class="auth-tab" data-tab="register">Sign Up</button>
    </div>

    <!-- Message Containers -->
    <div id="errorMessage" 
         class="error-message" 
         style="display: none;"
         data-component="message"
         data-message-type="error"></div>
    <div id="successMessage" 
         class="success-message" 
         style="display: none;"
         data-component="message"
         data-message-type="success"></div>

    <!-- Login Form -->
    <form id="loginForm" 
          class="auth-form active"
          data-component="form"
          data-component-id="login-form"
          data-validation="true"
          data-submit-url="../../backend/api.php"
          data-submit-method="POST"
          data-permissions='<?php echo json_encode(['canSubmit' => true], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
          data-auto-init="true">
        
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
            <label for="loginEmail">Email Address *</label>
            <input type="email" 
                   id="loginEmail" 
                   name="email"
                   placeholder="Enter your email..." 
                   class="form-control"
                   data-validate="required|email"
                   data-error-target="#loginEmailError"
                   autocomplete="email"
                   required>
            <div class="form-error" id="loginEmailError"></div>
        </div>
        
        <div class="form-group">
            <label for="loginPassword">Password *</label>
            <input type="password" 
                   id="loginPassword" 
                   name="password"
                   placeholder="Enter your password..." 
                   class="form-control"
                   data-validate="required|minlength:6"
                   data-error-target="#loginPasswordError"
                   autocomplete="current-password"
                   required>
            <div class="form-error" id="loginPasswordError"></div>
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" 
                   id="rememberMe" 
                   name="rememberMe" 
                   value="1">
            <label for="rememberMe">Remember me for 30 days</label>
        </div>
        
        <button type="submit" 
                class="auth-button"
                data-component="button"
                data-action="submit"
                data-loading-text="Signing in...">
            Sign In
        </button>
    </form>

    <!-- Registration Form -->
    <form id="registerForm" 
          class="auth-form"
          data-component="form"
          data-component-id="register-form"
          data-validation="true"
          data-submit-url="../../backend/api.php"
          data-submit-method="POST"
          data-permissions='<?php echo json_encode(['canSubmit' => true], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
          data-auto-init="true">
        
        <input type="hidden" name="action" value="register">
        
        <div class="form-group">
            <label for="registerName">Full Name *</label>
            <input type="text" 
                   id="registerName" 
                   name="name"
                   placeholder="Enter your full name..." 
                   class="form-control"
                   data-validate="required|minlength:2"
                   data-error-target="#registerNameError"
                   autocomplete="name"
                   required>
            <div class="form-error" id="registerNameError"></div>
        </div>
        
        <div class="form-group">
            <label for="registerEmail">Email Address *</label>
            <input type="email" 
                   id="registerEmail" 
                   name="email"
                   placeholder="Enter your email..." 
                   class="form-control"
                   data-validate="required|email"
                   data-error-target="#registerEmailError"
                   autocomplete="email"
                   required>
            <div class="form-error" id="registerEmailError"></div>
        </div>
        
        <div class="form-group">
            <label for="registerPassword">Password *</label>
            <input type="password" 
                   id="registerPassword" 
                   name="password"
                   placeholder="Create a password..." 
                   class="form-control"
                   data-validate="required|minlength:6"
                   data-error-target="#registerPasswordError"
                   autocomplete="new-password"
                   minlength="6"
                   required>
            <div class="form-error" id="registerPasswordError"></div>
        </div>
        
        <div class="form-group">
            <label for="confirmPassword">Confirm Password *</label>
            <input type="password" 
                   id="confirmPassword" 
                   name="confirmPassword"
                   placeholder="Confirm your password..." 
                   class="form-control"
                   data-validate="required|minlength:6|match:registerPassword"
                   data-error-target="#confirmPasswordError"
                   autocomplete="new-password"
                   minlength="6"
                   required>
            <div class="form-error" id="confirmPasswordError"></div>
        </div>
        
        <button type="submit" 
                class="auth-button"
                data-component="button"
                data-action="submit"
                data-loading-text="Creating account...">
            Create Account
        </button>
    </form>

    <!-- Additional Scripts for Enhanced Tab Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced tab switching with component integration
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const targetTab = tab.dataset.tab;
                
                // Update tab states
                tabs.forEach(function(t) {
                    t.classList.remove('active');
                });
                tab.classList.add('active');
                
                // Update form states
                forms.forEach(function(form) {
                    form.classList.remove('active');
                    if (form.id === targetTab + 'Form') {
                        form.classList.add('active');
                    }
                });
                
                // Clear any messages
                const errorMsg = document.getElementById('errorMessage');
                const successMsg = document.getElementById('successMessage');
                if (errorMsg) errorMsg.style.display = 'none';
                if (successMsg) successMsg.style.display = 'none';
                
                // Focus on first input of active form
                setTimeout(function() {
                    const activeForm = document.querySelector('.auth-form.active');
                    if (activeForm) {
                        const firstInput = activeForm.querySelector('input[type="text"], input[type="email"]');
                        if (firstInput) firstInput.focus();
                    }
                }, 100);
            });
        });
        
        // Auto-focus on email field when page loads
        const loginEmail = document.getElementById('loginEmail');
        if (loginEmail) {
            loginEmail.focus();
        }
        
        // Handle URL parameters for messages
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        
        if (message === 'logged_out') {
            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                successMsg.textContent = 'You have been successfully logged out.';
                successMsg.style.display = 'block';
                setTimeout(() => successMsg.style.display = 'none', 5000);
            }
        }
    });
    </script>
    <?php
});

// Add configuration and authentication scripts
?>
<script>
// Configuration for the component system
<?php echo $config->generateConfigJs(); ?>

// Enhanced authentication handling
window.AuthPage = {
    redirectUrl: './calendar.php',
    showMessages: function(message, type) {
        const errorEl = document.getElementById('errorMessage');
        const successEl = document.getElementById('successMessage');
        
        // Hide all messages first
        if (errorEl) errorEl.style.display = 'none';
        if (successEl) successEl.style.display = 'none';
        
        // Show appropriate message
        if (type === 'error' && errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        } else if (type === 'success' && successEl) {
            successEl.textContent = message;
            successEl.style.display = 'block';
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorEl) errorEl.style.display = 'none';
            if (successEl) successEl.style.display = 'none';
        }, 5000);
    }
};

console.log('Login page loaded with component architecture');
</script>