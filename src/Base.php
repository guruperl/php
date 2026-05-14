<?php

declare(strict_types=1);

namespace Genelet;

class Base extends Config
{
    public $Role_name;
    public $Tag_name;
    protected $role_obj;
    protected $tag_obj;

    public function __construct(object $c, string $rv, string $cv)
    {
        parent::__construct($c);
        $this->Role_name = $rv;
        $this->Tag_name = $cv;
        if ($this->pubrole != $rv) {
            $this->role_obj = $this->roles[$rv];
        }
        $this->tag_obj = $this->chartags[$cv];
    }

    public function Get_role()
    {
        return $this->role_obj;
    }

    public function Is_admin(): bool
    {
        if ($this->Is_public() === true) {
            return false;
        }
        return $this->role_obj->is_admin;
    }

    public function Is_public(): bool
    {
        return $this->pubrole == $this->Role_name;
    }

    public function Is_normal_role(): bool
    {
        if ($this->Is_public() === true || $this->Is_admin() === true) {
            return false;
        }
        return !empty($this->role_obj);
    }

    public function Is_json(): bool
    {
        return parent::Is_json_tag($this->Tag_name);
    }

    public function Get_idname(): string
    {
        return $this->role_obj->idname;
    }

    public function Get_provider(): string // get default provider
    {
        $one = "";
        foreach ($this->role_obj->issuers as $key => $val) {
            if ($val->default) {
                return $key;
            }
            if (empty($one)) {
                $one = $key;
            }
        }
        return $one;
    }

    public function Get_ip(): string
    {
        return AuthRequestHelper::clientIp($_SERVER);
    }

    public function Get_ua(): string
    {
        return AuthRequestHelper::userAgent($_SERVER);
    }

    private function _set_cookie(string $name, string $value, int $current): void
    {
        AuthRequestHelper::setCookie($this->Is_public(), $this->role_obj, $name, $value, $current, $_SERVER);
    }

    public function Set_cookie(string $name, string $value): void
    {
        $this->_set_cookie($name, $value, intval($_SERVER["REQUEST_TIME"]));
    }

    public function Set_cookie_session(string $name, string $value): void
    {
        $this->_set_cookie($name, $value, 0);
    }

    public function Set_cookie_expire(string $name): void
    {
        $this->_set_cookie($name, "0", -365 * 24 * 3600);
    }

    public function Handler_logout(): string
    {
        $role = $this->role_obj;
        AuthRequestHelper::expireCookie($this->Is_public(), $role, $role->surface, $_SERVER);
        AuthRequestHelper::expireCookie($this->Is_public(), $role, $role->surface . "_", $_SERVER);
        AuthRequestHelper::expireCookie($this->Is_public(), $role, $this->go_probe_name, $_SERVER);
        return $role->logout;
    }

    static public function base64_encode_url(string $string): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    static public function base64_decode_url(string $string): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }
}
