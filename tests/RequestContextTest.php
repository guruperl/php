<?php

declare(strict_types=1);

namespace Genelet\Tests;

use PHPUnit\Framework\TestCase;
use Genelet\RequestContext;

final class RequestContextTest extends TestCase
{
    public function testInvalidJsonBodyLeavesRequestUnchanged(): void
    {
        $_SERVER["CONTENT_TYPE"] = "application/json";
        $_REQUEST = array("keep" => "value");

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $method = new \ReflectionMethod(RequestContext::class, "bodyJson");
            $method->setAccessible(true);
            $method->invoke(null, '{"broken"');
        } finally {
            restore_error_handler();
            unset($_SERVER["CONTENT_TYPE"]);
        }

        $this->assertEquals(array("keep" => "value"), $_REQUEST);
    }
}
