<?php
class cache {
    private $cacheFolder;
    public function __construct(){
        $this->cacheFolder = __DIR__ .'/../cache';
    }
    
    function getFilename($ref){
        $ref = $this->validateFileName($ref);
        return $this->cacheFolder.'/'.$ref;
    }
    function validateFileName($ref){
        $delimiter = '¦';
        $unwanted = array( '/' );
        $ref = str_replace( $unwanted, $delimiter , $ref);
        $delimiter = '¬';
        $unwanted = array(',', ';', '|', ' ', '$', '"', "'", '*' );
        $ref = str_replace( $unwanted, $delimiter , $ref);
        return $ref;
    }
    //$ref is the rest url
    function checkForCached($ref){
        $fileName = $this->getFilename($ref);
        if ( file_exists($fileName) && is_readable($fileName) ) {
            return true;
        } else {
            return false;
        }    
    }
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
                error_log($this->cacheFolder.' folder is dosnt exists');
                return false;
            }
        }
    }
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
    function clearCache(){
        $files = glob($this->cachedFolder);
        foreach($files as $fileName){
            if(is_file($fileName)){
                if(is_writable($filename)){
                    unlink($fileName);
                }else{
                    error_log('clear cache: failed to remove file '.$fileName.' it is not writable');
                }
            }
        }
    }
}