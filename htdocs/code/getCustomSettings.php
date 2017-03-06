<?php
/**
 * get the custom settings from customsettings.php.
 */
require_once __DIR__.'/authenticationAndSecurity.php';

if (!file_exists(__DIR__.'/../../customSettings.php')) {
    die('custom settings file missing: please copy customSettings.php.example to customSettings.php and update it with your settings');
}

require_once __DIR__.'/../../customSettings.php';
$die = false;
if ($youtrackUrl === 'http://example.com') {
    die('please fill in the customSettings.php file with your details');
}

if (substr($youtrackUrl, 0, 7) !== 'http://' && substr($youtrackUrl, 0, 8) !== 'https://') {
    echo 'invalid youtrack url: "http://" or "https://" required at the start of your url. please update $youtrackUrl in customSettings.php'.$GLOBALS['newline'];
    $die = true;
}
if (substr($youtrackUrl, -1) === '/') {
    echo 'invalid youtrack url: dont finish your url with a "/" . please update $youtrackUrl in customSettings.php'.$GLOBALS['newline'];
    $die = true;
}
if ($die) {
    die();
}
unset($die);
