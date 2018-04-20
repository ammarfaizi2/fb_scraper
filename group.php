<?php

require __DIR__."/config.php";
require __DIR__."/vendor/autoload.php";

use Facebook\Run\Group;

$fp = $argv[1];

Group::run($fp);
