<?php
//app root
define('APP_ROOT', __DIR__);

// Debug: Check if we got here via proper routing or need to redirect
if (php_sapi_name() !== 'cli') {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // If REQUEST_URI doesn't match SCRIPT_NAME, we might have a routing issue
    // For debugging on Azure
    $_SERVER['REQUEST_URI'] = $request_uri;
}

//headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header_remove('X-Powered-By');

//App configurations
require_once 'config.php';

// Composer packages (TCPDF, etc.)
$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

//Error reporting
if (IS_DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

//use database query builder
require_once __DIR__ . '/scheme/Database.php';

//use helper functions
require_once __DIR__ . '/scheme/helpers.php';

//use router class
require_once __DIR__ . '/scheme/Router.php';
$router = new Router();

//call all routes
require_once  __DIR__ . '/routes.php';

//dispatch
$router->run();