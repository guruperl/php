<?php

declare(strict_types=1);

namespace Genelet;

class RequestContext
{
    public $cache_type;
    public $role;
    public $tag;
    public $component;
    public $action;
    public $url_key;
    public $error;
    public $request_method;
    public $request;
    public $server;
    public $cookie;
    public $headers;
    public $client_ip;
    public $user_agent;
    public $bearer_token;
    public $basic_auth;

    public function __construct(int $cache_type, string $role, string $tag, string $component, string $action, string $url_key, Gerror $error = null, RequestInput $input = null)
    {
        $this->cache_type = $cache_type;
        $this->role = $role;
        $this->tag = $tag;
        $this->component = $component;
        $this->action = $action;
        $this->url_key = $url_key;
        $this->error = $error;
        if ($input !== null) {
            $this->request_method = $input->requestMethod();
            $this->request = $input->request;
            $this->server = $input->server;
            $this->cookie = $input->cookie;
            $this->headers = $input->headers;
            $this->client_ip = $input->clientIp();
            $this->user_agent = $input->userAgent();
            $this->bearer_token = $input->bearerToken();
            $this->basic_auth = array();
        }
    }

    public static function fromGlobals(Config $config): RequestContext
    {
        $context = self::fromInput($config, RequestInput::fromGlobals());
        if (isset($context->request_method)) {
            $_SERVER["REQUEST_METHOD"] = $context->request_method;
        }
        if (isset($context->request)) {
            $_REQUEST = $context->request;
        }
        return $context;
    }

    public static function fromInput(Config $config, RequestInput $input): RequestContext
    {
        $length = strlen($config->script);
        $url_obj = parse_url($input->requestUri());
        $path = isset($url_obj["path"]) ? $url_obj["path"] : "";
        $l_url = strlen($path);
        if ($l_url <= $length || substr($path, 0, $length + 1) !== $config->script . "/") {
            return self::failed(new Gerror(400), $input);
        }

        $cache_type = 0;
        $url_key = "";
        $server = $input->server;
        $request = $input->request;
        $method = $input->requestMethod();

        $rest = substr($path, $length + 1);
        $path_info = explode("/", $rest);
        if (sizeof($path_info) == 4 && $method == "GET") {
            $url_key = array_pop($path_info);
            $method = "GET_item";
            $server["REQUEST_METHOD"] = $method;
        } elseif (sizeof($path_info) != 3) {
            return self::failed(new Gerror(400), $input);
        }
        $input = new RequestInput($server, $request, $input->cookie, $input->headers, $input->body);

        $arr = explode('.', $path_info[2]);
        if (sizeof($arr) == 2) {
            $role_name = $path_info[0];
            $comp_name = $path_info[1];
            $tag_name = $arr[1];
            $action = $arr[0];
            if (preg_match("/^[0-9]+$/", $arr[0])) {
                $cache_type = 1;
                $action = $config->default_actions["GET_item"];
                $url_key = $arr[0];
            } else {
                $cache_type = 2;
                $patterns = explode('_', $arr[0], 2);
                if (sizeof($patterns) == 2) {
                    $action = $patterns[0];
                    $url_key = $patterns[1];
                }
            }
            return new RequestContext($cache_type, $role_name, $tag_name, $comp_name, $action, $url_key, null, $input);
        }

        $role_name = $path_info[0];
        $tag_name = $path_info[1];
        $comp_name = $path_info[2];
        if (self::isJsonTag($config, $tag_name) && $method == "POST") {
            $request = self::requestWithJsonBody($input);
            $input = new RequestInput($server, $request, $input->cookie, $input->headers, $input->body);
        }
        $action_name = $config->Get_action_name();
        $action = isset($request[$action_name])
            ? $request[$action_name]
            : $config->default_actions[$method];
        return new RequestContext($cache_type, $role_name, $tag_name, $comp_name, $action, $url_key, null, $input);
    }

    private static function failed(Gerror $error, RequestInput $input = null): RequestContext
    {
        return new RequestContext(0, "", "", "", "", "", $error, $input);
    }

    private static function isJsonTag(Config $config, string $tag): bool
    {
        return !empty($config->chartags[$tag]) && $config->chartags[$tag]->case == 1;
    }

    private static function bodyJson(string $content = null): void
    {
        $input = RequestInput::fromGlobals($content);
        $_REQUEST = self::requestWithJsonBody($input);
    }

    private static function requestWithJsonBody(RequestInput $input): array
    {
        $json_found = false;
        $header_found = false;
        $items = array();
        if (isset($input->headers["Content-Type"])) {
            array_push($items, $input->headers["Content-Type"]);
        }
        if (isset($input->server["CONTENT_TYPE"])) {
            array_push($items, $input->server["CONTENT_TYPE"]);
        }
        if (isset($input->server["HTTP_CONTENT_TYPE"])) {
            array_push($items, $input->server["HTTP_CONTENT_TYPE"]);
        }
        if (!empty($items)) {
            $header_found = true;
            foreach ($items as $item) {
                if ($item == "application/x-www-form-urlencoded" || $item == "multipart/form-data") {
                    return $input->request;
                }
                if (strpos($item, 'json') !== false) {
                    $json_found = true;
                    break;
                }
            }
        }
        if (!$json_found && $header_found != false) {
            return $input->request;
        }
        $content = $input->body;
        if ($content === null) {
            $content = file_get_contents('php://input');
        }
        if (empty($content)) {
            return $input->request;
        }
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return $input->request;
        }
        $request = $input->request;
        foreach ($decoded as $k => $v) {
            $request[$k] = $v;
        }
        return $request;
    }
}
