<?php
/**
 * App configurations
 */

//Dev or Prod
define('IS_DEV', true);

//Database Config (Azure-ready)
define('DB_DRIVER', 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: 'minsu-mysql-server.mysql.database.azure.com');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'minsu_eregistrar');
define('DB_USERNAME', getenv('DB_USER') ?: 'minsuadmin@minsu-mysql-server');
define('DB_PASSWORD', getenv('DB_PASS') ?: 'MinSU@2024Secure!');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');
define('DB_PATH', '');

// Admin access gate key (moved to environment secret for production)
define('ADMIN_ACCESS_KEY', getenv('ADMIN_ACCESS_KEY') ?: 'minsuregistrarloginaccess');

// Rate limiting config
define('RATE_LIMIT_ENABLED', getenv('RATE_LIMIT_ENABLED') === 'false' ? false : true);
define('RATE_LIMIT_MAX_ATTEMPTS', (int) (getenv('RATE_LIMIT_MAX_ATTEMPTS') ?: 5));
define('RATE_LIMIT_WINDOW_MINUTES', (int) (getenv('RATE_LIMIT_WINDOW_MINUTES') ?: 15));

// Registration security limits
define('REGISTER_RATE_LIMIT_MAX_ATTEMPTS', (int) (getenv('REGISTER_RATE_LIMIT_MAX_ATTEMPTS') ?: 8));
define('REGISTER_RATE_LIMIT_WINDOW_MINUTES', (int) (getenv('REGISTER_RATE_LIMIT_WINDOW_MINUTES') ?: 15));

// Forgot password two-step security limits
define('FORGOT_REQUEST_MAX_ATTEMPTS', (int) (getenv('FORGOT_REQUEST_MAX_ATTEMPTS') ?: 3));
define('FORGOT_REQUEST_WINDOW_MINUTES', (int) (getenv('FORGOT_REQUEST_WINDOW_MINUTES') ?: 15));
define('FORGOT_CODE_MAX_ATTEMPTS', (int) (getenv('FORGOT_CODE_MAX_ATTEMPTS') ?: 3));
define('FORGOT_CODE_WINDOW_MINUTES', (int) (getenv('FORGOT_CODE_WINDOW_MINUTES') ?: 15));
define('FORGOT_CODE_EXPIRY_MINUTES', (int) (getenv('FORGOT_CODE_EXPIRY_MINUTES') ?: 10));

// Registration email OTP controls
$registerEmailOtpEnv = getenv('REGISTER_EMAIL_OTP_ENABLED');
$registerEmailOtpEnabled = $registerEmailOtpEnv === false
	? IS_DEV
	: in_array(strtolower(trim((string) $registerEmailOtpEnv)), ['1', 'true', 'yes', 'on'], true);
define('REGISTER_EMAIL_OTP_ENABLED', $registerEmailOtpEnabled);
define('REGISTER_OTP_EXPIRY_MINUTES', (int) (getenv('REGISTER_OTP_EXPIRY_MINUTES') ?: 10));
define('REGISTER_OTP_MAX_ATTEMPTS', (int) (getenv('REGISTER_OTP_MAX_ATTEMPTS') ?: 3));
define('REGISTER_OTP_WINDOW_MINUTES', (int) (getenv('REGISTER_OTP_WINDOW_MINUTES') ?: 15));

// Email delivery config
// Optional local SMTP file for development convenience.
$mailLocal = [];
$mailLocalPath = APP_ROOT . '/smtp.local.php';
if (is_file($mailLocalPath)) {
	$loadedMailLocal = require $mailLocalPath;
	if (is_array($loadedMailLocal)) {
		$mailLocal = $loadedMailLocal;
	}
}

$mailValue = static function (string $envKey, string $localKey, string $default = '') use ($mailLocal): string {
	$env = getenv($envKey);
	if ($env !== false && trim((string) $env) !== '') {
		return (string) $env;
	}

	$local = $mailLocal[$localKey] ?? '';
	if (trim((string) $local) !== '') {
		return (string) $local;
	}

	return $default;
};

define('MAIL_DRIVER', $mailValue('MAIL_DRIVER', 'MAIL_DRIVER', 'smtp'));
define('MAIL_FROM_ADDRESS', $mailValue('MAIL_FROM_ADDRESS', 'MAIL_FROM_ADDRESS'));
define('MAIL_FROM_NAME', $mailValue('MAIL_FROM_NAME', 'MAIL_FROM_NAME', 'MinSU e-Registrar'));
define('MAIL_LOG_PATH', getenv('MAIL_LOG_PATH') ?: APP_ROOT . '/storage/logs/mail.log');
define('SMTP_HOST', $mailValue('SMTP_HOST', 'SMTP_HOST'));
define('SMTP_PORT', (int) $mailValue('SMTP_PORT', 'SMTP_PORT', '587'));
define('SMTP_AUTH', strtolower($mailValue('SMTP_AUTH', 'SMTP_AUTH', 'true')) !== 'false');
define('SMTP_USERNAME', $mailValue('SMTP_USERNAME', 'SMTP_USERNAME'));
define('SMTP_PASSWORD', $mailValue('SMTP_PASSWORD', 'SMTP_PASSWORD'));
define('SMTP_ENCRYPTION', strtolower((string) $mailValue('SMTP_ENCRYPTION', 'SMTP_ENCRYPTION', 'tls')));
define('SMTP_TIMEOUT', (int) $mailValue('SMTP_TIMEOUT', 'SMTP_TIMEOUT', '15'));

