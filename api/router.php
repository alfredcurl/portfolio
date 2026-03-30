<?php
// Simple API router
define('ROOT_PATH', dirname(__DIR__));

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

if (strpos($path, '/api/cms') !== false) {
    require_once ROOT_PATH . '/api/cms.php';
} elseif (strpos($path, '/api/contact') !== false) {
    require_once ROOT_PATH . '/api/contact.php';
} else {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
}
