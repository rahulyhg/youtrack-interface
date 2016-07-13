<?php
require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

// --delete me : used for testing guzzle --
require_once __DIR__ . '/../vendor/autoload.php';

class timeTrackerSubmit{
    
    function organisePosts($posts){
        $organisedPosts = [];
        foreach($posts as $key => $value){
            $explodedKey = explode('-', $key);
            if(array_key_exists(1, $explodedKey)){
                if( !array_key_exists($explodedKey[0],$organisedPosts) ){
                    $organisedPosts[$explodedKey[0]] = [];
                }
                $organisedPosts[$explodedKey[0]][$explodedKey[1]] = $value;
            }
        }
       return $organisedPosts;
    }
    
    /*
     * converts duration format from "1h 12m" into an interger of minutes
     */
    function convertDuration($duration){
        $totalMinutes = 0;
        $explodedDuration = explode('h', $duration);

        if(isset($explodedDuration[1])){
            $hours = $explodedDuration[0];
            $totalMinutes += $hours * 60 ;
            $minutes = rtrim($explodedDuration[1],'m');
            $totalMinutes += $minutes;
        }else{
            $totalMinutes += $explodedDuration[0];
        }
        return $totalMinutes;
    }

    /*
     * create xml to send to youtrack
     *
     * input:-
     *  
     * $ticket array of ticket data and timings,
     *   e.g. $ticket =
     *   [ 
     *      'project'=>'testproject',
     *      'ticketnumber'=>'1',
     *      [
     *           0=>['start'=>'16:47', end=>'16:52', 'duration' => '0h 5m', 'description' => 'my description', 'type' => 'Development']
     *           1=>['start'=>'15:14', end=>'16:16', 'duration' => '1h 2m', 'description' => 'my description', 'type' => 'Development']
     *      ]
     *   ]
     * 
     */   
    function createXml($timeRow){
        $xml = '';
        if(!$date = strtotime($timeRow['date'])){
            $date = time();
        }
        $date .= '000';
        $duration = $this->convertDuration($timeRow['duration']);
        $xml .= '<workItem>'.
            '<date>'.$date.'</date>'.
            '<duration>'.$duration.'</duration>'.
            '<description>'.$timeRow['description'].'</description>'.
            '<worktype>'.
                '<name>'.$timeRow['type'].'</name>'.
            '</worktype>'.
        '</workItem>';
        return $xml;
    }
    
    function updateYoutrack($content,$ticketId){
        global $youtrack_url;
        $getDataFromYoutrack = new getDataFromYoutrack;
        $url = $youtrack_url . '/rest/issue/'
            . $ticketId
            .'/timetracking/workitem';
        $res = $getDataFromYoutrack->restResponse($url,'post',['Content-Type' => 'text/xml; charset=UTF8'],$content);
        $res = $res->getResponse();
         if($res->getStatusCode() == 201){
             return true;
         }else{
             return false;
         }
  }
    
    /*
     * post data to youtrack
     */
    function postData($content,$ticketId){
        try {
            return $this->updateYoutrack($content, $ticketId);
        } catch (Exception $e) {
            error_log($e);
            error_log('IMPORT ISSUE FAILED:: unable to import timing to ticket '.$ticketId."<br>");
            return false;
        }
    }
    
    function checkForCookie(){
        $authenticationAndSecurity = new authenticationAndSecurity;
        $authentication = $authenticationAndSecurity->getAuthentication();
        if( $authentication['type']==='cookie' && $authentication['details'] === false ){
            http_response_code(401); // set 'unauthorised' code
            exit();
        }
    }
    
    function submit(){
        $authenticationAndSecurity = new authenticationAndSecurity;
        $this->checkForCookie();
        $posts = $authenticationAndSecurity->getAllPosts();
        $ticketId = $posts['project'].'-'.$posts['ticketnumber'];
        $organisedPosts = $this->organisePosts($posts);
        foreach($organisedPosts as $key => $timeRow){
            $xml = $this->createXml($timeRow);
            $organisedPosts[$key]['success'] = $this->postData($xml,$ticketId);
        }
        return $organisedPosts;
    }
}

$timeTrackerSubmit = new timeTrackerSubmit;
$response = $timeTrackerSubmit->submit();
echo json_encode($response);