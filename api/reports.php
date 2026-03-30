<?php
// =============================================
// API: Reports — Stats for the CMS
// GET /api/reports.php
// Auth required
// =============================================

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
session_start();
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

try {
    // Count active items
    $stats = [
        'portfolio'  => DB::row("SELECT COUNT(*) as c FROM portfolio_projects WHERE deleted_at IS NULL")['c'] ?? 0,
        'experience' => DB::row("SELECT COUNT(*) as c FROM experience_entries WHERE deleted_at IS NULL")['c'] ?? 0,
        'ventures'   => DB::row("SELECT COUNT(*) as c FROM ventures_brands WHERE deleted_at IS NULL")['c'] ?? 0,
        'education'  => DB::row("SELECT COUNT(*) as c FROM education_entries WHERE deleted_at IS NULL")['c'] ?? 0,
        'skills'     => DB::row("SELECT COUNT(*) as c FROM skills_items WHERE deleted_at IS NULL")['c'] ?? 0,
    ];

    // Trash stats
    $trash = [
        'portfolio'  => DB::row("SELECT COUNT(*) as c FROM portfolio_projects WHERE deleted_at IS NOT NULL")['c'] ?? 0,
        'experience' => DB::row("SELECT COUNT(*) as c FROM experience_entries WHERE deleted_at IS NOT NULL")['c'] ?? 0,
        'ventures'   => DB::row("SELECT COUNT(*) as c FROM ventures_brands WHERE deleted_at IS NOT NULL")['c'] ?? 0,
        'education'  => DB::row("SELECT COUNT(*) as c FROM education_entries WHERE deleted_at IS NOT NULL")['c'] ?? 0,
        'skills'     => DB::row("SELECT COUNT(*) as c FROM skills_items WHERE deleted_at IS NOT NULL")['c'] ?? 0,
    ];

    // Message stats
    $messages = [
        'total'  => DB::row("SELECT COUNT(*) as c FROM messages")['c'] ?? 0,
        'unread' => DB::row("SELECT COUNT(*) as c FROM messages WHERE is_read = 0")['c'] ?? 0,
    ];

    echo json_encode([
        'success'  => true,
        'stats'    => $stats,
        'trash'    => $trash,
        'messages' => $messages,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
