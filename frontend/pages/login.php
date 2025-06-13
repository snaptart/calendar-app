<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Calendar App</title>
    
    <!-- Google Fonts - Noto Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Application Styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">📅 Calendar Login</h1>
            
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Sign In</button>
                <button class="auth-tab" data-tab="register">Create Account</button>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" class="auth-form active">
                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" placeholder="Enter your email" autocomplete="email" required>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" placeholder="Enter your password" autocomplete="current-password" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="rememberMe">
                        Remember me for 30 days
                    </label>
                </div>
                
                <button type="submit" id="loginBtn" class="btn btn-primary btn-full">Sign In</button>
            </form>
            
            <!-- Register Form -->
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label for="registerName">Full Name</label>
                    <input type="text" id="registerName" placeholder="Enter your full name" autocomplete="name" required>
                </div>
                
                <div class="form-group">
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" placeholder="Enter your email" autocomplete="email" required>
                </div>
                
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" placeholder="Create a password (min 6 characters)" autocomplete="new-password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" placeholder="Confirm your password" autocomplete="new-password" required>
                </div>
                
                <button type="submit" id="registerBtn" class="btn btn-primary btn-full">Create Account</button>
            </form>
            
            <!-- Message containers -->
            <div id="errorMessage" class="message error-message" style="display: none;"></div>
            <div id="successMessage" class="message success-message" style="display: none;"></div>
        </div>
    </div>
    
    <!-- Application JavaScript -->
    <script type="module" src="../assets/js/auth.js"></script>
</body>
</html>