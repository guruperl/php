<?php
declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$c = \TavolaSample\Application::config();
$controller = \TavolaSample\Application::controller($c);
$ret = $controller->Run();

echo $ret->report(\TavolaSample\Application::render($ret, $c));

?>
