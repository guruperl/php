<?php

declare(strict_types=1);

namespace Genelet\Tests;

use PHPUnit\Framework\TestCase;
use Genelet\Cache;

final class CacheTest extends TestCase
{
    private function cacheConfig(string $cachetop): object
    {
        $config = json_decode(file_get_contents("conf/test.conf"));
        $config->Cachetop = $cachetop;
        return $config;
    }

    private function removeDir(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        foreach (glob($dir . "/*") ?: array() as $path) {
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testHasMissingFileReturnsFalseWithoutWarning(): void
    {
        $dir = sys_get_temp_dir() . "/genelet-cache-test-" . uniqid();
        $_REQUEST["m_id"] = "1";
        $_SERVER["REQUEST_TIME"] = 200;
        $cache = new Cache($this->cacheConfig($dir), "m", "json", "topics", "t", 2, 10);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $this->assertFalse($cache->has("missing"));
            $this->assertTrue($cache->delete("missing"));
        } finally {
            restore_error_handler();
            $this->removeDir($dir);
            unset($_REQUEST["m_id"]);
        }
    }

    public function testDeleteRemovesExpiredFile(): void
    {
        $dir = sys_get_temp_dir() . "/genelet-cache-test-" . uniqid();
        $_REQUEST["m_id"] = "1";
        $_SERVER["REQUEST_TIME"] = 200;
        $cache = new Cache($this->cacheConfig($dir), "m", "json", "topics", "t", 2, 10);
        $path = $dir . "/m/1/topics_expired.json";

        try {
            $this->assertNotFalse($cache->set("expired", "cached"));
            touch($path, 100);

            $this->assertFalse($cache->has("expired"));
            $this->assertFileExists($path);
            $this->assertTrue($cache->delete("expired"));
            $this->assertFileDoesNotExist($path);
        } finally {
            $this->removeDir($dir);
            unset($_REQUEST["m_id"]);
        }
    }
}
