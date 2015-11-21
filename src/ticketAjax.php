<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$ticket = htmlspecialchars($authenticationAndSecurity->getGet("ticket"));

$response = $getDataFromYoutrack->getTicket($ticket);

echo json_encode($response);