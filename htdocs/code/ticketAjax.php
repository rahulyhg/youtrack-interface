<?php
/**
 *  time tracker ticket details ajax code
 */
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$response = [];

$ticket = htmlspecialchars($authenticationAndSecurity->getGet("ticket"));

$response['ticketRef'] = $ticket;
$response['summary'] = $getDataFromYoutrack->getTicketSummary($ticket);
$response['ticketUrl'] = $GLOBALS['youtrackUrl']."/issue/".$ticket;

$project = explode('-',$ticket)[0] ;
$response['workTypes'] = $getDataFromYoutrack->getTicketWorkTypes($project);

echo json_encode($response);