<?php
/**
 * Logout Handler
 * Location: frontend/pages/logout.php
 * 
 * Handles user logout and cleanup
 */

// Include service layer
require_once __DIR__ . '/../services/AuthService.php';

// Initialize auth service
$auth = new AuthService();

// Perform logout
try {
    $result = $auth->logout();
    
    // Clear any additional session data
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Redirect to login page with success message
    header('Location: ./login.php?message=logged_out');
    exit;
    
} catch (Exception $e) {
    // Even if logout fails, redirect to login
    error_log("Logout error: " . $e->getMessage());
    header('Location: ./login.php');
    exit;
}
?>