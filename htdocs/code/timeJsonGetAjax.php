<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
use getDataFromYoutrack;

$timeJsonGetAjax = new timeJsonGetAjax();
echo $timeJsonGetAjax->getJson();
