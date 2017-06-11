<?php


require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * ticket search on time tracker page.
 */
use Youtrackinterfacer\querySearchAjax;

$querySearchAjax = new querySearchAjax;
$querySearchAjax->execute();
