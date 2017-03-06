<?php

require_once __DIR__.'/cache.php';
$cache = new cache();
$cache->clearCache();
header('Location: '.$_SERVER['HTTP_REFERER']);
