<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

class timeJsonSaveAjax {
    private $timeJsonFolder;
    public function __construct(){
        $this->timeJsonFolder = __DIR__ .'/../timings';
    }

    function getFolderName($reporterName){
        $reporterFilenameFriendly = rawurlencode( trim($reporterName) );
        return $this->timeJsonFolder.'/'.$reporterFilenameFriendly;
    }
    
    function createReporterFolder($folderName){
        $parentFolder = $this->timeJsonFolder;
        if( file_exists( $parentFolder )){
            if ( is_writable( $parentFolder ) ){
                if( !file_exists( $folderName )){
                    mkdir($folderName);
                    chmod($folderName, 0755);
                }
            }else{
                error_log('timings folder not writable: '.$parentFolder);
            }
        }
    }
    
    function createTimeJsonFile($reporterName,$content){
        $folderName = $this->getFolderName($reporterName);
        $this->createReporterFolder($folderName);
        $fileName = $folderName.'/'.time();
        if( file_exists($fileName) ){
            error_log('timings file exists: '.$fileName);
            return false;
        }else{
            if( file_exists($folderName)){
                if ( is_writable($folderName) ){
                    file_put_contents($fileName, $content);
                    chmod($fileName, 0775);
                    return true;
                }else{
                    error_log('folder is not writable: '.$folderName);
                    return false;
                }
            }else{
                error_log('folder dosnt exists: '.$folderName);
                return false;
            }
        }
    }
    
    function removeOldFiles($reporterName){
        if( isset($GLOBALS['timeTrackerKeptEdits']) && is_numeric($GLOBALS['timeTrackerKeptEdits']) ){
            $timeTrackerKeptEdits = $GLOBALS['timeTrackerKeptEdits'];
        }else {
            $timeTrackerKeptEdits = 10;
        }
        if( $timeTrackerKeptEdits > 0 ){
            $folderName = $this->getFolderName($reporterName);
            $this->createReporterFolder($folderName);
            $files = scandir($folderName, SCANDIR_SORT_DESCENDING);
            for($i=$timeTrackerKeptEdits;$i<sizeof($files)-2 ;$i++){
                unlink($folderName.'/'.$files[$i]);
            }
        }
    }
    
    function saveJson($json){
        $authenticationAndSecurity = new authenticationAndSecurity;
        
        $reporterCookieName = 'myCookie';
        if(null !== $authenticationAndSecurity->getcookie($reporterCookieName)){
            $reporterName =  $authenticationAndSecurity->getSingleCookie($reporterCookieName);
        }else{
            die('Error: no reporter cookie');
        }
        
        if( $this->createTimeJsonFile($reporterName, $json) ){
            $this->removeOldFiles($reporterName);
            return true;
        }else{
            return false;
        }
    }
}
$timeJsonSaveAjax = new timeJsonSaveAjax;
$authenticationAndSecurity = new authenticationAndSecurity;
$json = $authenticationAndSecurity->getPost('json');
if($timeJsonSaveAjax->saveJson($json)){
    return 'successful server file creation';
}else{
    return 'failed';
}