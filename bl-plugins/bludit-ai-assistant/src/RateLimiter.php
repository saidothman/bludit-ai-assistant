<?php

/**
 * RateLimiter
 *
 * Simple file-based rate limiter. No Redis or database required.
 * Stores request timestamps in a JSON file per IP.
 *
 * Limits: 10 requests per IP per 60 seconds.
 */
class RateLimiter
{
    private const MAX_REQUESTS = 10;
    private const WINDOW_SEC   = 60;

    private string $storageDir;

    public function __construct(string $storageDir = '')
    {
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/ai_assistant_ratelimit';
        if (! is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0700, true);
        }
    }

    /**
     * Returns true if the IP is allowed to make a request.
     * Side effect: records the current request timestamp.
     */
    public function allow(string $ip): bool
    {
        $file = $this->storageDir . '/' . md5($ip) . '.json';
        $now  = time();

        $timestamps = [];
        if (file_exists($file)) {
            $timestamps = json_decode(file_get_contents($file), true) ?? [];
        }

        // Remove timestamps outside the window
        $timestamps = array_filter($timestamps, fn($t) => ($now - $t) < self::WINDOW_SEC);
        $timestamps = array_values($timestamps);

        if (count($timestamps) >= self::MAX_REQUESTS) {
            return false;
        }

        $timestamps[] = $now;
        file_put_contents($file, json_encode($timestamps), LOCK_EX);

        return true;
    }
}
