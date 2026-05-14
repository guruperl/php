<?php

declare(strict_types=1);

namespace Genelet;

class RequestInput
{
    public $server;
    public $request;
    public $cookie;
    public $headers;
    public $body;

    public function __construct(array $server, array $request, array $cookie, array $headers = array(), string $body = null)
    {
        $this->server = $server;
        $this->request = $request;
        $this->cookie = $cookie;
        $this->headers = self::normalizeHeaders($headers, $server);
        $this->body = $body;
    }

    public static function fromGlobals(string $body = null): RequestInput
    {
        return new RequestInput(
            $_SERVER,
            $_REQUEST,
            $_COOKIE,
            AuthRequestHelper::requestHeaders(),
            $body
        );
    }

    public static function normalizeHeaders(array $headers, array $server = array()): array
    {
        $out = array();
        foreach ($headers as $k => $v) {
            $out[self::normalizeHeaderName((string) $k)] = $v;
        }
        foreach ($server as $k => $v) {
            if (strpos($k, "HTTP_") === 0) {
                $name = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($k, 5)))));
                $out[$name] = $v;
            } elseif ($k == "CONTENT_TYPE") {
                $out["Content-Type"] = $v;
            } elseif ($k == "CONTENT_LENGTH") {
                $out["Content-Length"] = $v;
            }
        }
        return $out;
    }

    private static function normalizeHeaderName(string $name): string
    {
        return str_replace(" ", "-", ucwords(strtolower(str_replace(array("_", "-"), " ", $name))));
    }

    public function header(string $name): ?string
    {
        $key = self::normalizeHeaderName($name);
        return isset($this->headers[$key]) ? trim((string) $this->headers[$key]) : null;
    }

    public function requestMethod(): string
    {
        return isset($this->server["REQUEST_METHOD"]) ? $this->server["REQUEST_METHOD"] : "";
    }

    public function requestUri(): string
    {
        return isset($this->server["REQUEST_URI"]) ? $this->server["REQUEST_URI"] : "";
    }

    public function clientIp(): string
    {
        return AuthRequestHelper::clientIp($this->server);
    }

    public function userAgent(): string
    {
        return isset($this->server["HTTP_USER_AGENT"]) ? $this->server["HTTP_USER_AGENT"] : "";
    }

    public function bearerToken(string $cookieName = null): ?string
    {
        return AuthRequestHelper::bearerToken($this->server, $this->cookie, $this->headers, $cookieName);
    }

    public function basicAuth(string $userKey, string $passwordKey): array
    {
        return AuthRequestHelper::basicCredentials($this->server, $this->request, $userKey, $passwordKey);
    }
}
