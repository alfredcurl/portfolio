<?php
// =============================================
// Performance Optimization Helpers
// Output buffering, HTTP caching headers
// =============================================

class Performance
{
    private static $start_time = 0;
    
    /**
     * Start output buffering and timer
     */
    public static function start(): void
    {
        self::$start_time = microtime(true);
        ob_start();
        
        // Enable output compression
        if (!ob_get_level()) {
            ini_set('output_buffering', '4096');
        }
    }

    /**
     * Set cache headers for static assets
     */
    public static function cacheHeaders(string $type = 'static', int $seconds = 2592000): void
    {
        if ($type === 'dynamic') {
            // For HTML: cache only in browser, not CDN
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
        } else {
            // For CSS, JS, images: cache for 30 days
            header('Cache-Control: public, max-age=' . $seconds);
            header('Pragma: cache');
        }
        
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }

    /**
     * Get page generation time (for debugging)
     */
    public static function getGenerationTime(): float
    {
        if (self::$start_time === 0) return 0;
        return microtime(true) - self::$start_time;
    }

    /**
     * Output timing comment (visible in HTML source)
     */
    public static function commentTime(): void
    {
        $time = self::getGenerationTime();
        echo "<!-- Page generated in " . round($time * 1000, 2) . "ms -->\n";
    }

    /**
     * Flush and finish output buffering
     */
    public static function finish(): void
    {
        self::commentTime();
        ob_end_flush();
    }
}
