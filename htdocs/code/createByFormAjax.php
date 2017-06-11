<?php
/**
 * returns field options for createByForm page.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\createByFormAjax;

$createByFormAjax = new createByFormAjax;
$createByFormAjax->execute();
