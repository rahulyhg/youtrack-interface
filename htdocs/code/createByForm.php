<?php


require_once __DIR__ . '/../../vendor/autoload.php';
use Youtrackinterfacer\createByForm;

$createByForm = new createByForm;
$variables = $createByForm->execute();
$projectAssignees = $variables['projectAssignees'];
$customFieldDetails = $variables['customFieldDetails'];
$linkTypes = $variables['linkTypes'];


