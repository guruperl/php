<?php

declare(strict_types=1);

namespace Genelet;

class AuthRequestHelper
{
    public static function requestHeaders(): array
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
        return is_array($headers) ? $headers : array();
    }

    public static function clientIp(array $server): string
    {
        if (isset($server['HTTP_CLIENT_IP'])) {
            return $server['HTTP_CLIENT_IP'];
        }
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }
        return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : "";
    }

    public static function userAgent(array $server): string
    {
        return isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : "";
    }

    public static function bearerToken(array $server, array $cookie, array $headers, string $cookieName = null): ?string
    {
        $header = null;
        if (isset($server['Authorization'])) {
            $header = trim($server["Authorization"]);
        } elseif (isset($server['HTTP_AUTHORIZATION'])) {
            $header = trim($server["HTTP_AUTHORIZATION"]);
        } else {
            $normalized = RequestInput::normalizeHeaders($headers);
            if (isset($normalized['Authorization'])) {
                $header = trim($normalized['Authorization']);
            }
        }
        if ($header != null && substr($header, 0, 7) == 'Bearer ') {
            return substr($header, 7);
        }
        if ($cookieName !== null && !empty($cookie[$cookieName])) {
            return $cookie[$cookieName];
        }
        return null;
    }

    public static function basicCredentials(array $server, array $request, string $userKey, string $passwordKey): array
    {
        $user = isset($request[$userKey]) ? $request[$userKey] : null;
        $password = isset($request[$passwordKey]) ? $request[$passwordKey] : null;
        $isBasic = false;
        if (!empty($server['PHP_AUTH_USER'])) {
            $user = $server['PHP_AUTH_USER'];
            $isBasic = true;
        }
        if (!empty($server['PHP_AUTH_PW'])) {
            $password = $server['PHP_AUTH_PW'];
            $isBasic = true;
        }
        return array($user, $password, $isBasic);
    }

    public static function probeValue(string $name, array $server, array $request, string $input = null): string
    {
        if (isset($request[$name])) {
            return $request[$name];
        }
        $query = parse_url(isset($server["REQUEST_URI"]) ? $server["REQUEST_URI"] : "", PHP_URL_QUERY);
        if ($query !== null) {
            foreach (explode("&", $query) as $item) {
                $len = strlen($name);
                if (substr($item, 0, $len + 1) == $name . "=") {
                    return urldecode(substr($item, $len + 1));
                }
            }
        }
        return isset($input) ? $input : "/";
    }

    public static function callbackAddress(array $server, string $script, string $role, string $tag, string $provider): string
    {
        $http = "http";
        if (isset($server["HTTPS"])) {
            $http .= "s";
        }
        return $http . "://" . $server["HTTP_HOST"] . $script . "/" . $role . "/" . $tag . "/" . $provider;
    }

    public static function setCookie(bool $isPublic, ?object $role, string $name, string $value, int $current, array $server): void
    {
        if ($isPublic) {
            return;
        }
        $domain = empty($role->domain) ? $server["HTTP_HOST"] : $role->domain;
        $_COOKIE["SET_COOKIE"][$name] = $value;
        $exp = ($current > 0) ? $current + $role->duration : $current;
        setcookie($name, $value, $exp, $role->path, $domain);
    }

    public static function expireCookie(bool $isPublic, ?object $role, string $name, array $server): void
    {
        self::setCookie($isPublic, $role, $name, "0", -365 * 24 * 3600, $server);
    }

    public static function mirrorSetCookiesFromHeaders(array $headers, array &$cookies, ?string &$redirect = null): void
    {
        foreach ($headers as $v) {
            if (stripos($v, "Location: ") === 0) {
                $redirect = substr($v, 10);
            }
            if (stripos($v, "Set-Cookie: ") !== 0) {
                continue;
            }
            $cookie = substr($v, 12);
            $parts = explode(";", $cookie, 2);
            $item = explode("=", $parts[0], 2);
            if (sizeof($item) === 2) {
                $cookies[$item[0]] = urldecode($item[1]);
            }
        }
    }
}
