<?php

return function (): bool {
    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    
    // Allow GET requests to login pages and POST/GET requests to admin access gate
    $isLoginOrAdminAccess = (
        ($requestMethod === 'GET' && preg_match('#(^|/)(login|student/login|admin/login|admin/access)$#i', $requestPath)) ||
        preg_match('#(^|/)admin/access$#i', $requestPath) // Allow both GET and POST to admin/access
    );

    if ($isLoginOrAdminAccess) {
        return true;
    }

    if (is_student_logged_in()) {
        redirect('student/dashboard');
    }

    if (is_admin_logged_in()) {
        redirect('admin/dashboard');
    }

    return true;
};
