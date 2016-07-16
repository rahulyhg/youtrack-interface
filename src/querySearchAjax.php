<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$project = htmlspecialchars($authenticationAndSecurity->getPost("project"));
$query = htmlspecialchars($authenticationAndSecurity->getPost("query"));

echo json_encode($getDataFromYoutrack->getTicketsFromSearch($project,$query));