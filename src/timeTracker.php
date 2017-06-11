<?php
/**
 *  return list of projects for rendering the time tracker page.
 */
namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';

use getCustomSettings;
use authenticationAndSecurity;
use getDataFromYoutrack;

$getDataFromYoutrack = new getDataFromYoutrack();

$projectList = $getDataFromYoutrack->getProjectsList();
