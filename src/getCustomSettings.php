<?php

if( !file_exists( __DIR__.'/../customSettings.php' ) ){
    die('custom settings file missing: please copy customSettings.php.example to customSettings.php and update it with your settings');
}

require_once __DIR__.'/../customSettings.php';
$die = false;
if( $youtrack_url === 'http://example.com'){
    die('please fill in the customSettings.php file with your details');
}

if( substr($youtrack_url, 0, 7)  !== 'http://' ){
    echo('invalid youtrack url: "http://" required at the start of your url. please update $youtrack_url in customSettings.php'.$GLOBALS['newline']);
    $die = true;
}
if(  substr($youtrack_url, -1) === '/' ){
    echo('invalid youtrack url: dont finish your url with a "/" . please update $youtrack_url in customSettings.php'.$GLOBALS['newline']);
    $die = true;
}
if($die){
    die();
}
unset($die);