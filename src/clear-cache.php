<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
use cache;

$cache = new cache();
$cache->clearCache();
header('Location: '.$_SERVER['HTTP_REFERER']);
