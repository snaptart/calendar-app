<?php
/**
 * Login Page - Converted from frontend/pages/login.html
 * Location: frontend/pages/login.php
 * 
 * Authentication page using the new service layer architecture
 */

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ApiClient.php';
require_once __DIR__ . '/../layouts/AuthLayout.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = new AuthService();

// Check if already authenticated
if ($auth->isAuthenticated()) {
    $auth->redirectAfterLogin('./calendar.php');
    exit;
}

// Initialize variables
$error = null;
$success = null;
$formData = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action'])) {
        
        switch ($_POST['action']) {
            case 'login':
                try {
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $rememberMe = isset($_POST['rememberMe']);
                    
                    if (empty($email) || empty($password)) {
                        throw new Exception('Email and password are required');
                    }
                    
                    $result = $auth->login($email, $password, $rememberMe);
                    
                    if ($result['success']) {
                        $auth->redirectAfterLogin('./calendar.php');
                        exit;
                    } else {
                        $error = $result['error'] ?? 'Login failed';
                    }
                    
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;
                
            case 'register':
                try {
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $confirmPassword = $_POST['confirmPassword'] ?? '';
                    
                    // Validation
                    if (empty($name) || strlen($name) < 2) {
                        throw new Exception('Please enter a valid name (at least 2 characters)');
                    }
                    
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Please enter a valid email address');
                    }
                    
                    if (empty($password) || strlen($password) < 6) {
                        throw new Exception('Password must be at least 6 characters long');
                    }
                    
                    if ($password !== $confirmPassword) {
                        throw new Exception('Passwords do not match');
                    }
                    
                    $result = $auth->register($name, $email, $password);
                    
                    if ($result['success']) {
                        $success = 'Account created successfully! You can now sign in.';
                        // Store email for pre-filling login form
                        $formData['loginEmail'] = $email;
                    } else {
                        $error = $result['error'] ?? 'Registration failed';
                    }
                    
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    // Preserve form data on error
                    $formData = [
                        'registerName' => $_POST['name'] ?? '',
                        'registerEmail' => $_POST['email'] ?? ''
                    ];
                }
                break;
        }
    }
}

// Get page configuration
$pageConfig = $config->forLayout('login', [
    'title' => 'Login - Collaborative Calendar',
    'formTitle' => '📅 Calendar',
    'formSubtitle' => 'Collaborative calendar for teams',
    'showDemoNotice' => true
]);

// Create the layout
$layout = new AuthLayout($pageConfig);
$layout->addAuthScripts();

// Add custom JavaScript for form handling
$layout->addInlineJS('
document.addEventListener("DOMContentLoaded", function() {
    // Handle form submissions
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");
    
    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            const email = document.getElementById("loginEmail").value.trim();
            const password = document.getElementById("loginPassword").value;
            
            if (!email || !password) {
                e.preventDefault();
                showAuthMessage("Please fill in all fields", "error");
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector("button[type=submit]");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Signing in...";
            }
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener("submit", function(e) {
            const name = document.getElementById("registerName").value.trim();
            const email = document.getElementById("registerEmail").value.trim();
            const password = document.getElementById("registerPassword").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                showAuthMessage("Please fill in all fields", "error");
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAuthMessage("Passwords do not match", "error");
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector("button[type=submit]");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Creating account...";
            }
        });
    }
    
    // Auto-switch to login tab and pre-fill email after successful registration
    ' . ($success ? 'setTimeout(function() {
        const loginTab = document.querySelector(".auth-tab[data-tab=\'login\']");
        if (loginTab) {
            loginTab.click();
            const loginEmail = document.getElementById("loginEmail");
            if (loginEmail && "' . ($formData['loginEmail'] ?? '') . '") {
                loginEmail.value = "' . ($formData['loginEmail'] ?? '') . '";
            }
        }
    }, 2000);' : '') . '
});
');

// Render the authentication page
$layout->renderAuthPage(function() use ($error, $success, $formData) {
    ?>
    
    <!-- Display messages -->
    <?php if ($error): ?>
        <div class="error-message" style="display: block;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message" style="display: block;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- Authentication Tabs -->
    <div class="auth-tabs">
        <button class="auth-tab active" data-tab="login">Sign In</button>
        <button class="auth-tab" data-tab="register">Sign Up</button>
    </div>
    
    <!-- Login Form -->
    <form id="loginForm" class="auth-form active" method="POST">
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
            <label for="loginEmail">Email Address</label>
            <input 
                type="email" 
                id="loginEmail" 
                name="email" 
                placeholder="Enter your email..." 
                value="<?php echo htmlspecialchars($formData['loginEmail'] ?? ''); ?>"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="loginPassword">Password</label>
            <input 
                type="password" 
                id="loginPassword" 
                name="password" 
                placeholder="Enter your password..." 
                required
            >
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" id="rememberMe" name="rememberMe">
            <label for="rememberMe">Remember me for 30 days</label>
        </div>
        
        <button type="submit" class="auth-button">Sign In</button>
    </form>
    
    <!-- Registration Form -->
    <form id="registerForm" class="auth-form" method="POST">
        <input type="hidden" name="action" value="register">
        
        <div class="form-group">
            <label for="registerName">Full Name</label>
            <input 
                type="text" 
                id="registerName" 
                name="name" 
                placeholder="Enter your full name..." 
                value="<?php echo htmlspecialchars($formData['registerName'] ?? ''); ?>"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="registerEmail">Email Address</label>
            <input 
                type="email" 
                id="registerEmail" 
                name="email" 
                placeholder="Enter your email..." 
                value="<?php echo htmlspecialchars($formData['registerEmail'] ?? ''); ?>"
                required
            >
        </div>
        
        <div class="form-group">
            <label for="registerPassword">Password</label>
            <input 
                type="password" 
                id="registerPassword" 
                name="password" 
                placeholder="Create a password..." 
                required 
                minlength="6"
            >
        </div>
        
        <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input 
                type="password" 
                id="confirmPassword" 
                name="confirmPassword" 
                placeholder="Confirm your password..." 
                required 
                minlength="6"
            >
        </div>
        
        <button type="submit" class="auth-button">Create Account</button>
    </form>
    
    <?php
});
?>