<?php


require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\timeJsonGetAjax;

$timeJsonGetAjax = new timeJsonGetAjax();
echo $timeJsonGetAjax->getJson();
