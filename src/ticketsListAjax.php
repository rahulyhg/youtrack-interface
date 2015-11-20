<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;

$project = htmlspecialchars($authenticationAndSecurity->getGet("project"));



echo json_encode($response);