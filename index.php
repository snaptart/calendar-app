<?php
/**
 * Root index.php - Entry point with authentication check
 * 
 * This file serves as the entry point for the collaborative calendar application.
 * It now checks authentication and redirects accordingly.
 */

// Set proper headers
header('Content-Type: text/html; charset=UTF-8');

require_once 'backend/database/config.php';
require_once 'backend/auth/Auth.php';

// Initialize authentication
$auth = new Auth($pdo);

// Check if user is authenticated
$authResult = $auth->checkAuth();

if ($authResult['authenticated']) {
    // User is authenticated, redirect to calendar
    $frontend_url = './frontend/pages/index.html';
} else {
    // User is not authenticated, redirect to login
    $frontend_url = './frontend/pages/login.html';
}

// Check if the target file exists
if (file_exists($frontend_url)) {
    header("Location: $frontend_url");
    exit();
} else {
    // Fallback error page if frontend is missing
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Calendar App - Setup Required</title>
        <style>
            body { 
                font-family: 'Noto Sans', Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                padding: 40px; 
                border-radius: 16px; 
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            }
            h1 { color: #e74c3c; margin-bottom: 16px; }
            p { color: #666; line-height: 1.6; }
            .setup-list { text-align: left; margin: 20px 0; }
            .setup-list li { margin: 10px 0; }
            code { 
                background: #f7fafc; 
                padding: 2px 6px; 
                border-radius: 4px; 
                font-family: 'Courier New', monospace; 
                font-size: 0.875rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Setup Required</h1>
            <p>The collaborative calendar application requires setup before use.</p>
            
            <div class="setup-list">
                <h3>Setup Steps:</h3>
                <ol>
                    <li>Ensure all frontend files are in place:
                        <ul>
                            <li><code>frontend/pages/login.html</code></li>
                            <li><code>frontend/pages/index.html</code></li>
                            <li><code>frontend/js/auth.js</code></li>
                            <li><code>frontend/js/script.js</code></li>
                        </ul>
                    </li>
                    <li>Configure your database settings in: <code>backend/database/config.php</code></li>
                    <li>Import the database schema from: <code>documentation/calendar-app.sql</code></li>
                    <li>Ensure authentication classes are in place:
                        <ul>
                            <li><code>backend/auth/Auth.php</code></li>
                            <li><code>backend/auth/Session.php</code></li>
                        </ul>
                    </li>
                    <li>Verify your web server has PHP and MySQL enabled</li>
                </ol>
            </div>
            
            <p>Once setup is complete, refresh this page to access the calendar application.</p>
            
            <p><strong>Authentication Features:</strong></p>
            <ul style="text-align: left; display: inline-block;">
                <li>User registration and login</li>
                <li>Remember me functionality</li>
                <li>Secure session management</li>
                <li>User-specific calendar events</li>
                <li>Real-time collaborative features</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>