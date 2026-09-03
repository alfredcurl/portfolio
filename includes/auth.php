<?php
// =============================================
// Auth — MySQL-backed admin authentication
// =============================================

require_once ROOT_PATH . '/includes/config.php';

class Auth
{

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['cms_admin']) && $_SESSION['cms_admin'] === true;
    }

    public static function login(string $username, string $password): bool
    {
        // First try MySQL
        try {
            $row = DB::row(
                "SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1",
                [trim($username)]
            );
            if ($row && password_verify($password, $row['password_hash'])) {
                $_SESSION['cms_admin']   = true;
                $_SESSION['cms_user']    = $row['username'];
                $_SESSION['cms_user_id'] = $row['id'];
                // Update last_login
                DB::query("UPDATE admin_users SET last_login = NOW() WHERE id = ?", [$row['id']]);
                return true;
            }
        } catch (\Exception $e) {
            // DB not ready — fall back to hardcoded defaults
            if ($username === 'alfred' && $password === 'alfred2024') {
                $_SESSION['cms_admin'] = true;
                $_SESSION['cms_user']  = 'alfred';
                return true;
            }
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION['cms_admin'] = false;
        unset($_SESSION['cms_admin'], $_SESSION['cms_user'], $_SESSION['cms_user_id']);
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php');
            $project_base = dirname(dirname($script_path));
            $project_base = $project_base === '/' || $project_base === '.' ? '' : rtrim($project_base, '/');
            header('Location: ' . $project_base . '/admin/index.php');
            exit;
        }
    }

    public static function changePassword(string $new_password): bool
    {
        if (!isset($_SESSION['cms_user_id'])) return false;
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            DB::query(
                "UPDATE admin_users SET password_hash = ? WHERE id = ?",
                [$hash, $_SESSION['cms_user_id']]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getCurrentUser(): string
    {
        return $_SESSION['cms_user'] ?? 'alfred';
    }
}
