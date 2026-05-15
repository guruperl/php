<?php
declare (strict_types = 1);

namespace TavolaSample;

class Beacon extends \Genelet\Beacon
{
	public function __construct(string $role) {
		$ip  = "192.168.1.2";
		$tag = "json";
		$headers = ['Content-Type'=>"application/x-www-form-urlencoded",
				'Cookie' => array("go_probe"=>"/")];
		$c = Application::config();
		$pdo = Application::pdo($c);
		[$jsons, $storage] = Application::components($pdo);
		parent::__construct($c, $pdo, $jsons, $storage, Application::logger($c), $role, $tag, $ip, $headers);
	}
}
