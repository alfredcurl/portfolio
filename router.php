<?php

/**
 * PHP Built-in Server Router
 * Run: php -S localhost:8080 router.php
 * From the /portfolio/ directory
 */

$request = $_SERVER['REQUEST_URI'];
$path    = parse_url($request, PHP_URL_PATH);
$file    = __DIR__ . $path;

// Serve real static files directly (css, js, images, uploads)
if (is_file($file)) {
    return false;
}

// Route requests
if (preg_match('#^/admin#', $path)) {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/admin/index.php';
} elseif (preg_match('#^/api/upload#', $path)) {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/api/upload.php';
} elseif (preg_match('#^/api/cms#', $path)) {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/api/cms.php';
} elseif (preg_match('#^/api/contact#', $path)) {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/api/contact.php';
} elseif (preg_match('#^/setup#', $path)) {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/setup.php';
} else {
    define('ROOT_PATH', __DIR__);
    require_once __DIR__ . '/public/index.php';
}
