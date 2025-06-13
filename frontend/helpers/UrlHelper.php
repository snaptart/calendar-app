<?php

class UrlHelper {
    
    public static function base($path = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        
        if ($path && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        return $protocol . '://' . $host . $basePath . $path;
    }
    
    public static function calendar($year = null, $month = null, $day = null) {
        if ($year && $month && $day) {
            return self::base("/calendar/{$year}/{$month}/{$day}");
        } elseif ($year && $month) {
            return self::base("/calendar/{$year}/{$month}");
        }
        return self::base('/calendar');
    }
    
    public static function events($eventId = null, $action = null) {
        if ($eventId && $action === 'edit') {
            return self::base("/events/{$eventId}/edit");
        } elseif ($eventId) {
            return self::base("/events/{$eventId}");
        }
        return self::base('/events');
    }
    
    public static function users($userId = null) {
        if ($userId) {
            return self::base("/users/{$userId}");
        }
        return self::base('/users');
    }
    
    public static function userEvents($userId) {
        return self::base("/users/{$userId}/events");
    }
    
    public static function import() {
        return self::base('/import');
    }
    
    public static function api($endpoint = '') {
        if ($endpoint && !str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }
        return self::base('/api' . $endpoint);
    }
    
    public static function current() {
        return $_SERVER['REQUEST_URI'];
    }
    
    public static function redirect($url, $statusCode = 302) {
        if (!str_starts_with($url, 'http')) {
            $url = self::base($url);
        }
        
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    public static function isActive($path, $exact = false) {
        // Handle query parameter based routing
        $currentPage = $_GET['page'] ?? 'calendar';
        
        // Convert path to page name for comparison
        $pageName = ltrim($path, '/');
        if (empty($pageName)) {
            $pageName = 'calendar';
        }
        
        // Direct comparison for our query-based routing
        return $currentPage === $pageName;
    }
}