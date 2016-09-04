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

$youtrackFields = [];

$filename = $authenticationAndSecurity->getPost('filename');

list($youtrackFieldsList, $youtrackFields) = $getYoutrackData->getCustomFieldsWithDetails();

$youtrackFields['user'] = $getYoutrackData->getUsers();
array_push($youtrackFieldsList, 'user');

function reorganiseArray($array){
    $newArray = [];
    foreach( $array as $key => $value ){
        $i = 0;
        foreach( $value as $key2 =>$val){
           if( !isset($newArray[$i]) ){
               $newArray[$i] = [];
           }
           $newArray[$i][$key] = $val;
           $i++;
        }
    }
    return $newArray;
}
$csvData = reorganiseArray($youtrackFields);

function makeColumnsFullLength($csvData, $youtrackFieldsList){
// needs to replace cos just amending old causes data in wrong column when csv created
    $csvDataReplace = [];
    foreach($csvData as $key => $column ){
        $csvDataReplace[$key] = [];
        foreach($youtrackFieldsList as $field){
            if( !isset($column[$field]) ){
                $csvDataReplace[$key][$field] = '';
            }else{
                $csvDataReplace[$key][$field] = $csvData[$key][$field];
            }
        }
    }
    return $csvDataReplace;
}
$csvData = makeColumnsFullLength($csvData,$youtrackFieldsList);

if( substr($filename, -4) != '.csv'){
    $filename = $filename.'.csv';
}
$outputFileLocation = '../export/'.$filename;
$csvCreated = $csvClass->createCsv( $csvData, $outputFileLocation );

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
    transmitFile($outputFileLocation);
}