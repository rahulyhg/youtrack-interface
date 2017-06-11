<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . 'getCustomSettings.php';
use youtrackLogin;

$youtrackLogin = new youtrackLogin;
$youtrackLogin->execute();

