<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

class timeJsonGetAjax {
    
    private $timeJsonFolder;
    public function __construct(){
        $this->timeJsonFolder = __DIR__ . '/../timings';
    }
    
    function getFolderName($reporterName){
        $reporterFilenameFriendly = rawurlencode( trim($reporterName) );
        return $this->timeJsonFolder.'/'.$reporterFilenameFriendly;
    }
    
    function getNewestFileFromFolder($folder){
        $files = scandir($folder, SCANDIR_SORT_DESCENDING);
        $newestFile = $files[0];
        return $newestFile;
    }
    
    function getFileContents($fileName){
        if( file_exists($fileName) ){
            if( is_readable($fileName) ){
                return file_get_contents($fileName);
            }else{
                error_log('cannot read file:'.$fileName);
                return false;
            }
        }else{
            error_log('timings file dosnt exist:'.$fileName);
        }
    }
    
    function getTimeJsonFile($reporterName){
        $folderName = $this->getFolderName($reporterName);
        $fileName = $this->getNewestFileFromFolder($folderName);
        $json = $this->getFileContents($folderName.'/'.$fileName);
        return $json;
    }
    
    function getJson(){
        $authenticationAndSecurity = new authenticationAndSecurity;
        
        $reporterCookieName = 'myCookie';
        if(null !== $authenticationAndSecurity->getcookie($reporterCookieName)){
            $reporterName =  $authenticationAndSecurity->getSingleCookie($reporterCookieName);
        }else{
            die('Error: no reporter cookie');
        }
        
        $json = $this->getTimeJsonFile($reporterName);
        return $json;
    }
    
}

$timeJsonGetAjax = new timeJsonGetAjax;
echo $timeJsonGetAjax->getJson();