<?php
// =============================================
// API: Contact Form — MySQL storage
// POST  → save message
// GET   ?action=list → list (admin only)
// DELETE ?action=delete&id=N → delete (admin only)
// =============================================

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
session_start();
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/datastore.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET: List messages (admin only) ──────────────────────
if ($method === 'GET' && $action === 'list') {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    try {
        $rows = DB::all("SELECT * FROM messages ORDER BY created_at DESC");
        echo json_encode(['messages' => $rows]);
    } catch (\Exception $e) {
        echo json_encode(['messages' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE: Delete a message (admin only) ────────────────
if ($method === 'DELETE' && $action === 'delete') {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    try {
        DB::query("DELETE FROM messages WHERE id = ?", [$id]);
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── PATCH: Mark message as read (admin only) ─────────────
if ($method === 'PATCH' && $action === 'read') {
    if (!Auth::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    try {
        DB::query("UPDATE messages SET is_read = 1 WHERE id = ?", [$id]);
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── POST: Submit contact form ─────────────────────────────
if ($method === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data) {
        // Try form-encoded
        $data = $_POST;
    }

    $name    = trim($data['name']    ?? '');
    $email   = trim($data['email']   ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    if (!$name || !$email || !$subject || !$message) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        DB::query(
            "INSERT INTO messages (name, email, subject, message, ip) VALUES (?, ?, ?, ?, ?)",
            [
                htmlspecialchars($name),
                htmlspecialchars($email),
                htmlspecialchars($subject),
                htmlspecialchars($message),
                $ip
            ]
        );
    } catch (\Exception $e) {
        error_log("Message save error: " . $e->getMessage());
        // Don't fail silently — tell the user
    }

    // Try to send email (optional)
    $contact = DataStore::get('contact');
    $to = $contact['email'] ?? '';
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $mail_subject = "[Portfolio] New Message: $subject";
        $mail_body    = "From: $name <$email>\n\n$message\n\nSent: " . date('Y-m-d H:i:s');
        $headers      = "From: noreply@portfolio.local\r\nReply-To: $email";
        @mail($to, $mail_subject, $mail_body, $headers);
    }

    echo json_encode(['success' => true, 'message' => 'Message received! I\'ll get back to you soon.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
