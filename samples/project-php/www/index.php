<?php
declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$c = \TavolaSample\Application::config();
$controller = \TavolaSample\Application::controller();
$ret = $controller->Run();

if ($ret->code == 200) {
	echo $ret->report(\TavolaSample\Application::render($ret, $c));
} else {
	$ret->report(\TavolaSample\Application::render($ret, $c));
}

?>
