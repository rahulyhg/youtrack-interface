<?php
/**
 * csv ticket importer.
 */

require_once __DIR__.'/../../vendor/autoload.php';
use  Youtrackinterfacer\csvUploaderSubmit;

$csvUploaderSubmit = new csvUploaderSubmit;
$csvUploaderSubmit->execute();