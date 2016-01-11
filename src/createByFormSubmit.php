<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/csv.php';

use Guzzle\Client;
use Ddeboer\DataImport\Writer\CsvWriter;
require_once __DIR__.'/authenticationAndSecurity.php';

class createByFormSubmit{

    function organisePosts(){
        $posts = [];
        foreach($_POST as $inputName => $inputValue){
            if( $inputName != 'test' && $inputName != 'user' && $inputName != 'password' ){
                $keyArray = [];
                $explode = explode('-', $inputName); // split all parts
                if(count($explode) > 0){
                    $keyArray[1] = array_pop($explode); // removes the last element, and returns it
                    if(count($explode) > 0){
                        $keyArray[0] = implode('-', $explode); // glue the remaining pieces back together
                        $keyArray[0] = str_replace('¬', ' ', $keyArray[0] );
                    }
                }
                $posts[ $keyArray[1] ][ $keyArray[0] ] = $inputValue;
            }
        }
        // remove hidden inputs form
        unset($posts[0]);
        return $posts;
    }
    function sendPostData($posts){
        $authenticationAndSecurity = new authenticationAndSecurity;
        foreach($posts as $postskey => $singlePost){
            foreach ($singlePost as $key => $field ){
                if( $field == '' ){
                    unset($singlePost[$key] );
                }
            }
            if( isset($_POST['user']) ){
                $singlePost['reporterName'] = $_POST['user'];
            }else{
                $reporterCookieName = 'myCookie';
                if(isset($_COOKIE[$reporterCookieName])){
                    $singlePost['reporterName'] =  $authenticationAndSecurity->getSingleCookie($reporterCookieName);
                }else{
                    echo 'Error: no reporter cookie or user set in customSettings'.$GLOBALS['newline'];
                }
            }
            $workflow = new ApiWriter;
            try {
                $workflow->updateTracker($singlePost);
                $posts[$postskey] = array_merge( ['upload success' => 'success'] , $posts[$postskey] );
            } catch (Exception $e) {
                error_log($e);
                echo 'IMPORT ISSUE FAILED:: unable to import ticket to '.$singlePost['project'].' with summary "'.$singlePost['summary'].'"'.$GLOBALS['newline'];
                $posts[$postskey] = array_merge( ['upload success' => 'failed'] , $posts[$postskey] );
            }
        }
        return $posts;
    } 
    function removeSuccessfulPosts($posts){
        foreach( $posts as $singlePost){
            if( $singlePost['upload success'] === 'success'){
                unset($posts[$key]);
            }
        }
        return $posts;
    }
    function createFolder($folder){
        if (!file_exists($folder)) {
            mkdir($folder);
            chmod($folder,$GLOBALS['folderPermissions']);
        }
    }
    function submit(){
        $csvClass = new csvClass;
        
        $GLOBALS['newline'] = '<br/>';
        $newLine = $GLOBALS['newline'];

        echo $newLine . $newLine .
         "------------------------------" . $newLine .
         "    Youtrack csv importer     " . $newLine .
         "------------------------------" . $newLine;

        if (isset($_POST['test'])) {
            echo "-- Testing progress --" . $newLine;
        } else {
            echo "-- Progress --" . $newLine;
        }

        $posts = $this->organisePosts();
        
        $csvLogFolder = __DIR__.'/../log/createByForm/'.date("Y-m-d");
        $csvLogFileName = time().'.csv';
        
        // creates csv log before sending to guzzle as guzzle dosnt fail gracefully
        if($GLOBALS['createByFormTransferLog']){
            $this->createFolder($csvLogFolder);
            $csvClass->create_csv($posts, $csvLogFolder.'/'.$csvLogFileName);
        }
        
        $posts = $this->sendPostData($posts);
        
        if($GLOBALS['createByFormTransferLog']){
            $csvClass->create_csv($posts, $csvLogFolder.'/'.$csvLogFileName);
        }elseif( $GLOBALS['createByFormTransferErrorLog'] ){
            $posts = $this->removeSuccessfulPosts($posts);
            $this->createFolder($csvLogFolder);
            $csvClass->create_csv($posts, $csvLogFolder.'/'.$csvLogFileName);
        }
        
        if (isset($_POST['test'])) {
            echo $newLine . "---- Test Finished -----" . $newLine;
        } else {
            echo $newLine . "---- Upload Finished -----" . $newLine;
        }
    }
}
$createByFormSubmit = new createByFormSubmit;
$createByFormSubmit->submit();