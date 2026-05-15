<?php

declare(strict_types=1);

namespace Genelet;

class Controller extends Config
{
    public $pdo;
    public $components;
    public $Storage;
    public $logger;
    public function __construct(object $c, \PDO $pdo, array $components, array $storage, Logger $log)
    {
        parent::__construct($c);
        $this->pdo = $pdo;
        $this->components = $components;
        $this->Storage = $storage;
        $this->logger = $log;
    }

    public function Run(): Response
    {
        // self::cross_domain();
        $input = RequestInput::fromGlobals();
        if ($input->requestMethod() == "OPTIONS") {
            return new Response(200);
        }
        $logger = $this->logger;
        $logger->screen_start($input->requestMethod(), $input->requestUri(), $input->clientIp(), $input->userAgent());
        if (empty($this->default_actions[$input->requestMethod()])) {
            $logger->info("request method not defined.");
            return new Response(405);
        }
        $request = $input->request;
        foreach ($request as $k => $v) {
            if ($v == "") {
                unset($request[$k]);
            }
        }
        $input = new RequestInput($input->server, $request, $input->cookie, $input->headers, $input->body);

        $context = RequestContext::fromInput($this, $input);
        $_SERVER["REQUEST_METHOD"] = $context->request_method;
        $_REQUEST = $context->request;
        if (isset($context->error)) {
            $logger->info($context->error);
            return new Response($context->error->error_code);
        }
        $cache_type = $context->cache_type;
        $role_name = $context->role;
        $tag_name = $context->tag;
        $comp_name = $context->component;
        $action = $context->action;
        $url_key = $context->url_key;
        $logger->info("role=>" . $role_name);
        $logger->info("tag=>" . $tag_name);
        $logger->info("comp=>" . $comp_name);
        $logger->info("action=>" . $action);
        if (empty($this->chartags[$tag_name])) {
            $logger->info("tag not found.");
            return new Response(404);
        }
        $tag_obj = $this->chartags[$tag_name];
        $is_json = $this->Is_json_tag($tag_name);
        $response = new Response(200, $role_name, $tag_name, $is_json, $comp_name, $action, $url_key);
        $c = $this->original;

        if ($role_name != $this->pubrole) {
            $logger->info("login role.");
            if (empty($this->roles[$role_name])) {
                $logger->info("role not found.");
                return new Response(404);
            }
            $role_obj = $this->roles[$role_name];

            if ($this->Is_logout($comp_name)) {
                $logger->info("logout.");
                $base = new Base($c, $role_name, $tag_name);
                $logout = $base->Handler_logout();
                if ($is_json) {
                    return $response->with_results(["success" => true]);
                }
                return $response->with_redirect($logout);
            } elseif ($this->Is_login($comp_name) || $this->Is_oauth2($comp_name)) {
                $logger->info("login using " . $this->provider_name);
                return $this->login_or_as($c, $role_name, $tag_name, $comp_name, $is_json, $response);
            }
        }
        if (
            empty($this->components[$comp_name]) ||
            empty($this->components[$comp_name]->{"actions"}) ||
            empty($this->components[$comp_name]->{"actions"}->{$action})
        ) {
            $logger->info("component or action not found.");
            return new Response(404);
        }

        $filter_name = ($this->project == "Genelet")
            ? '\\Genelet\\Filter'
            : '\\' . $this->project . '\\' . ucfirst($comp_name) . '\\Filter';
        $filter = new $filter_name($this->components[$comp_name], $action, $comp_name, $c, $role_name, $tag_name);

        if (!empty($url_key) && $cache_type == 0) { // GET request with 4 in url
            $logger->info("ID from URL.");
            $_REQUEST[$filter->current_key] = $url_key;
        }
        // $OLD = $_REQUEST;

        if (!$filter->Is_public()) {
            $logger->info("check authentication for not public role.");
            $surface = $filter->roles[$role_name]->surface;
            if (empty($_REQUEST[$surface])) {
                $err = $filter->Verify_cookie();
            } else {
                $err = $filter->Verify_cookie($_REQUEST[$surface]);
                unset($_REQUEST[$surface]);
            }
            if ($err != null) {
                $logger->info("ticket check failed.");
                if ($is_json) {
                    header("Content-Type: application/json");
					$def_provider = $filter->Get_provider();
					if ($this->Is_oauth2($def_provider)) {
						$t = new Oauth2(new Dbi($this->pdo, $this->logger), null, $c, $role_name, $tag_name, $def_provider);
						$t->App_authorize();
						header('WWW-Authenticate: Bearer realm="'.urlencode($t->Uri).'", , charset="UTF-8"');
							header("Tavola-Error: " . $def_provider);
							header("Tavola-Error-Description: " . $t->Uri);
					} else {
						header('WWW-Authenticate: Bearer realm="' . $filter->script . "/" . $filter->Role_name . "/" . $filter->Tag_name . "/" . $filter->login_name . '", charset="UTF-8"');
							header("Tavola-Error: " . $err->error_code);
							header("Tavola-Error-Description: " . $err->error_string);
					}
                    $response->code = 401;
                    return $response->with_error($err);
                }
                return $response->with_redirect($filter->Forbid());
            } else {
                $logger->info("ticket check successful.");
                foreach ($filter->Decoded as $k => $v) {
                    $_REQUEST[$k] = $v;
                }
            }
            $logger->info("put decoded into the request object.");
        }

        if (!$filter->Role_can()) {
            $logger->info("acl failed.");
            return new Response(403);
        }

        $ttl = isset($filter->actionHash["ttl"]) ? $filter->actionHash["ttl"] : $this->ttl;
        if ($cache_type > 0) {
            $cache = new Cache($c, $role_name, $tag_name, $action, $comp_name, $cache_type, $ttl);
            $response->cache = $cache;
            $logger->info("caching needed.");
            if ($cache->has($url_key)) {
                return $response->with_cached();
            } elseif ($cache_type == 1) { // GET id request
                $_REQUEST[$filter->current_key] = $url_key;
            } elseif ($cache_type == 2 && !empty($url_key)) {
                $queries = unserialize(base64_decode(str_replace(['-', '_'], ['+', '/'], $url_key)));
                foreach ($queries as $k => $v) {
                    $_REQUEST[$k] = $v;
                }
            }
        }

        $err = $filter->Preset();
        $logger->info("preset completed.");
        if ($err != null) {
            return $response->with_error($err);
        }

        $model = $this->Storage[$comp_name];
        $lists = array();
        $other = array();
        if (isset($filter->actionHash[$model->EMPTIES])) {
            $_REQUEST[$model->EMPTIES] = $filter->actionHash[$model->EMPTIES];
        }
        $model->Set_defaults($_REQUEST, $lists, $other, $this->Storage, $logger, $role_name, $tag_name);

        $extra = array();
        $nextextra = array();
        $onceextra = array();
        $err = $filter->Before($model, $extra, $nextextra, $onceextra);
        $logger->info("before completed.");
        if ($err != null) {
            return $response->with_error($err);
        }

        if (empty($filter->actionHash["options"]) || array_search("no_method", $filter->actionHash["options"]) === false) {
            $action = $filter->Action;
            $logger->info("start model action: " . $action);
            $err = $model->$action($extra, ...$nextextra);
            if ($err != null) {
                return $response->with_error($err);
            }
        }
        $logger->info("action $action completed.");

        $err = $filter->After($model, $onceextra);
        $logger->info("after completed.");
        if ($err != null) {
            return $response->with_error($err);
        }

        if ($this->Is_loginas($action) && !empty($model->OTHER[$action])) {
            $logger->info("login_as starts...");
            return $this->login_or_as($c, $role_name, $tag_name, $comp_name, $is_json, $response, $model->OTHER[$action]);
        }

        $logger->info("end page, and sending to browser.");
        return $response->with_results(["success" => true, "incoming" => $_REQUEST, "data" => $model->LISTS, "included" => $model->OTHER]);
    }

