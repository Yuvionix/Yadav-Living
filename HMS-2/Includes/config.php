<?php
/**
 * Yadav Living - Database Connection & System Configuration
 * 
 * Supports environment variables via .env.local (developer secrets) and .env (deployments).
 * Zero Composer dependencies required - powered by lightweight native env_loader.php.
 * 
 * Team reminder: Always place custom credentials in .env.local (git-ignored),
 * never hardcode database passwords in tracked repository files!
 */
require_once __DIR__ . '/env_loader.php';

// Database configuration with environment variable support
define('DB_SERVER', hms_env('DB_HOST', 'localhost'));
define('DB_USERNAME', hms_env('DB_USER', 'root'));
define('DB_PASSWORD', hms_env('DB_PASS', ''));
define('DB_NAME', hms_env('DB_NAME', 'hms'));
define('DB_PORT', (int)hms_env('DB_PORT', 3306));
$dbSslCa = hms_env('DB_SSL_CA', '');
$appEnvironment = strtolower((string)hms_env('APP_ENV', 'local'));
if ($dbSslCa === '' && $appEnvironment === 'production' && !in_array(DB_SERVER, array('localhost', '127.0.0.1', '::1'), true)) {
    $dbSslCa = '/etc/ssl/certs/ca-certificates.crt';
}
$privateUploadDir = hms_env('HMS_PRIVATE_UPLOAD_DIR', '');
if ($privateUploadDir === '') {
    $privateUploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private_uploads';
}
define('HMS_PRIVATE_UPLOAD_DIR', rtrim($privateUploadDir, '/\\'));

/* Attempt to connect to MySQL database */
try {
    $con = mysqli_init();
    if ($con === false) {
        throw new RuntimeException('Unable to initialize the database connection.');
    }

    $connectFlags = 0;
    if ($dbSslCa !== '') {
        if (!is_readable($dbSslCa) || !mysqli_ssl_set($con, null, null, $dbSslCa, null, null)) {
            throw new RuntimeException('The configured database TLS certificate is unavailable or invalid.');
        }
        $connectFlags |= MYSQLI_CLIENT_SSL;
    }

    if (!mysqli_real_connect($con, DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT, null, $connectFlags)) {
        throw new RuntimeException(mysqli_connect_error());
    }
} catch (Throwable $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(503);
    die("The service is temporarily unavailable. Please try again later.");
}

require_once __DIR__ . '/kyc_files.php';
?>