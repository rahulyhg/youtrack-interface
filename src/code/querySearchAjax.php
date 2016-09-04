<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$project = htmlspecialchars($authenticationAndSecurity->getPost("project"));
$query = htmlspecialchars($authenticationAndSecurity->getPost("query"));
$after = htmlspecialchars($authenticationAndSecurity->getPost("after"));
$after = ($after) ? $after : 0;
        
echo json_encode($getDataFromYoutrack->getTicketsFromSearch($project,$query,$maximumReturned=100,$after));