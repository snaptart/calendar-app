<?php
/**
* Login Page - Using new modular architecture
*/

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../layouts/AuthLayout.php';
require_once __DIR__ . '/../components/forms/LoginForm.php';

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
	'title' => 'Login - Collaborative Calendar'
]);

// Create the layout
$layout = new AuthLayout($pageConfig);

// Render the page
$layout->renderAuthPage(function() {
	// Render the login form with tabs
	LoginForm::renderLoginWithTabs();
});
?>