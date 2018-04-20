<?php

require __DIR__."/config.php";
require __DIR__."/vendor/autoload.php";

use Facebook\Run\FansPage;

$fp = "ThePandaSpot";

FansPage::run($fp);

class x {
  public function _call($a, $b) { echo 123; }
}

$controller = "x";
$method = "aaa";

if (class_exists($controller)) {
 $controller = new x();
 var_dump($controller);
}

if (is_callable([$controller, $method]))  {
 $controller->{$method}();
 var_dump($controller);
}