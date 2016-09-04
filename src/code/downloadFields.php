<?php 
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
require_once __DIR__ . '/authenticationAndSecurity.php';
$getYoutrackData = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

require_once __DIR__ . '/csv.php';
$csvClass = new csvClass;

use Ddeboer\DataImport\Writer\CsvWriter;

$youtrack_fields = [];

$filename = $authenticationAndSecurity->getPost('filename');

list($youtrack_fields_list, $youtrack_fields) = $getYoutrackData->getCustomFieldsWithDetails();

$youtrack_fields['user'] = $getYoutrackData->getUsers();
array_push($youtrack_fields_list, 'user');

function reorganiseArray($array){
    $new_array = [];
    foreach( $array as $key => $value ){
        $i = 0;
        foreach( $value as $key2 =>$val){
           if( !isset($new_array[$i]) ){
               $new_array[$i] = [];
           }
           $new_array[$i][$key] = $val;
           $i++;
        }
    }
    return $new_array;
}
$csv_data = reorganiseArray($youtrack_fields);

function makeColumnsFullLength($csv_data, $youtrack_fields_list){
// needs to replace cos just amending old causes data in wrong column when csv created
    $csv_data_replace = []; 
    foreach($csv_data as $key => $column ){
        $csv_data_replace[$key] = [];
        foreach($youtrack_fields_list as $field){
            if( !isset($column[$field]) ){
                $csv_data_replace[$key][$field] = '';
            }else{
                $csv_data_replace[$key][$field] = $csv_data[$key][$field];
            }
        }
    }
    return $csv_data_replace;
}
$csv_data = makeColumnsFullLength($csv_data,$youtrack_fields_list);

if( substr($filename, -4) != '.csv'){
    $filename = $filename.'.csv';
}
$output_file_location = '../export/'.$filename;
$csvCreated = $csvClass->createCsv( $csv_data, $output_file_location );

if($csvCreated == FALSE){
    function transmitFile($filepath){
        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($filepath));
            header('Expires: 1');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            header('Content-type: text/csv');
            readfile($filepath);
            exit;
        }
        unlink($filepath);
    }
    transmitFile($output_file_location);
}