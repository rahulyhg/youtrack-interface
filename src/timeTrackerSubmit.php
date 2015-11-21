<?php
require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

class timeTrackerSubmit{
    
    function organisePosts($posts){
        $organisedPosts = [];
        foreach($posts as $key => $value){
            $explodedKey = explode('-', $key);
            if(array_key_exists(1, $explodedKey)){
                $key0 = $explodedKey[0];
                $key1 = $explodedKey[1];
                if( !array_key_exists($key0,$organisedPosts) ){
                    $organisedPosts[$key0] = [];
                }
                if( !array_key_exists($key1,$organisedPosts[$key0]) ){
                    $organisedPosts[$key0][$key1] = [];
                }
                if( isset($explodedKey[2]) ){
                    $organisedPosts[$key0][$key1][$explodedKey[2]] = $value;
                }else{
                    $organisedPosts[$key0][$key1] = $value;    
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
        foreach($ticket as $data){
            if( gettype($data) === 'string' ){
                continue; // not timing data
            }
            if(!$date = strtotime($data['date'])){
                $date = time();
            }
            $date .= '000';
            $duration = $this->convertDuration($data['duration']);
            $xml .= '<workItem>'.
                '<date>'.$date.'</date>'.
                '<duration>'.$duration.'</duration>'.
                '<description>'.$data['description'].'<description>'.
                '<worktype>'.
                    '<name>'.$data['type'].'</name>'.
                '</worktype>'.
            '</workItem>';
        }
        return $xml;
    }
    
    function updateYoutrack($content,$ticketId,$test=false){
        global $youtrack_url;
        $authenticationAndSecurity = new authenticationAndSecurity;
        $getDataFromYoutrack = new getDataFromYoutrack;
        $url = $youtrack_url . '/rest/issue/'
            . $ticketId
            .'/timetracking/workitem';
//        if($test){
//            $url .= '?test=true';
//        }
         $getDataFromYoutrack->rest($url,'post',['Content-Type'=>'application/xml'],$content);
    }
    
    /*
     * post data to youtrack
     */
    function postData($content,$ticket){
        $ticketId = $ticket['project'].'-'.$ticket['ticketnumber'];
        
        try {
            $this->updateYoutrack($content, $ticketId,$ticket['test']);
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
        $organisedPosts = $this->organisePosts($posts);
        foreach($organisedPosts as $key => $ticket){
            $xml = $this->createXml($ticket);
            $organisedPosts[$key]['success'] = $this->postData($xml,$ticket);
        }
    }
}
$timeTrackerSubmit = new timeTrackerSubmit;
$timeTrackerSubmit->submit();