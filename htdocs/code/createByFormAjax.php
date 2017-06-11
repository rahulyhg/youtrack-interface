<?php
/**
 * returns field options for createByForm page.
 */
namespace  Youtrackinterfacer;
require_once __DIR__ . '/../../vendor/autoload.php';
use createByFormAjax;

$createByFormAjax = new createByFormAjax;
$createByFormAjax->execute();
