<?php
/**
 * Router for PHP built-in development server
 * Place this in websystem folder and run:
 * php -S localhost:8000 router.php
 */

$requested_file = $_SERVER['REQUEST_URI'];

// Remove query string for file checking
$path = parse_url($requested_file, PHP_URL_PATH);

// Don't intercept real files or directories
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Let built-in server serve it
}

// Route everything to index.php
$_SERVER['REQUEST_URI'] = $path;
require __DIR__ . '/index.php';
