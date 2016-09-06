<?php
/**
 * download all issues in a project in a csv
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
require_once __DIR__ . '/authenticationAndSecurity.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$filename = $authenticationAndSecurity->getPost('filename');

$url = $youtrackUrl . '/rest/export/'
    . $authenticationAndSecurity->getPost('project')
    .'/issues';
$res = $getDataFromYoutrack->rest($url,'get');

$filepath = '../export/'.$filename;
$file = fopen($filepath, "w")  or die("Unable to open file!");
fwrite($file, $res);
fclose($file);

chmod($file, $GLOBALS['filePermissions'] );

if (file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($filepath));
    header('Expires: 1');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    header('Content-type: text/xml');
    readfile($filepath);
    exit;
}
unlink($filepath);
