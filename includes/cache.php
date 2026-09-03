<?php
// =============================================
// Simple File-Based Caching System
// Reduces database queries from 8+ to 1-2 per page
// =============================================

class Cache
{
    private static $cache_dir = null;
    private static $ttl = 3600; // 1 hour default

    public static function init(?string $cache_dir = null): void
    {
        if ($cache_dir === null) {
            $cache_dir = defined('ROOT_PATH') ? ROOT_PATH . '/.cache' : sys_get_temp_dir() . '/portfolio_cache';
        }
        
        self::$cache_dir = $cache_dir;
        
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
    }

    /**
     * Get cached data
     */
    public static function get(string $key): mixed
    {
        if (self::$cache_dir === null) self::init();
        
        $file = self::$cache_dir . '/' . self::hashKey($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }

        $data = @unserialize(file_get_contents($file));
        if ($data === false) {
            @unlink($file);
            return null;
        }

        // Check expiration
        if ($data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set cache data
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (self::$cache_dir === null) self::init();
        
        $ttl ??= self::$ttl;
        $file = self::$cache_dir . '/' . self::hashKey($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Invalidate cache entry
     */
    public static function delete(string $key): bool
    {
        if (self::$cache_dir === null) self::init();
        
        $file = self::$cache_dir . '/' . self::hashKey($key) . '.cache';
        return @unlink($file);
    }

    /**
     * Clear all cache
     */
    public static function flush(): bool
    {
        if (self::$cache_dir === null) self::init();
        
        $files = @glob(self::$cache_dir . '/*.cache');
        if ($files === false) return false;
        
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    private static function hashKey(string $key): string
    {
        return md5($key);
    }
}
