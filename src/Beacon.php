<?php

declare(strict_types=1);

namespace Genelet;

class Beacon extends Controller
{
	public $ip;
	public $Role_name;
	public $Tag_name;
	public $headers; // this is for client REQUEST header
	public $redirect;

	public function __construct(object $c, \PDO $pdo, array $components, array $storage, Logger $logger, string $role, string $tag = null, string $ip = null, array $headers = null)
	{
		parent::__construct($c, $pdo, $components, $storage, $logger);
		$this->Role_name = $role;
		if (isset($tag)) {
			$this->Tag_name = $tag;
		} else {
			$this->Tag_name = "json";
		}
		if (isset($ip)) {
			$this->ip = $ip;
		} else {
			$this->ip = "127.0.0.1";
		}
		if (isset($headers)) {
			$this->headers = $headers;
		} else {
			$this->headers = ["Content-Type" => "application/x-www-form-urlencoded", "Cookie" => []];
		}
		if (!isset($this->headers["Cookie"])) {
			$this->headers["Cookie"] = [];
		}
	}

	private function refresh(string $obj, string $query = null): void
	{
		global $_SERVER, $_REQUEST, $_COOKIE;
		$_SERVER = array();
		$_REQUEST = array();
		$_COOKIE = array();

		$_SERVER['HTTP_HOST'] = "localhost";
		$_SERVER['SCRIPT_NAME'] = $this->script;
		$_SERVER['REMOTE_ADDR'] = $this->ip;
		$_SERVER['HTTP_USER_AGENT'] = "ua";
		$_SERVER["REQUEST_URI"] = $this->server_url . $this->script . "/" . $this->Role_name . "/" . $this->Tag_name . "/" . $obj;
		if (isset($query)) {
			$_SERVER["REQUEST_URI"] .= "?" . $query;
			parse_str($query, $_REQUEST);
		}
		$_COOKIE = $this->headers["Cookie"];
	}

	private function update_cookie(): void
	{
		$cookies = headers_list();
		if (empty($cookies) && isset($_COOKIE["SET_COOKIE"])) {
			$this->headers["Cookie"] = $_COOKIE["SET_COOKIE"];
			unset($_COOKIE["SET_COOKIE"]);
		} else {
			foreach ($cookies as $k => $v) {
				if (stripos($v, "Location: ") === 0) {
					$this->redirect = substr($v, 10);
				}
				if (stripos($v, "Set-Cookie: ") === 0) {
					$cookie = substr($v, 12);
					$parts = explode(";", $cookie, 2);
					$item = explode("=", $parts[0], 2);
					if (sizeof($item) === 2) {
						$this->headers["Cookie"][$item[0]] = urldecode($item[1]);
					}
				}
			}
		}
	}

	public function get_mock(string $obj, string $query = null): Response
	{
		$this->refresh($obj, $query);
		$_SERVER["REQUEST_METHOD"] = "GET";
		$_SERVER["REQUEST_TIME"] = time();
		$err = $this->Run();
		$this->update_cookie();
		return $err;
	}

	public function post_mock(string $obj, array $data): Response
	{
		$this->refresh($obj);
		foreach ($data as $k => $v) {
			$_REQUEST[$k] = $v;
		}
		$_SERVER["REQUEST_METHOD"] = "POST";
		$_SERVER["REQUEST_TIME"] = time();
		$err = $this->Run();
		$this->update_cookie();
		return $err;
	}

	public function LOGIN(array $data): Response
	{
		return $this->post_mock($this->login_name, $data);
	}
}
