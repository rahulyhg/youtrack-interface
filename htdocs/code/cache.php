<?php

/**
 * Class cache caching responses from youtrack api
 */
class cache {
    private $cacheFolder;
    public function __construct(){
        $this->cacheFolder = realpath(__DIR__ . '/../../cache');
    }

    /**
     * get file path for cache file
     * @param string $ref is the Youtrack rest url
     * @return string file path
     */
    function getFilename($ref){
        $ref = $this->validateFileName($ref);
        return $this->cacheFolder.'/'.$ref;
    }
    /**
     * validate filename for cache
     * @param string $ref is the Youtrack rest url
     * @return string filename
     */
    function validateFileName($ref){
        $delimiter = '¦';
        $unwanted = array( '/' );
        $ref = str_replace($unwanted, $delimiter , $ref);
        $delimiter = '¬';
        $unwanted = array(',', ';', '|', ' ', '$', '"', "'", '*' );
        $ref = str_replace($unwanted, $delimiter , $ref);
        return $ref;
    }
    /**
     * is file cached
     * @param string $ref is the Youtrack rest url
     * @return bool
     */
    function checkForCached($ref){
        $fileName = $this->getFilename($ref);
        if ( file_exists($fileName) && is_readable($fileName) ) {
            return true;
        } else {
            return false;
        }    
    }
    /**
     * create cache file
     * @param string $ref is the Youtrack rest url
     * @param string $content content to cache
     * @return bool
     */
    function createCache($ref,$content){
        $fileName = $this->getFilename($ref);
        if( file_exists($fileName) ){
            error_log('cache '.$fileName.' file exists. possible cache corruption');
            return false;
        }else{
            if( file_exists($this->cacheFolder)){
                if ( is_writable($this->cacheFolder) ){
                    file_put_contents($fileName, $content);
                    chmod($fileName, 0775);
                    return true;
                }else{
                    error_log($this->cacheFolder.' folder is not writable');
                    return false;
                }
            }else{
                error_log($this->cacheFolder." folder dosen't exists");
                return false;
            }
        }
    }
    /**
     * get cached content
     * @param string $ref is the Youtrack rest url
     * @return string
     */
    function getCached($ref){
        $fileName = $this->getFilename($ref);
        if( file_exists($fileName) ){
            if( is_readable($fileName) ){
                return file_get_contents($fileName);
            }else{
                error_log('cannot read file:'.$fileName);
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * clear the cache
     * @param string $fileRef filename or references in cache folder to clear
     */
    public function clearCache($fileRef)
    {
        if (!$fileRef) {
            $fileRef = '*';
        }
        $files = glob($this->cacheFolder.'/'.$fileRef);
        foreach ($files as $fileName) {
            if (is_file($fileName)) {
                if (is_writable($fileName)) {
                    unlink($fileName);
                } else {
                    error_log('clear cache: failed to remove file '.$fileName.' it is not writable');
                }
            }
        }
    }
}