<?php

/**
 * =============================================
 * Database Setup Script
 * Creates all tables for the Alfred Portfolio CMS
 * Database: alfred
 * Run: php setup.php  OR  visit /setup.php in browser
 * =============================================
 */
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/datastore.php';

$errors   = [];
$success  = [];
$warnings = [];

// ── 1. Connect to the existing database ───────────────
try {
    // Try to connect directly to the existing database
    DB::connect();
    $success[] = "Connected to database `" . DB_NAME . "` successfully.";
} catch (\Exception $e) {
    $errors[] = "Could not connect to database `" . DB_NAME . "`: " . $e->getMessage();
    $errors[] = "Make sure you created the database in the InfinityFree Control Panel.";
    goto show_results;
}

// ── 2. Create tables ──────────────────────────────────────
$tables = [

    // CMS section content
    "cms_sections" => "
        CREATE TABLE IF NOT EXISTS `cms_sections` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `section_name` VARCHAR(80)  NOT NULL UNIQUE,
            `content`      LONGTEXT     NOT NULL,
            `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_section` (`section_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Uploaded media files
    "media_library" => "
        CREATE TABLE IF NOT EXISTS `media_library` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename`    VARCHAR(255)  NOT NULL,
            `original`    VARCHAR(255)  NOT NULL,
            `mime_type`   VARCHAR(100)  NOT NULL,
            `size_bytes`  INT UNSIGNED  NOT NULL DEFAULT 0,
            `width`       SMALLINT UNSIGNED NULL,
            `height`      SMALLINT UNSIGNED NULL,
            `section`     VARCHAR(80)   DEFAULT NULL COMMENT 'Which CMS section owns this',
            `url`         VARCHAR(500)  NOT NULL,
            `uploaded_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_section` (`section`),
            INDEX `idx_uploaded` (`uploaded_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Contact form messages
    "messages" => "
        CREATE TABLE IF NOT EXISTS `messages` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(200)  NOT NULL,
            `email`      VARCHAR(200)  NOT NULL,
            `subject`    VARCHAR(500)  NOT NULL,
            `message`    TEXT          NOT NULL,
            `ip`         VARCHAR(50)   DEFAULT NULL,
            `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
            `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_read`    (`is_read`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Admin credentials
    "admin_users" => "
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username`     VARCHAR(80)  NOT NULL UNIQUE,
            `password_hash`VARCHAR(255) NOT NULL,
            `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_login`   DATETIME     NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Specialized tables for CRUD with soft deletes
    "portfolio_projects" => "
        CREATE TABLE IF NOT EXISTS `portfolio_projects` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title`       VARCHAR(255) NOT NULL,
            `category`    VARCHAR(100) NOT NULL,
            `cat_color`   VARCHAR(50)  DEFAULT 'green',
            `description` TEXT,
            `image`       VARCHAR(500) DEFAULT NULL,
            `icon`        VARCHAR(100) DEFAULT 'fas fa-globe',
            `icon_color`  VARCHAR(100) DEFAULT 'text-green-500',
            `bg_gradient` VARCHAR(255) DEFAULT NULL,
            `tags_json`   TEXT         COMMENT 'JSON array of tags',
            `link`        VARCHAR(500) DEFAULT '#',
            `sort_order`  INT NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`  DATETIME NULL DEFAULT NULL,
            INDEX `idx_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "experience_entries" => "
        CREATE TABLE IF NOT EXISTS `experience_entries` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `period`      VARCHAR(100) NOT NULL,
            `title`       VARCHAR(255) NOT NULL,
            `company`     VARCHAR(255) NOT NULL,
            `bullets_json` TEXT        COMMENT 'JSON array of bullets',
            `sort_order`  INT NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`  DATETIME NULL DEFAULT NULL,
            INDEX `idx_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "ventures_brands" => "
        CREATE TABLE IF NOT EXISTS `ventures_brands` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `initial`     VARCHAR(5)   DEFAULT 'N',
            `name`        VARCHAR(255) NOT NULL,
            `role`        VARCHAR(255) NOT NULL,
            `role_color`  VARCHAR(100) DEFAULT 'text-green-500',
            `description` TEXT,
            `bg_color`    VARCHAR(100) DEFAULT 'bg-blue-500/10',
            `logo`        VARCHAR(500) DEFAULT NULL,
            `sort_order`  INT NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`  DATETIME NULL DEFAULT NULL,
            INDEX `idx_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "education_entries" => "
        CREATE TABLE IF NOT EXISTS `education_entries` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `degree`      VARCHAR(255) NOT NULL,
            `institution` VARCHAR(255) NOT NULL,
            `period`      VARCHAR(100) NOT NULL,
            `sort_order`  INT NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`  DATETIME NULL DEFAULT NULL,
            INDEX `idx_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "skills_items" => "
        CREATE TABLE IF NOT EXISTS `skills_items` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type`        ENUM('coding', 'tool') NOT NULL,
            `name`        VARCHAR(100) NOT NULL,
            `percent`     TINYINT UNSIGNED DEFAULT 0 COMMENT 'Only for coding type',
            `icon`        VARCHAR(100) DEFAULT NULL COMMENT 'Only for tool type',
            `color`       VARCHAR(100) DEFAULT NULL COMMENT 'Only for tool type',
            `description` VARCHAR(255) DEFAULT NULL COMMENT 'Only for tool type',
            `sort_order`  INT NOT NULL DEFAULT 0,
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at`  DATETIME NULL DEFAULT NULL,
            INDEX `idx_deleted` (`deleted_at`),
            INDEX `idx_type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
];

foreach ($tables as $name => $sql) {
    try {
        DB::connect()->exec($sql);
        $success[] = "Table `$name` created / verified.";
    } catch (\Exception $e) {
        $errors[] = "Table `$name` failed: " . $e->getMessage();
    }
}

// ── 3. Seed default admin user ────────────────────────────
try {
    $existing = DB::row("SELECT id FROM admin_users WHERE username = 'alfred'");
    if (!$existing) {
        $hash = password_hash('alfred2024', PASSWORD_DEFAULT);
        DB::query(
            "INSERT INTO admin_users (username, password_hash) VALUES (?, ?)",
            ['alfred', $hash]
        );
        $success[] = "Default admin user created: alfred / alfred2024";
    } else {
        $warnings[] = "Admin user 'alfred' already exists — password unchanged.";
    }
} catch (\Exception $e) {
    $errors[] = "Admin user seed failed: " . $e->getMessage();
}

// ── 4. Seed CMS sections with defaults ───────────────────
try {
    DataStore::seedIfEmpty();
    $success[] = "CMS sections seeded with default content.";
} catch (\Exception $e) {
    $errors[] = "CMS seed failed: " . $e->getMessage();
}

// ── 5. Create uploads directory ───────────────────────────
$upload_dir = ROOT_PATH . '/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    $success[] = "Uploads directory created at /uploads/";
} else {
    $success[] = "Uploads directory exists at /uploads/";
}

// Write .htaccess to allow images but block PHP in uploads
$htaccess = <<<'HTACCESS'
# Block PHP execution in uploads
<Files "*.php">
    Order allow,deny
    Deny from all
</Files>
<Files "*.phtml">
    Order allow,deny
    Deny from all
</Files>
Options -Indexes
HTACCESS;
file_put_contents($upload_dir . '/.htaccess', $htaccess);
$success[] = "Uploads .htaccess security created.";

// ── Check PHP version & extensions ────────────────────────
$php_checks = [
    'PHP 7.4+'        => version_compare(PHP_VERSION, '7.4', '>='),
    'PDO extension'   => extension_loaded('pdo'),
    'PDO MySQL'       => extension_loaded('pdo_mysql'),
    'GD (images)'     => extension_loaded('gd'),
    'Fileinfo'        => extension_loaded('fileinfo'),
    'JSON'            => extension_loaded('json'),
    'Session support' => function_exists('session_start'),
];

goto show_results;

show_results:
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Portfolio — Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', monospace;
            background: #0d1117;
            color: #c9d1d9;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        h1 {
            color: #58a6ff;
            border-bottom: 2px solid #30363d;
            padding-bottom: 12px;
        }

        h2 {
            color: #f0f6fc;
            margin-top: 32px;
        }

        .ok {
            background: #238636;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .err {
            background: #da3633;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .warn {
            background: #9e6a03;
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info {
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }

        code {
            background: #161b22;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #161b22;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 12px;
        }

        th,
        td {
            padding: 10px 16px;
            text-align: left;
            border-bottom: 1px solid #30363d;
        }

        th {
            color: #8b949e;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pass {
            color: #3fb950;
            font-weight: bold;
        }

        .fail {
            color: #f85149;
            font-weight: bold;
        }

        a.btn {
            display: inline-block;
            background: #238636;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 8px 6px 0 0;
        }

        a.btn.outline {
            background: transparent;
            border: 1px solid #30363d;
        }
    </style>
</head>

<body>
    <h1>🗄️ Alfred Portfolio — Database Setup</h1>

    <?php if (!empty($errors)): ?>
        <h2>❌ Errors</h2>
        <?php foreach ($errors as $e): ?><div class="err">⛔ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($warnings)): ?>
        <h2>⚠️ Warnings</h2>
        <?php foreach ($warnings as $w): ?><div class="warn">⚠️ <?= htmlspecialchars($w) ?></div><?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <h2>✅ Results</h2>
        <?php foreach ($success as $s): ?><div class="ok">✅ <?= htmlspecialchars($s) ?></div><?php endforeach; ?>
    <?php endif; ?>

    <h2>🔬 PHP Extensions</h2>
    <table>
        <tr>
            <th>Check</th>
            <th>Status</th>
        </tr>
        <?php foreach ($php_checks as $label => $ok): ?>
            <tr>
                <td><?= $label ?></td>
                <td class="<?= $ok ? 'pass' : 'fail' ?>"><?= $ok ? '✅ OK' : '❌ Missing' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="info">
        <h2 style="margin-top:0;color:#58a6ff;">🚀 Quick Start</h2>
        <p><strong>MySQL:</strong> Database <code>alfred</code> | Host: <code><?= DB_HOST ?></code> | User: <code><?= DB_USER ?></code></p>
        <p><strong>Start dev server:</strong></p>
        <code style="display:block;padding:12px;background:#161b22;border-radius:6px;margin:8px 0;">php -S localhost:8080 router.php</code>
        <p><strong>Access:</strong></p>
        <a href="/" class="btn">🌐 View Portfolio</a>
        <a href="/admin" class="btn">⚙️ Open CMS</a>
        <p style="margin-top:16px;color:#8b949e;font-size:13px;">CMS Login: <code>alfred</code> / <code>alfred2024</code></p>
        <?php if (!empty($errors)): ?>
            <p style="color:#f85149;margin-top:12px;">
                ⚠️ Fix the errors above first. Common fix: ensure MySQL is running and
                credentials in <code>includes/config.php</code> are correct.
            </p>
        <?php endif; ?>
    </div>
</body>

</html>