<?php
/**
 *  return ist of projects for rendering the time tracker page
 */
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/authenticationAndSecurity.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

$getDataFromYoutrack = new getDataFromYoutrack;

$projectList = $getDataFromYoutrack->getProjectsList();
