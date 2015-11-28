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
                $key0 = $explodedKey[0];
                if( !array_key_exists($key0,$organisedPosts) ){
                    $organisedPosts[$key0] = [];
                }
                if( isset($explodedKey[1]) ){
                    $organisedPosts[$key0][$explodedKey[1]] = $value;
                }else{
                    $organisedPosts[$key0] = $value;    
                }
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
    function createXml($ticket){
        $xml = '';
        if(!$date = strtotime($ticket['date'])){
            $date = time();
        }
        $date .= '000';
        $duration = $this->convertDuration($ticket['duration']);
        $xml .= '<workItem>'.
            '<date>'.$date.'</date>'.
            '<duration>'.$duration.'</duration>'.
            '<description>'.$ticket['description'].'</description>'.
            '<worktype>'.
                '<name>'.$ticket['type'].'</name>'.
            '</worktype>'.
        '</workItem>';
        return $xml;
    }
    
    function updateYoutrack($content,$ticketId,$test=false){
        global $youtrack_url;
        $authenticationAndSecurity = new authenticationAndSecurity;
        $getDataFromYoutrack = new getDataFromYoutrack;
        $url = $youtrack_url . '/rest/issue/'
            . $ticketId
            .'/timetracking/workitem';
        //$url=$youtrack_url . '/rest/issue/junointernal-296/timetracking/workitem??test=true';
        $res = $getDataFromYoutrack->restResponse($url,'post',['Content-Type' => 'text/xml; charset=UTF8'],$content);
        $res = $res->getResponse();
         if($res->getStatusCode() == 201){
             echo 'success';
         }else{
             echo 'fail';
         }  
        die();
  }
    
    /*
     * post data to youtrack
     */
    function postData($content,$ticketId){
        try {
            $this->updateYoutrack($content, $ticketId);
            echo 'import success';
            return true;
        } catch (Exception $e) {
            error_log($e);
            echo 'IMPORT ISSUE FAILED:: unable to import timing to ticket '.$ticketId.$GLOBALS["newline"];
            return false;
        }
    }
    
    function submit(){
        $authenticationAndSecurity = new authenticationAndSecurity;
        $posts = $authenticationAndSecurity->getAllPosts();
        $ticketId = $posts['project'].'-'.$posts['ticketnumber'];
        $organisedPosts = $this->organisePosts($posts);
        foreach($organisedPosts as $key => $ticket){
            $xml = $this->createXml($ticket);
            $organisedPosts[$key]['success'] = $this->postData($xml,$ticketId);
        }
    }
}
$timeTrackerSubmit = new timeTrackerSubmit;
$timeTrackerSubmit->submit();