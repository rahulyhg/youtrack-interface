<?php
use Ddeboer\DataImport\Writer\CsvWriter;
require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__ . '/../vendor/autoload.php';

class csvClass {
    /*
     * returns false if unable to write to folder
     */
    function create_csv( $data, $output_file_location, $delimiter = ',', $header=[] ){
        $authenticationAndSecurity = new authenticationAndSecurity;
        if ( ! is_writable(dirname($output_file_location))) {
            echo 'Unable to create file '.$output_file_location.' . Please check write permissions for your web server (apache/nginx/..)'.$authenticationAndSecurity->getGlobal("newline");
            return false;
        } else {
            $writer = new CsvWriter($delimiter);
            $writer->setStream(fopen($output_file_location, 'w'));
            foreach( $data as $col ){
                if( empty($header) ){
                    $header = array_keys($col);
                    $writer->writeItem($header);           
                }
               $writer->writeItem($col);
            }
            $writer->finish();
            $filePermissions = $authenticationAndSecurity->getGlobal('filePermissions');
            chmod($output_file_location, $filePermissions);
        }
    }
}
