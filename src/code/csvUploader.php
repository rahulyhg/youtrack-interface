<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__.'/authenticationAndSecurity.php';

ini_set('display_errors', true);

use Juno\Workflow;
use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Guzzle\Client;
use Ddeboer\DataImport\Writer\WriterInterface;

$authenticationAndSecurity = new authenticationAndSecurity;

function file_upload($file_type){
    $currentdir = getcwd();
    $target_dir = $currentdir."/../uploads/";
    $target_file = $target_dir .'current.csv';
    $uploadOk = 1;
    $imageFileType = pathinfo($_FILES["fileToUpload"]["name"],PATHINFO_EXTENSION);
    //
    //Check file size
    if ($_FILES["fileToUpload"]["size"] > 50000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    // Allow certain file formats
    if($imageFileType != $file_type ) {
        echo "Sorry, only '".$file_type."'s files are allowed.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        return false;
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
            return true;
        } else {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }
    }
}

//  =====================
//    csv to php object
//  =====================

/* command line
 * -u user
 * -p password
 * -f file location
 * -t test (if present)
 */
//get command line options
$options = getopt("u:p:f:t");

// if not run in terminal (web page)
if( !isset($options['f']) ){
    $GLOBALS["newline"] = "<br/>\n";
    $uploaded = file_upload('csv');
    if($uploaded !== true ){
        exit();
    }
    $csv = new SplFileObject(__DIR__ . '/../uploads/current.csv');
}else{
    $GLOBALS["newline"] = "\n";
    $_POST["user"] = $options['u'];
    $_POST["password"] = $options['p'];
    if(isset($options['t'])){
        $_POST['test'] = true;
    }
    if( strpos('/',$options['f']) == false ){
        $csv = new SplFileObject(__DIR__ . '/../uploads/'.$options['f']);
    }else{
        $csv = new SplFileObject($options['f']);
    }
}

$newLine = $GLOBALS["newline"];

$reader = new CsvReader($csv);
$reader->setHeaderRowNumber(0);

$mappings = [
    'Project' => 'project',
    'AssigneeGroup' => 'assigneeGroup',
    'Summary' => 'summary',
];

$mappingConverter = new MappingItemConverter($mappings);


echo $newLine.$newLine.
"------------------------------".$newLine.
"    Youtrack csv importer     ".$newLine.
"------------------------------".$newLine;

if(null !== $authenticationAndSecurity->getPost("newline")){
    echo "-- Testing progress --".$newLine;
}else{
    echo "-- Progress --".$newLine;
}

$workflow = new Workflow($reader);
$workflow->addItemConverter($mappingConverter)
    ->addWriter(new ApiWriter())
    ->process();

if(null !== $authenticationAndSecurity->getPost("newline")){
    echo $newLine."---- Test Finished -----".$newLine;
}else{
    echo $newLine."---- Upload Finished -----".$newLine;
}


