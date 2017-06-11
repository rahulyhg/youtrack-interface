<?php
/**
 * download a csv with field options.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\downloadFieldsSubmit;

$downloadFieldsSubmit = new downloadFieldsSubmit;
$downloadFieldsSubmit->execute();