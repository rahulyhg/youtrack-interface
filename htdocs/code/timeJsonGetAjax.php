<?php


require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\getDataFromYoutrack;

$timeJsonGetAjax = new timeJsonGetAjax();
echo $timeJsonGetAjax->getJson();
