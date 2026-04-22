<?php
/**
 * Router initialization - prepended to all PHP requests
 * This ensures all requests are routed through index.php
 */

// Only run if we're not already in index.php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'index.php') {
    // Reconstruct the request URI
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? $_SERVER['REQUEST_URL'] ?? '/';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
    
    // Include index.php to handle the request
    require_once __DIR__ . '/index.php';
    exit;
}