    private static function cross_domain(): void
    {
        foreach ($_SERVER as $name => $value) {
            if ($name == "ORIGIN") {
                header("Access-Control-Allow-Origin: $value");
                header("Access-Control-Max-Age: 1728000");
                header("Access-Control-Allow-Credentials: true");
            } elseif ($name == "Access-Control-Request-Method") {
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
            } elseif ($name == "Access-Control-Request-Headers") {
                header("Access-Control-Allow-Headers: $value");
            }
        }
    }

    private function get_ticket($c, $role_name, $tag_name, $comp_name): Ticket
    {
        $dbi = new Dbi($this->pdo, $this->logger);
        if ($this->Is_oauth2($comp_name)) {
            return new Oauth2($dbi, null, $c, $role_name, $tag_name, $comp_name);
        } elseif (isset($_REQUEST[$this->provider_name])) {
            return new Procedure($dbi, null, $c, $role_name, $tag_name, $_REQUEST[$this->provider_name]);
        }
        return new Procedure($dbi, null, $c, $role_name, $tag_name);
        /*
        return $this->Is_oauth2($comp_name)
        ? new Oauth2($dbi, null, $c, $role_name, $tag_name, $comp_name)
        : isset($_REQUEST[$this->provider_name])
        ? new Procedure($dbi, null, $c, $role_name, $tag_name, $_REQUEST[$this->provider_name])
        : new Procedure($dbi, null, $c, $role_name, $tag_name);
*/
    }

    private function login_or_as($c, $role_name, $tag_name, $comp_name, $is_json, $response, $as = null)
    {
        $ticket = null;
        $err = null;
        if (isset($as)) {
            $ticket = $this->get_ticket($c, $as["Role"], $tag_name, $comp_name);
            $ticket->Uri = $as["Uri"];
            $ticket->Provider = $as["Provider"];
            $err = $ticket->Handler_as($as["Login"]);
        } else {
            $ticket = $this->get_ticket($c, $role_name, $tag_name, $comp_name);
            $err = ($is_json) ? $ticket->Basic() : $ticket->Handler();
        }
        $this->logger->info("ticket returns:");
        if ($err !== null && $err->error_code == 303) {
            $this->logger->info($err);
            return $response->with_redirect($ticket->Uri);
        } elseif ($err !== null) {
            $this->logger->info($err);
            if ($is_json) {
                header("Tavola-Error: " . $err->error_code);
                header("Tavola-Error-Description: " . $err->error_string);
                $response->code = 400;
                return $response->with_error($err);
            }
            return $response->with_login($err);
        }

        $signed = (!empty($as) && isset($as['Extra'])) ?
            $ticket->Signature($ticket->Get_fields($as['Extra'])) :
            $ticket->Signature($ticket->Get_fields());
        $role = $ticket->roles[$ticket->Role_name];
        if ($ticket->IsBasic() == false) {
            $this->logger->info("set up cookie.");
            $ticket->Set_cookie($role->surface, $signed);
            $ticket->Set_cookie_session($role->surface . "_", $signed);
        }
        if ($is_json) {
            return $response->with_results(["token_type" => "bearer", "access_token" => $signed, "expires_in" => $role->duration]);
        }
        $this->logger->info("redirect: " . $ticket->Uri);
        return $response->with_redirect($ticket->Uri);
    }
}
