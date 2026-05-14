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

    public function __construct(int $cache_type, string $role, string $tag, string $component, string $action, string $url_key, Gerror $error = null)
    {
        $this->cache_type = $cache_type;
        $this->role = $role;
        $this->tag = $tag;
        $this->component = $component;
        $this->action = $action;
        $this->url_key = $url_key;
        $this->error = $error;
    }

    public static function fromGlobals(Config $config): RequestContext
    {
        $length = strlen($config->script);
        $url_obj = parse_url($_SERVER["REQUEST_URI"]);
        $path = $url_obj["path"];
        $l_url = strlen($path);
        if ($l_url <= $length || substr($path, 0, $length + 1) !== $config->script . "/") {
            return self::failed(new Gerror(400));
        }

        $cache_type = 0;
        $url_key = "";

        $rest = substr($path, $length + 1);
        $path_info = explode("/", $rest);
        if (sizeof($path_info) == 4 && $_SERVER["REQUEST_METHOD"] == "GET") {
            $url_key = array_pop($path_info);
            $_SERVER["REQUEST_METHOD"] = "GET_item";
        } elseif (sizeof($path_info) != 3) {
            return self::failed(new Gerror(400));
        }

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
            return new RequestContext($cache_type, $role_name, $tag_name, $comp_name, $action, $url_key);
        }

        $role_name = $path_info[0];
        $tag_name = $path_info[1];
        $comp_name = $path_info[2];
        if (self::isJsonTag($config, $tag_name) && $_SERVER["REQUEST_METHOD"] == "POST") {
            self::bodyJson();
        }
        $action_name = $config->Get_action_name();
        $action = isset($_REQUEST[$action_name])
            ? $_REQUEST[$action_name]
            : $config->default_actions[$_SERVER["REQUEST_METHOD"]];
        return new RequestContext($cache_type, $role_name, $tag_name, $comp_name, $action, $url_key);
    }

    private static function failed(Gerror $error): RequestContext
    {
        return new RequestContext(0, "", "", "", "", "", $error);
    }

    private static function isJsonTag(Config $config, string $tag): bool
    {
        return !empty($config->chartags[$tag]) && $config->chartags[$tag]->case == 1;
    }

    private static function bodyJson(string $content = null): void
    {
        $json_found = false;
        $header_found = false;
        $items = array();
        if (function_exists('apache_request_headers')) {
            $hs = apache_request_headers();
            if (isset($hs["Content-Type"])) {
                array_push($items, $hs["Content-Type"]);
            }
        }
        if (isset($_SERVER["CONTENT_TYPE"])) {
            array_push($items, $_SERVER["CONTENT_TYPE"]);
        }
        if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            array_push($items, $_SERVER["HTTP_CONTENT_TYPE"]);
        }
        if (!empty($items)) {
            $header_found = true;
            foreach ($items as $item) {
                if ($item == "application/x-www-form-urlencoded" || $item == "multipart/form-data") {
                    return;
                }
                if (strpos($item, 'json') !== false) {
                    $json_found = true;
                    break;
                }
            }
        }
        if ($json_found || $header_found == false) {
            if ($content === null) {
                $content = file_get_contents('php://input');
            }
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (!is_array($decoded)) {
                    return;
                }
                foreach ($decoded as $k => $v) {
                    $_REQUEST[$k] = $v;
                }
            }
        }
    }
}
