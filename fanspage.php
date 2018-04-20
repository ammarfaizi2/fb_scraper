<?php

require __DIR__."/config.php";
require __DIR__."/vendor/autoload.php";

use Facebook\Run\FansPage;

$fp = "ammarfaizi2";

FansPage::run($fp);
