<?php
/**
 *  return list of projects for rendering the time tracker page.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\timeTracker;

$timeTracker = new timeTracker;
$projectList = $timeTracker->getProjectsList();