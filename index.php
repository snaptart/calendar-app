<?php
/**
 * Root index.php - Redirects to frontend application
 * 
 * This file serves as the entry point for the collaborative calendar application.
 * For now, it simply redirects to the frontend HTML page.
 * 
 * Future enhancements could include:
 * - Environment detection (dev/staging/prod)
 * - Authentication checks
 * - Server-side routing
 * - Initial configuration loading
 */

// Set proper headers
header('Content-Type: text/html; charset=UTF-8');

// For development, redirect to frontend
$frontend_url = './frontend/pages/index.html';

// Check if the frontend file exists
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
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: #f5f5f5; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                padding: 40px; 
                border-radius: 8px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            }
            h1 { color: #e74c3c; }
            p { color: #666; line-height: 1.6; }
            .setup-list { text-align: left; margin: 20px 0; }
            .setup-list li { margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Setup Required</h1>
            <p>The collaborative calendar application requires setup before use.</p>
            
            <div class="setup-list">
                <h3>Setup Steps:</h3>
                <ol>
                    <li>Ensure all frontend files are in place: <code>frontend/pages/index.html</code></li>
                    <li>Configure your database settings in: <code>backend/database/config.php</code></li>
                    <li>Import the database schema from: <code>documentation/calendar-app.sql</code></li>
                    <li>Verify your web server has PHP and MySQL enabled</li>
                </ol>
            </div>
            
            <p>Once setup is complete, refresh this page to access the calendar application.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>