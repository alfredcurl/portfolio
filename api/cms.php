<?php
// =============================================
// API: CMS — Save section data
// POST /api/cms.php
// Auth required
// =============================================

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
session_start();
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/datastore.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body    = file_get_contents('php://input');
$payload = json_decode($body, true);

if (!$payload) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

// ── Handle soft delete ────────────────────────────────────
if (isset($payload['action']) && $payload['action'] === 'delete_item') {
    $section = $payload['section'] ?? '';
    $id = (int)($payload['id'] ?? 0);
    if ($id > 0 && DataStore::softDelete($section, $id)) {
        echo json_encode(['success' => true, 'message' => "Item from '$section' deleted (soft delete)."]);
    } else {
        echo json_encode(['success' => false, 'error' => "Failed to delete item from '$section'."]);
    }
    exit;
}

// ── Handle restore ─────────────────────────────────────────
if (isset($payload['action']) && $payload['action'] === 'restore_item') {
    $section = $payload['section'] ?? '';
    $id = (int)($payload['id'] ?? 0);
    if ($id > 0 && DataStore::restore($section, $id)) {
        echo json_encode(['success' => true, 'message' => "Item from '$section' restored."]);
    } else {
        echo json_encode(['success' => false, 'error' => "Failed to restore item from '$section'."]);
    }
    exit;
}

// ── Handle save single item ────────────────────────────────
if (isset($payload['action']) && $payload['action'] === 'save_item') {
    $section = $payload['section'] ?? '';
    $data = $payload['data'] ?? null;
    if ($data && ($id = DataStore::saveItem($section, $data))) {
        echo json_encode(['success' => true, 'id' => $id, 'message' => "Item saved in '$section'."]);
    } else {
        echo json_encode(['success' => false, 'error' => "Failed to save item in '$section'."]);
    }
    exit;
}

// ── Handle change password ────────────────────────────────
if (isset($payload['action']) && $payload['action'] === 'change_password') {
    $new = $payload['new_password'] ?? '';
    if (strlen($new) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }
    $ok = Auth::changePassword($new);
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Password changed successfully.' : 'Failed to change password.']);
    exit;
}

// ── Handle section save ───────────────────────────────────
if (isset($payload['action']) && $payload['action'] === 'save_section') {
    $allowed = ['hero', 'about', 'skills', 'experience', 'portfolio', 'ventures', 'education', 'contact', 'site_settings'];
    $section = $payload['section'] ?? '';
    $data    = $payload['data']    ?? null;
    if (!in_array($section, $allowed)) {
        echo json_encode(['success' => false, 'error' => "Invalid section: '$section'"]);
        exit;
    }
    $ok = DataStore::save($section, $data);
    if ($ok) {
        echo json_encode(['success' => true, 'message' => "Section '$section' saved."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database write failed. Check MySQL connection.']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request or action missing.']);
