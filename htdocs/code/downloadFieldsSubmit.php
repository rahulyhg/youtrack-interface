<?php
/**
 * download a csv with field options.
 */
namespace  Youtrackinterfacer;
require_once __DIR__ . '/../../vendor/autoload.php';
use downloadFieldsSubmit;

$downloadFieldsSubmit = new downloadFieldsSubmit;
$downloadFieldsSubmit->execute();