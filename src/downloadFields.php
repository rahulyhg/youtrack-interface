<?php 
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getYoutrackData = new getDataFromYoutrack;

require_once __DIR__ . '/csv.php';
$csvClass = new csvClass;

use Ddeboer\DataImport\Writer\CsvWriter;

$youtrack_fields = [];

$filename = $_POST['filename'];

list($youtrack_fields_list, $youtrack_fields) = $getYoutrackData->get_custom_fields_with_details();

$youtrack_fields['user'] = $getYoutrackData->get_users();
array_push($youtrack_fields_list, 'user');

function reorganise_array($array){
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
$csv_data = reorganise_array($youtrack_fields);

function make_columns_full_length($csv_data,$youtrack_fields_list){
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
$csv_data = make_columns_full_length($csv_data,$youtrack_fields_list);

if( substr($filename, -4) != '.csv'){
    $filename = $filename.'.csv';
}
$output_file_location = '../export/'.$filename;
$csvCreated = $csvClass->create_csv( $csv_data, $output_file_location );

if($csvCreated == FALSE){
    function transmit_file($filepath){
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
    transmit_file($output_file_location);
}