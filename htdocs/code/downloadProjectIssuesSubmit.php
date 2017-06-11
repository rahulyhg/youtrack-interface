<?php
/**
 * download all issues in a project in a csv.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\downloadProjectIssuesSubmit;

$downloadProjectIssuesSubmit = new downloadProjectIssuesSubmit;
$downloadProjectIssuesSubmit->execute();
