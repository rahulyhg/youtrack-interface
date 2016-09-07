<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';

/**
 * Class timeJsonGetAjax get most recent timer data from this server for this user
 */
class timeJsonGetAjax {
    
    private $timeJsonFolder;
    public function __construct(){
        $this->timeJsonFolder = __DIR__ . '/../../timings';
    }

    /**
     * get folder location for the users timing data
     * @param string $reporterName user reference
     * @return string
     */
    function getFolderName($reporterName){
        $reporterFilenameFriendly = rawurlencode( trim($reporterName) );
        return $this->timeJsonFolder.'/'.$reporterFilenameFriendly;
    }

    /**
     * get most recent timing file location
     * @param string $folder folder location
     * @return string filename
     */
    function getNewestFileFromFolder($folder){
        $files = scandir($folder, SCANDIR_SORT_DESCENDING);
        $newestFile = $files[0];
        return $newestFile;
    }

    /**
     * get contents of the file
     * @param string $fileName file location
     * @return bool|string contents or false on fail
     */
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

    /**
     * get most recent timing data
     * @param string $reporterName user reference
     * @return string json string of timing data
     */
    function getTimeJsonFile($reporterName){
        $folderName = $this->getFolderName($reporterName);
        $fileName = $this->getNewestFileFromFolder($folderName);
        $json = $this->getFileContents($folderName.'/'.$fileName);
        return $json;
    }

    /**
     * @return string json string of timer data
     */
    function getJson(){
        $authenticationAndSecurity = new authenticationAndSecurity;
        
        $reporterCookieName = 'timerCookie';
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