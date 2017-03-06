<?php

require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__.'/getDataFromYoutrack.php';
require_once __DIR__.'/bootstrap.php';

// --delete me : used for testing guzzle --
require_once __DIR__.'/../../vendor/autoload.php';

/**
 * submit data from the time tracker page to youtrack.
 */
class timeTrackerSubmit
{
    /**
     * organise post data by ticket.
     *
     * @param array $posts
     *
     * @return array post data in format $postsArray[ticket][field] = value
     */
    public function organisePosts($posts)
    {
        $organisedPosts = [];
        foreach ($posts as $key => $value) {
            $explodedKey = explode('-', $key);
            if (array_key_exists(1, $explodedKey)) {
                if (!array_key_exists($explodedKey[0], $organisedPosts)) {
                    $organisedPosts[$explodedKey[0]] = [];
                }
                $organisedPosts[$explodedKey[0]][$explodedKey[1]] = $value;
            }
        }

        return $organisedPosts;
    }

    /**
     * converts duration format from "1h 12m" into an integer of minutes.
     *
     * @param string $duration duration in format "1h 3m"
     *
     * @return int
     */
    public function convertDuration($duration)
    {
        $totalMinutes = 0;
        $explodedDuration = explode('h', $duration);

        if (isset($explodedDuration[1])) {
            $hours = $explodedDuration[0];
            $totalMinutes += $hours * 60;
            $minutes = rtrim($explodedDuration[1], 'm');
            $totalMinutes += $minutes;
        } else {
            $totalMinutes += $explodedDuration[0];
        }

        return $totalMinutes;
    }

    /**
     * create xml to send to Youtrack.
     *
     * @param array $timeRow single timing data  ['start'=>'16:47', end=>'16:52', 'duration' => '0h 5m', 'description' => 'my description', 'type' => 'Development']
     *
     * @return string timing xml for upload
     */
    public function createXml($timeRow)
    {
        $xml = '';
        if (!$date = strtotime($timeRow['date'])) {
            $date = time();
        }
        $date .= '000';
        $duration = $this->convertDuration($timeRow['duration']);
        $xml .= '<workItem>'.
            '<date>'.$date.'</date>'.
            '<duration>'.$duration.'</duration>'.
            '<description>'.str_replace('&', 'and', $timeRow['description']).'</description>'. // & symbol not accepted by youtrack api
            '<worktype>'.
                '<name>'.$timeRow['type'].'</name>'.
            '</worktype>'.
        '</workItem>';

        return $xml;
    }

    /**
     * update Youtrack with timing data.
     *
     * @param string $content  timing xml
     * @param string $ticketId ticket reference
     *
     * @return bool success
     */
    public function updateYoutrack($content, $ticketId)
    {
        global $youtrackUrl;
        $getDataFromYoutrack = new getDataFromYoutrack();
        $url = $youtrackUrl.'/rest/issue/'
            .$ticketId
            .'/timetracking/workitem';
        $res = $getDataFromYoutrack->restResponse($url, 'post', ['Content-Type' => 'text/xml; charset=UTF8'], $content);
        $res = $res->getResponse();
        if ($res->getStatusCode() == 201) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * post timing data to youtrack.
     *
     * @param string $content  timing xml
     * @param string $ticketId ticket reference
     *
     * @return bool
     */
    public function postData($content, $ticketId)
    {
        try {
            return $this->updateYoutrack($content, $ticketId);
        } catch (Exception $e) {
            error_log($e);
            error_log('IMPORT ISSUE FAILED:: unable to import timing to ticket '.$ticketId.'<br>');

            return false;
        }
    }

    /**
     * exit respond with 401 code if logged in.
     */
    public function checkForCookie()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        $authentication = $authenticationAndSecurity->getAuthentication();
        if ($authentication['type'] === 'cookie' && $authentication['details'] === false) {
            http_response_code(401); // set 'unauthorised' code
            exit();
        }
    }

    /**
     * format & send timing data to Youtrack.
     *
     * @return array
     */
    public function submit()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        $this->checkForCookie();
        $posts = $authenticationAndSecurity->getAllPosts();
        $ticketId = $posts['project'].'-'.$posts['ticketnumber'];
        $organisedPosts = $this->organisePosts($posts);
        foreach ($organisedPosts as $key => $timeRow) {
            if ($timeRow['duration']) {
                $xml = $this->createXml($timeRow);
                $organisedPosts[$key]['success'] = $this->postData($xml, $ticketId);
            }
        }

        $workflow = new ApiWriter();
        if (strlen(trim($posts['state'])) > 0) {
            $workflow->stdUserUpdateIssue($ticketId, ['State' => $posts['state'], 'project' => $posts['project']]);
            if ($GLOBALS['createByFormAjax'][$ticketId]['updated']) {
                $organisedPosts['state']['success'] = true;
            }
        }

        return $organisedPosts;
    }
}

$timeTrackerSubmit = new timeTrackerSubmit();
$response = $timeTrackerSubmit->submit();
echo json_encode($response);
