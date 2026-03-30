<?php
// Main entry point - routes to either the front-end or admin CMS
define('ROOT_PATH', __DIR__);
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('API_PATH', ROOT_PATH . '/api');

session_start();

$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($base_path, '', $request_uri);
$path = strtok($path, '?');
$path = trim($path, '/');

// Route requests
if (strpos($path, 'admin') === 0) {
    require_once ROOT_PATH . '/admin/index.php';
} elseif (strpos($path, 'api') === 0) {
    require_once ROOT_PATH . '/api/router.php';
} else {
    require_once ROOT_PATH . '/public/index.php';
}
