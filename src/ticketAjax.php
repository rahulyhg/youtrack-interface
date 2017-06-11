<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
use getDataFromYoutrack;

class ticketAjax{
    function execute()
    {
        /**
         *  time tracker ticket details ajax code.
         */
        $getDataFromYoutrack = new getDataFromYoutrack();
        $authenticationAndSecurity = new authenticationAndSecurity();

        $response = [];

        $ticket = htmlspecialchars($authenticationAndSecurity->getGet('ticket'));

        $response['ticketRef'] = $ticket;
        $response['summary'] = $getDataFromYoutrack->getTicketSummary($ticket);
        $response['ticketUrl'] = $GLOBALS['youtrackUrl'].'/issue/'.$ticket;

        $project = explode('-', $ticket)[0];
        $response['workTypes'] = $getDataFromYoutrack->getTicketWorkTypes($project);

        echo json_encode($response);
    }
}

