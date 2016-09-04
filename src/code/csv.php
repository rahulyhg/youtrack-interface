<?php
use Ddeboer\DataImport\Writer\CsvWriter;
require_once __DIR__ . '/authenticationAndSecurity.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class csvClass {
    /*
     * returns false if unable to write to folder
     */
    function createCsv($data, $outputFileLocation, $delimiter = ',', $header=[] ){
        if ( ! is_writable(dirname($outputFileLocation))) {
            echo 'Unable to create file '.$outputFileLocation.' . Please check write permissions for your web server (apache/nginx/..)'.$GLOBALS["newline"];
            return false;
        } else {
            $writer = new CsvWriter($delimiter);
            $writer->setStream(fopen($outputFileLocation, 'w'));
            foreach( $data as $col ){
                if( empty($header) ){
                    $header = array_keys($col);
                    $writer->writeItem($header);           
                }
               $writer->writeItem($col);
            }
            $writer->finish();
            $filePermissions = $GLOBALS['filePermissions'];
            chmod($outputFileLocation, $filePermissions);
        }
    }
}
