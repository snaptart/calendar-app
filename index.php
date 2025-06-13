<?php
session_start();

// Check authentication
require_once 'backend/database/config.php';
require_once 'backend/auth/auth.php';

$auth = new Auth($pdo);
$authCheck = $auth->checkAuth();

if (!$authCheck['authenticated']) {
    header('Location: frontend/pages/login.php');
    exit;
}

$currentUser = $authCheck['user'];

// Load and initialize page controller
require_once 'frontend/controller/PageController.php';
$pageController = new PageController($currentUser);

// Handle the request
$pageController->handleRequest();
?>