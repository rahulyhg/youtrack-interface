<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../../vendor/autoload.php';
use createByForm;

$createByForm = new createByForm;
$variables = $createByForm->execute();
$projectAssignees = $variables['projectAssignees'];
$customFieldDetails = $variables['customFieldDetails'];
$linkTypes = $variables['linkTypes'];


