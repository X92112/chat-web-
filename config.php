<?php
// Snapwall Configuration

// Environment & Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database credentials for XAMPP
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'snapwall');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Paths and URLs
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$detected_base = (stripos($request_uri, '/nexly') === 0) ? '/nexly' : '/snapwall';
define('BASE_URL', $detected_base);
define('APP_NAME', 'Nexly');
define('ROOT_PATH', __DIR__);
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Upload constraints
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIMES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
]);

// Rate limiting (operations per time window in seconds)
define('RATE_LIMIT_UPLOAD_COUNT', 6);
define('RATE_LIMIT_UPLOAD_WINDOW', 60);

define('RATE_LIMIT_COMMENT_COUNT', 20);
define('RATE_LIMIT_COMMENT_WINDOW', 60);

// Default timezone
date_default_timezone_set('UTC');
