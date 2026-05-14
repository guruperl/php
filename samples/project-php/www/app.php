<?php
declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$c = \Tabilet\Application::config();
$controller = \Tabilet\Application::controller();
$ret = $controller->Run();

if ($ret->code == 200) {
	echo $ret->report(\Tabilet\Application::render($ret, $c));
} else {
	$ret->report(\Tabilet\Application::render($ret, $c));
}

?>
