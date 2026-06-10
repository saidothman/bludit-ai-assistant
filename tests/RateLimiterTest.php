<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/RateLimiter.php';

class RateLimiterTest extends TestCase
{
    private string $tmpDir;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/ai_test_' . uniqid();
        $this->limiter = new RateLimiter($this->tmpDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        array_map('unlink', glob($this->tmpDir . '/*.json') ?: []);
        @rmdir($this->tmpDir);
    }

    public function test_first_request_is_allowed(): void
    {
        $this->assertTrue($this->limiter->allow('192.168.1.1'));
    }

    public function test_multiple_requests_within_limit_are_allowed(): void
    {
        $ip = '10.0.0.1';
        for ($i = 0; $i < 9; $i++) {
            $this->assertTrue($this->limiter->allow($ip), "Request #{$i} should be allowed");
        }
    }

    public function test_request_beyond_limit_is_blocked(): void
    {
        $ip = '10.0.0.2';
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->allow($ip);
        }

        $this->assertFalse($this->limiter->allow($ip), '11th request should be blocked');
    }

    public function test_different_ips_are_tracked_independently(): void
    {
        $ip1 = '1.1.1.1';
        $ip2 = '2.2.2.2';

        for ($i = 0; $i < 10; $i++) {
            $this->limiter->allow($ip1);
        }

        // ip1 is maxed out — ip2 should still be allowed
        $this->assertFalse($this->limiter->allow($ip1));
        $this->assertTrue($this->limiter->allow($ip2));
    }

    public function test_storage_directory_is_created_if_missing(): void
    {
        $newDir = sys_get_temp_dir() . '/ai_new_dir_' . uniqid();
        $this->assertDirectoryDoesNotExist($newDir);

        new RateLimiter($newDir);

        $this->assertDirectoryExists($newDir);
        @rmdir($newDir);
    }
}
