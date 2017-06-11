<?php
/**
 * csv ticket importer.
 */
namespace  Youtrackinterfacer;
require_once __DIR__.'/../../vendor/autoload.php';
use  csvUploaderSubmit;

$csvUploaderSubmit = new csvUploaderSubmit;
$csvUploaderSubmit->execute();