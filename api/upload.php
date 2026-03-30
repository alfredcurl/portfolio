<?php
// =============================================
// API: Image / File Upload Handler
// POST /api/upload.php
// Requires CMS authentication
// Returns: { success, url, filename, id }
// =============================================

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
session_start();
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List media library ───────────────────────────────
if ($method === 'GET') {
    $section = $_GET['section'] ?? null;
    try {
        if ($section) {
            $rows = DB::all(
                "SELECT * FROM media_library WHERE section = ? ORDER BY uploaded_at DESC",
                [$section]
            );
        } else {
            $rows = DB::all("SELECT * FROM media_library ORDER BY uploaded_at DESC LIMIT 100");
        }
        echo json_encode(['success' => true, 'media' => $rows]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE: Remove a media item ───────────────────────────
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'No id']);
        exit;
    }
    try {
        $row = DB::row("SELECT filename FROM media_library WHERE id = ?", [$id]);
        if ($row) {
            $filepath = UPLOAD_DIR . $row['filename'];
            if (file_exists($filepath)) unlink($filepath);
            DB::query("DELETE FROM media_library WHERE id = ?", [$id]);
        }
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── POST: Upload a file ───────────────────────────────────
if ($method === 'POST') {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $php_errors = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (PHP ini limit)',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file to disk',
        ];
        $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        echo json_encode(['success' => false, 'error' => $php_errors[$code] ?? 'Upload error']);
        exit;
    }

    $file    = $_FILES['file'];
    $section = trim($_POST['section'] ?? 'general');

    // ── Validate MIME type ──────────────────────────────
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    // Use finfo for real MIME detection
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => "File type '$mime_type' not allowed. Use JPEG, PNG, GIF, WebP or SVG."]);
        exit;
    }

    // ── Max size: 8 MB ──────────────────────────────────
    $max_bytes = 8 * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        echo json_encode(['success' => false, 'error' => 'File exceeds 8MB limit.']);
        exit;
    }

    // ── Build unique filename ───────────────────────────
    $ext      = $allowed_types[$mime_type];
    $original = pathinfo($file['name'], PATHINFO_FILENAME);
    $original = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $original);
    $filename = strtolower($original) . '_' . uniqid() . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    // ── Ensure upload dir exists ───────────────────────
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'error' => 'Could not save file. Check uploads/ directory permissions.']);
        exit;
    }

    // ── Get image dimensions if it's a raster image ────
    $width = $height = null;
    if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        $dims   = @getimagesize($dest);
        $width  = $dims[0] ?? null;
        $height = $dims[1] ?? null;
    }

    // ── Save to media_library table ─────────────────────
    $url = UPLOAD_URL . $filename;
    try {
        DB::query(
            "INSERT INTO media_library (filename, original, mime_type, size_bytes, width, height, section, url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$filename, $file['name'], $mime_type, $file['size'], $width, $height, $section, $url]
        );
        $media_id = DB::connect()->lastInsertId();
    } catch (\Exception $e) {
        // If DB is down still return the URL — file was saved
        $media_id = null;
    }

    echo json_encode([
        'success'  => true,
        'url'      => $url,
        'filename' => $filename,
        'id'       => $media_id,
        'width'    => $width,
        'height'   => $height,
        'size'     => $file['size'],
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
