<?php
/*
 * Vaccination Management System (e-Vaccination Portal)
 * Database Configuration File
 */
define('APP_NAME', 'e-Vaccination Portal');
define('APP_TAGLINE', 'Child Immunization & Hospital Appointment Management System');
define('APP_VERSION', '1.0.0');

// Database Driver:'mysql'
// SQLite requires zero setup and works out of the box with PHP.
// Change to 'mysql' when running on XAMPP, WAMP, or production LAMP stacks.
define('DB_DRIVER', 'mysql');

// MySQL Settings (used if DB_DRIVER is set to 'mysql')
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'vaccination_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL detection for proper routing and asset loading
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// root path
$basePath = preg_replace('#/(admin|parent|hospital)$#', '', $scriptDir);
if ($basePath === '/' || $basePath === '') {
    define('BASE_URL', '');
} else {
    define('BASE_URL', rtrim($basePath, '/'));
}
