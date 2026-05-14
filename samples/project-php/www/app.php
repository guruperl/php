<?php
declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$c = \Tabilet\Application::config();
$controller = \Tabilet\Application::controller($c);
$ret = $controller->Run();

echo $ret->report(\Tabilet\Application::render($ret, $c));

?>
