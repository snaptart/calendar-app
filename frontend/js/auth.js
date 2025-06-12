/**
 * Authentication Page - Refactored to use modular components
 * Location: frontend/js/auth.js
 * 
 * This file has been refactored to use shared utilities where possible
 * while maintaining its page-specific authentication functionality.
 */

// Import shared utilities
import { Config } from './core/config.js';
import { Utils } from './core/utils.js';

// =============================================================================
// AUTHENTICATION UTILITIES
// =============================================================================

const AuthUtils = {
    /**
     * Make authenticated API request using shared Config
     */
    async makeRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include', // Include cookies for session
                ...options
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Auth API Request failed:', error);
            throw error;
        }
    },
    
    /**
     * Validate email format
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    /**
     * Validate password strength
     */
    validatePassword(password) {
        return password && password.length >= 6;
    },
    
    /**
     * Show form validation errors
     */
    showFormError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = field?.parentNode.querySelector('.error-message');
        
        if (field) {
            field.classList.add('error');
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    },
    
    /**
     * Clear form validation errors
     */
    clearFormErrors() {
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
    },
    
    /**
     * Show loading state
     */
    showLoading(buttonId, loadingText = 'Loading...') {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            button.setAttribute('data-original-text', button.textContent);
            button.textContent = loadingText;
        }
    },
    
    /**
     * Hide loading state
     */
    hideLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.textContent = originalText;
                button.removeAttribute('data-original-text');
            }
        }
    }
};

// =============================================================================
// AUTHENTICATION MANAGER
// =============================================================================

const AuthManager = {
    /**
     * Handle user login
     */
    async login(email, password, rememberMe = false) {
        try {
            AuthUtils.clearFormErrors();
            AuthUtils.showLoading('loginBtn', 'Signing in...');
            
            // Validate inputs
            if (!email || !AuthUtils.validateEmail(email)) {
                AuthUtils.showFormError('loginEmail', 'Please enter a valid email address');
                return false;
            }
            
            if (!password || !AuthUtils.validatePassword(password)) {
                AuthUtils.showFormError('loginPassword', 'Password must be at least 6 characters');
                return false;
            }
            
            // Make login request using shared Config
            const response = await AuthUtils.makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'login',
                    email,
                    password,
                    remember_me: rememberMe
                })
            });
            
            if (response.success) {
                // Successful login - redirect to main application
                window.location.href = response.redirect || 'index.php';
                return true;
            } else {
                throw new Error(response.error || 'Login failed');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            this.showAuthError(error.message);
            return false;
        } finally {
            AuthUtils.hideLoading('loginBtn');
        }
    },
    
    /**
     * Handle user registration
     */
    async register(name, email, password, confirmPassword) {
        try {
            AuthUtils.clearFormErrors();
            AuthUtils.showLoading('registerBtn', 'Creating account...');
            
            // Validate inputs
            if (!name || name.trim().length < 2) {
                AuthUtils.showFormError('registerName', 'Name must be at least 2 characters');
                return false;
            }
            
            if (!email || !AuthUtils.validateEmail(email)) {
                AuthUtils.showFormError('registerEmail', 'Please enter a valid email address');
                return false;
            }
            
            if (!password || !AuthUtils.validatePassword(password)) {
                AuthUtils.showFormError('registerPassword', 'Password must be at least 6 characters');
                return false;
            }
            
            if (password !== confirmPassword) {
                AuthUtils.showFormError('confirmPassword', 'Passwords do not match');
                return false;
            }
            
            // Make registration request using shared Config
            const response = await AuthUtils.makeRequest(Config.apiEndpoints.api, {
                method: 'POST',
                body: JSON.stringify({
                    action: 'register',
                    name: name.trim(),
                    email,
                    password
                })
            });
            
            if (response.success) {
                this.showAuthSuccess('Registration successful! Please log in.');
                this.switchToLogin();
                return true;
            } else {
                throw new Error(response.error || 'Registration failed');
            }
            
        } catch (error) {
            console.error('Registration error:', error);
            this.showAuthError(error.message);
            return false;
        } finally {
            AuthUtils.hideLoading('registerBtn');
        }
    },
    
    /**
     * Switch between login and registration forms
     */
    switchToLogin() {
        document.getElementById('loginForm')?.classList.add('active');
        document.getElementById('registerForm')?.classList.remove('active');
        document.querySelectorAll('.auth-tab').forEach(tab => {
            if (tab.getAttribute('data-tab') === 'login') {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        AuthUtils.clearFormErrors();
    },
    
    switchToRegister() {
        document.getElementById('loginForm')?.classList.remove('active');
        document.getElementById('registerForm')?.classList.add('active');
        document.querySelectorAll('.auth-tab').forEach(tab => {
            if (tab.getAttribute('data-tab') === 'register') {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        AuthUtils.clearFormErrors();
    },
    
    /**
     * Show authentication success message
     */
    showAuthSuccess(message) {
        const alertDiv = document.getElementById('successMessage');
        if (alertDiv) {
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Hide error message
            const errorDiv = document.getElementById('errorMessage');
            if (errorDiv) errorDiv.style.display = 'none';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }
    },
    
    /**
     * Show authentication error message
     */
    showAuthError(message) {
        const alertDiv = document.getElementById('errorMessage');
        if (alertDiv) {
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Hide success message
            const successDiv = document.getElementById('successMessage');
            if (successDiv) successDiv.style.display = 'none';
        }
    }
};

// =============================================================================
// EVENT HANDLERS
// =============================================================================

const AuthEventHandlers = {
    /**
     * Initialize all event handlers
     */
    init() {
        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabType = tab.getAttribute('data-tab');
                if (tabType === 'login') {
                    AuthManager.switchToLogin();
                } else if (tabType === 'register') {
                    AuthManager.switchToRegister();
                }
            });
        });
        
        // Form submissions
        document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail')?.value;
            const password = document.getElementById('loginPassword')?.value;
            const rememberMe = document.getElementById('rememberMe')?.checked;
            
            await AuthManager.login(email, password, rememberMe);
        });
        
        document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('registerName')?.value;
            const email = document.getElementById('registerEmail')?.value;
            const password = document.getElementById('registerPassword')?.value;
            const confirmPassword = document.getElementById('confirmPassword')?.value;
            
            await AuthManager.register(name, email, password, confirmPassword);
        });
        
        // Clear errors on input focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', () => {
                input.classList.remove('error');
                const errorElement = input.parentNode.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
        
        // Hide alerts on click
        document.getElementById('errorMessage')?.addEventListener('click', function() {
            this.style.display = 'none';
        });
        document.getElementById('successMessage')?.addEventListener('click', function() {
            this.style.display = 'none';
        });
    }
};

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing Authentication Page...');
    
    // Initialize event handlers
    AuthEventHandlers.init();
    
    // Show login form by default
    AuthManager.switchToLogin();
    
    console.log('Authentication Page initialized successfully');
});

// Make AuthManager available globally if needed
window.AuthManager = AuthManager;