<?php
// =============================================
// Environment Configuration (SECURITY)
// Load from environment variables or .env file
// =============================================

// Try to load from .env file in root
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Helper function to get env variables with fallback
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

// Hosted fallback values retained for reference:
// define('DB_HOST',    'sql313.infinityfree.com');
// define('DB_NAME',    'if0_41440545_alfred');
// define('DB_USER',    'if0_41440545');
// define('DB_PASS',    'YSW6pHennPea0E');
// define('DB_HOST',    'mysql'); // Docker service hostname
// Load database config from environment with local development fallbacks.
define('DB_HOST',    env('DB_HOST',    'localhost'));
define('DB_NAME',    env('DB_NAME',    'alfred'));
define('DB_USER',    env('DB_USER',    'root'));
define('DB_PASS',    env('DB_PASS',    ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('DB_PORT',    env('DB_PORT',    3306));
