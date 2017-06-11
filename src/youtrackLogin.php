<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';

class youtrackLogin {

    public $authenticationAndSecurity;
    public $getDataFromYoutrack;

    function __construct()
    {
        $this->getDataFromYoutrack = new getDataFromYoutrack();
        $this->authenticationAndSecurity = new authenticationAndSecurity();
    }

    /**
     * set login cookie.
     */
    function LoginToYouTrack()
    {
        global $youtrackUrl;

        $url = $youtrackUrl.'/rest/user/login';
        $response = $this->getDataFromYoutrack->rest(
            $url,
            'post',
            null,
            [
                'login' => $this->authenticationAndSecurity->getPost('user'),
                'password' => $this->authenticationAndSecurity->getPost('password')
            ],
            array(),
            false);
        if ($response && $response->getStatusCode() == 200) {
            $cookies = $response->getHeader('set-cookie');
            foreach ($cookies as $key => $singleCookie) {
                $this->authenticationAndSecurity->setCookie('Set-Cookie'.$key, $singleCookie, 0, '/');
            }
            $reporterCookieName = 'myCookie';
            $this->authenticationAndSecurity->setCookie($reporterCookieName, $this->authenticationAndSecurity->getPost('user'), 0, '/');

            return true;
        } else {
            return false;
        }
    }

    function execute()
    {
        $this->LoginToYouTrack();
        $this->authenticationAndSecurity->redirectBackToIndex();

    }
}
