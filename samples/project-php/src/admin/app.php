<?php
declare (strict_types = 1);
namespace Tabilet\Admin;

ob_start();

$beacon = new Beacon("a");
$response = $beacon->LOGIN(["login"=>"admin", "passwd"=>"KZ2k8M]B", "provider"=>"db", "go_uri"=>"/"]);
if ($response->code !== 200) {
	exit($response->code);
}

$response = $beacon->GET("action=topics");
echo $response->report();
