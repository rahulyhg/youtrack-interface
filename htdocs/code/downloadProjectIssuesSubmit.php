<?php
/**
 * download all issues in a project in a csv.
 */
namespace  Youtrackinterfacer;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . 'getCustomSettings.php';
use downloadProjectIssuesSubmit;

$downloadProjectIssuesSubmit = new downloadProjectIssuesSubmit;
$downloadProjectIssuesSubmit->execute();
