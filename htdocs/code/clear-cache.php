<?php


require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\cache;

$cache = new cache();
$cache->clearCache();
header('Location: '.$_SERVER['HTTP_REFERER']);
