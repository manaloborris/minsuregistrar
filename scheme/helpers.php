<?php
defined('APP_ROOT') OR exit('No direct script access allowed');
/**
 * Global Helper Functions
 */
session_start();

//get base url
function base_url(): string
{
    $scheme = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

    return $scheme . '://' . $host . ($path ? $path : '') . '/';
}


//generate url based on BASE_URL
function url(string $path = ''): string
{
    return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}

//generate csrf field
function csrf_field() {
    $router = new Router();
    return $router->csrf_field();
}

//set session flash data
function set_flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

//get session flash data
function get_flash(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

//json response
function json_response($data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

//Database connection
function db() {
    $db = new Database();
    return $db;
}

//escape output
function esc($var, $double_encode = TRUE): string|array
{
    if ($var === null) {
        return '';
    }

	if (is_array($var))
	{
		foreach (array_keys($var) as $key)
		{
			$var[$key] = esc($var[$key], $double_encode);
		}

		return $var;
	}

    return htmlspecialchars((string) $var, ENT_QUOTES, 'utf-8', $double_encode);
}

//get segments
function segment($seg)
{
    $parts = is_int($seg) ? explode('/', $_SERVER['REQUEST_URI']) : FALSE;
    return isset($parts[$seg]) ? $parts[$seg] : false;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_student_logged_in(): bool
{
    $student = $_SESSION['auth']['student'] ?? null;
    if (empty($student) || empty($student['student_id'])) {
        return false;
    }

    $studentId = strtoupper(trim((string) ($student['student_id'] ?? '')));
    if ($studentId === '') {
        unset($_SESSION['auth']['student']);
        return false;
    }

    $userRow = db()->table('users')
        ->select('id')
        ->where('username', $studentId)
        ->where('role', 'student')
        ->limit(1)
        ->get();

    $studentRow = db()->table('students')
        ->select('student_id')
        ->where('student_id', $studentId)
        ->limit(1)
        ->get();

    if (!$userRow || !$studentRow) {
        unset($_SESSION['auth']['student']);
        return false;
    }

    return true;
}

function is_admin_logged_in(): bool
{
    $admin = $_SESSION['auth']['admin'] ?? null;
    if (empty($admin) || empty($admin['admin_id'])) {
        return false;
    }

    $adminId = (int) ($admin['admin_id'] ?? 0);
    if ($adminId <= 0) {
        unset($_SESSION['auth']['admin']);
        return false;
    }

    $adminRole = strtolower((string) ($admin['role'] ?? 'admin'));

    // Superadmin sessions should remain usable even if the admin row was truncated or temporarily unavailable.
    // This prevents hard lockouts in recovery scenarios while keeping normal admin accounts validated below.
    if ($adminRole === 'superadmin') {
        try {
            $adminRow = db()->table('admins')
                ->select('admin_id, is_active')
                ->where('admin_id', $adminId)
                ->limit(1)
                ->get();

            if ($adminRow && (int) ($adminRow['is_active'] ?? 1) !== 1) {
                unset($_SESSION['auth']['admin']);
                return false;
            }
        } catch (
            Throwable $e
        ) {
            // Keep the current superadmin session if the admins table is temporarily unavailable.
            return true;
        }

        return true;
    }

    try {
        $adminRow = db()->table('admins')
            ->select('admin_id, is_active')
            ->where('admin_id', $adminId)
            ->limit(1)
            ->get();
    } catch (Throwable $e) {
        unset($_SESSION['auth']['admin']);
        return false;
    }

    if (!$adminRow || (int) ($adminRow['is_active'] ?? 1) !== 1) {
        unset($_SESSION['auth']['admin']);
        return false;
    }

    return true;
}

function current_student_id(): ?string
{
    return is_student_logged_in() ? ($_SESSION['auth']['student']['student_id'] ?? null) : null;
}

function current_admin_id(): ?int
{
    return is_admin_logged_in() ? (int) ($_SESSION['auth']['admin']['admin_id'] ?? 0) : null;
}

function verify_password(string $input, string $stored): bool
{
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
        return password_verify($input, $stored);
    }

    return hash_equals($stored, $input);
}

function ensure_dir(string $absolutePath): void
{
    if (!is_dir($absolutePath)) {
        mkdir($absolutePath, 0775, true);
    }
}

function upload_public_file(array $file, string $subDir = 'uploads'): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $name = $file['name'] ?? '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    $baseDir = APP_ROOT . '/public/' . trim($subDir, '/');
    ensure_dir($baseDir);

    $safeName = uniqid('req_', true) . '.' . $ext;
    $target = $baseDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return trim($subDir, '/') . '/' . $safeName;
}

function upload_public_image(array $file, string $subDir = 'uploads/profile-photos'): ?string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $name = $file['name'] ?? '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    $maxBytes = 2 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) <= 0 || (int) ($file['size'] ?? 0) > $maxBytes) {
        return null;
    }

    $mime = mime_content_type($file['tmp_name'] ?? '') ?: '';
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        return null;
    }

    $baseDir = APP_ROOT . '/public/' . trim($subDir, '/');
    ensure_dir($baseDir);

    $safeName = uniqid('profile_', true) . '.' . $ext;
    $target = $baseDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return trim($subDir, '/') . '/' . $safeName;
}