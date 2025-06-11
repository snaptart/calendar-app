// Authentication System for Collaborative Calendar
// Location: frontend/js/auth.js

// =============================================================================
// AUTHENTICATION UTILITIES
// =============================================================================

const AuthUtils = {
    apiEndpoint: 'backend/api.php',
    
    /**
     * Make authenticated API request
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
     * Show message to user
     */
    showMessage(message, type = 'error') {
        const errorEl = document.getElementById('errorMessage');
        const successEl = document.getElementById('successMessage');
        
        // Hide all messages first
        errorEl.style.display = 'none';
        successEl.style.display = 'none';
        
        // Show appropriate message
        if (type === 'error') {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        } else {
            successEl.textContent = message;
            successEl.style.display = 'block';
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorEl.style.display = 'none';
            successEl.style.display = 'none';
        }, 5000);
    },
    
    /**
     * Set loading state for button
     */
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.classList.remove('loading');
        }
    },
    
    /**
     * Redirect to calendar page
     */
    redirectToCalendar() {
        window.location.href = 'index.php';
    }
};

// =============================================================================
// AUTHENTICATION API CLIENT
// =============================================================================

const AuthAPI = {
    /**
     * Login user
     */
    async login(email, password, rememberMe = false) {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'login',
                email,
                password,
                rememberMe
            })
        });
    },
    
    /**
     * Register new user
     */
    async register(name, email, password) {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'register',
                name,
                email,
                password
            })
        });
    },
    
    /**
     * Check if user is authenticated
     */
    async checkAuth() {
        return AuthUtils.makeRequest(`${AuthUtils.apiEndpoint}?action=check_auth`);
    },
    
    /**
     * Logout user
     */
    async logout() {
        return AuthUtils.makeRequest(AuthUtils.apiEndpoint, {
            method: 'POST',
            body: JSON.stringify({
                action: 'logout'
            })
        });
    }
};

// =============================================================================
// TAB MANAGEMENT
// =============================================================================

const TabManager = {
    init() {
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                // Update tab states
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update form states
                forms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === `${targetTab}Form`) {
                        form.classList.add('active');
                    }
                });
                
                // Clear any messages
                AuthUtils.showMessage('', 'clear');
            });
        });
    }
};

// =============================================================================
// LOGIN FORM HANDLER
// =============================================================================

const LoginForm = {
    init() {
        const form = document.getElementById('loginForm');
        if (!form) return;
        
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;
        const rememberMe = document.getElementById('rememberMe').checked;
        const submitButton = e.target.querySelector('button[type="submit"]');
        
        // Validate input
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
            return;
        }
        
        if (!password) {
            AuthUtils.showMessage('Please enter your password');
            return;
        }
        
        try {
            AuthUtils.setButtonLoading(submitButton, true);
            
            const response = await AuthAPI.login(email, password, rememberMe);
            
            if (response.success) {
                AuthUtils.showMessage('Login successful! Redirecting...', 'success');
                
                // Redirect after a short delay
                setTimeout(() => {
                    AuthUtils.redirectToCalendar();
                }, 1000);
            } else {
                AuthUtils.showMessage(response.error || 'Login failed');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            AuthUtils.showMessage(error.message || 'Login failed. Please try again.');
        } finally {
            AuthUtils.setButtonLoading(submitButton, false);
        }
    }
};

// =============================================================================
// REGISTRATION FORM HANDLER
// =============================================================================

const RegisterForm = {
    init() {
        const form = document.getElementById('registerForm');
        if (!form) return;
        
        form.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const name = document.getElementById('registerName').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const submitButton = e.target.querySelector('button[type="submit"]');
        
        // Validate input
        if (!name || name.length < 2) {
            AuthUtils.showMessage('Please enter a valid name (at least 2 characters)');
            return;
        }
        
        if (!AuthUtils.validateEmail(email)) {
            AuthUtils.showMessage('Please enter a valid email address');
            return;
        }
        
        if (!AuthUtils.validatePassword(password)) {
            AuthUtils.showMessage('Password must be at least 6 characters long');
            return;
        }
        
        if (password !== confirmPassword) {
            AuthUtils.showMessage('Passwords do not match');
            return;
        }
        
        try {
            AuthUtils.setButtonLoading(submitButton, true);
            
            const response = await AuthAPI.register(name, email, password);
            
            if (response.success) {
                AuthUtils.showMessage('Account created successfully! You can now sign in.', 'success');
                
                // Switch to login tab after successful registration
                setTimeout(() => {
                    document.querySelector('.auth-tab[data-tab="login"]').click();
                    
                    // Pre-fill email in login form
                    document.getElementById('loginEmail').value = email;
                }, 1500);
            } else {
                AuthUtils.showMessage(response.error || 'Registration failed');
            }
            
        } catch (error) {
            console.error('Registration error:', error);
            AuthUtils.showMessage(error.message || 'Registration failed. Please try again.');
        } finally {
            AuthUtils.setButtonLoading(submitButton, false);
        }
    }
};

// =============================================================================
// AUTH GUARD - Check if user is already logged in
// =============================================================================

const AuthGuard = {
    async init() {
        try {
            const response = await AuthAPI.checkAuth();
            
            if (response.authenticated) {
                // User is already logged in, redirect to calendar
                console.log('User already authenticated:', response.user);
                AuthUtils.redirectToCalendar();
                return true;
            }
        } catch (error) {
            console.log('User not authenticated or session expired');
        }
        
        return false;
    }
};

// =============================================================================
// APPLICATION INITIALIZATION
// =============================================================================

const AuthApp = {
    async init() {
        console.log('Initializing Authentication System...');
        
        // Check if user is already logged in
        const isAuthenticated = await AuthGuard.init();
        
        if (!isAuthenticated) {
            // Initialize UI components
            TabManager.init();
            LoginForm.init();
            RegisterForm.init();
            
            // Focus on email field
            document.getElementById('loginEmail')?.focus();
            
            console.log('Authentication system initialized');
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AuthApp.init();
});

// Handle browser back/forward navigation
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was loaded from cache, check auth status again
        AuthGuard.init();
    }
});