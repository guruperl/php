<?php

declare(strict_types=1);

namespace Genelet\Tests;

use PHPUnit\Framework\TestCase;
use Genelet\Config;
use Genelet\RequestContext;
use Genelet\RequestInput;

final class RequestContextTest extends TestCase
{
    private function config(): Config
    {
        return new Config(json_decode(file_get_contents("conf/test.conf")));
    }

    public function testFromInputParsesActionRequest(): void
    {
        $input = new RequestInput(
            array("REQUEST_URI" => "/bb/m/json/t?action=topics", "REQUEST_METHOD" => "GET", "REMOTE_ADDR" => "1.1.1.1", "HTTP_USER_AGENT" => "ua"),
            array("action" => "topics"),
            array()
        );

        $context = RequestContext::fromInput($this->config(), $input);

        $this->assertNull($context->error);
        $this->assertEquals("m", $context->role);
        $this->assertEquals("json", $context->tag);
        $this->assertEquals("t", $context->component);
        $this->assertEquals("topics", $context->action);
        $this->assertEquals("GET", $context->request_method);
    }

    public function testFromInputParsesGetItemPath(): void
    {
        $input = new RequestInput(
            array("REQUEST_URI" => "/bb/m/json/t/123", "REQUEST_METHOD" => "GET"),
            array(),
            array()
        );

        $context = RequestContext::fromInput($this->config(), $input);

        $this->assertNull($context->error);
        $this->assertEquals("GET_item", $context->request_method);
        $this->assertEquals("edit", $context->action);
        $this->assertEquals("123", $context->url_key);
    }

    public function testFromInputMergesJsonBodyWhenJsonTagPosts(): void
    {
        $input = new RequestInput(
            array("REQUEST_URI" => "/bb/m/json/t", "REQUEST_METHOD" => "POST", "CONTENT_TYPE" => "application/json"),
            array("keep" => "value"),
            array(),
            array(),
            '{"x":"aaa"}'
        );

        $context = RequestContext::fromInput($this->config(), $input);

        $this->assertNull($context->error);
        $this->assertEquals("insert", $context->action);
        $this->assertEquals("value", $context->request["keep"]);
        $this->assertEquals("aaa", $context->request["x"]);
    }

    public function testFromInputDoesNotMergeFormBody(): void
    {
        $input = new RequestInput(
            array("REQUEST_URI" => "/bb/m/json/t", "REQUEST_METHOD" => "POST", "CONTENT_TYPE" => "application/x-www-form-urlencoded"),
            array("keep" => "value"),
            array(),
            array(),
            '{"x":"aaa"}'
        );

        $context = RequestContext::fromInput($this->config(), $input);

        $this->assertNull($context->error);
        $this->assertArrayNotHasKey("x", $context->request);
    }

    public function testRequestInputNormalizesHeadersAndAuthMetadata(): void
    {
        $input = new RequestInput(
            array(
                "HTTP_AUTHORIZATION" => "Bearer server-token",
                "HTTP_X_FORWARDED_FOR" => "2.2.2.2",
                "REMOTE_ADDR" => "1.1.1.1",
                "HTTP_USER_AGENT" => "ua"
            ),
            array("email" => "request-user", "passwd" => "request-pass"),
            array("mc" => "cookie-token"),
            array("content-type" => "application/json")
        );

        $this->assertEquals("application/json", $input->header("Content-Type"));
        $this->assertEquals("server-token", $input->bearerToken("mc"));
        $this->assertEquals("2.2.2.2", $input->clientIp());
        $this->assertEquals("ua", $input->userAgent());
        $this->assertEquals(array("request-user", "request-pass", false), $input->basicAuth("email", "passwd"));
    }

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
