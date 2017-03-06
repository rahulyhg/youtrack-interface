<?php

$GLOBALS['newline'] = '\n'; // keep at the top needed by getCustomSettings
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/getCustomSettings.php';
require_once __DIR__.'/getDataFromYoutrack.php';
require_once __DIR__.'/authenticationAndSecurity.php';

$getDataFromYoutrack = new getDataFromYoutrack();
$authenticationAndSecurity = new authenticationAndSecurity();

/**
 * set login cookie.
 */
function LoginToYouTrack()
{
    global $getDataFromYoutrack;
    global $youtrackUrl;
    global $authenticationAndSecurity;

    $url = $youtrackUrl.'/rest/user/login';
    $response = $getDataFromYoutrack->restResponse($url, 'post', null, ['login' => $authenticationAndSecurity->getPost('user'), 'password' => $authenticationAndSecurity->getPost('password')], array());
    $response = $response->getResponse();
    if ($response->getStatusCode() == 200) {
        $cookies = $response->getHeader('set-cookie');
        foreach ($cookies as $key => $singleCookie) {
            $authenticationAndSecurity->setCookie('Set-Cookie'.$key, $singleCookie, 0, '/');
        }
        $reporterCookieName = 'myCookie';
        $authenticationAndSecurity->setCookie($reporterCookieName, $authenticationAndSecurity->getPost('user'), 0, '/');

        return true;
    } else {
        return false;
    }
}
LoginToYouTrack();
$authenticationAndSecurity->redirectBackToIndex();
