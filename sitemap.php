<?php
/**
 * sitemap.php — Dynamic XML Sitemap Generator
 * Accessible at: /sitemap.xml (via .htaccess rewrite)
 */

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'alfred.chrysalisdigitals.com';
$base_url  = $protocol . '://' . $host;

$pages = [
    [
        'loc'        => $base_url . '/',
        'lastmod'    => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority'   => '1.0',
    ],
    [
        'loc'        => $base_url . '/#about',
        'lastmod'    => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority'   => '0.8',
    ],
    [
        'loc'        => $base_url . '/#portfolio',
        'lastmod'    => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority'   => '0.9',
    ],
    [
        'loc'        => $base_url . '/#contact',
        'lastmod'    => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority'   => '0.7',
    ],
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($page['loc']) . "</loc>\n";
    echo "    <lastmod>{$page['lastmod']}</lastmod>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
