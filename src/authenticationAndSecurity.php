<?php
class authenticationAndSecurity {
    function setCookie( $name, $value, $expire = 0, $path = '/'){
        $encryptedValue = $this->encryptDecrypt('encrypt', $value);
        setCookie($name, $encryptedValue, $expire, $path);
    }
    function encryptDecrypt($action, $string) {
        $output = false;
        $key = $GLOBALS['cookieEncryptionKey'];
        // initialization vector
        $iv = md5(md5($key));
        if( $action == 'encrypt' ) {
                $output = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, $iv);
                $output = base64_encode($output);
        }
        else if( $action == 'decrypt' ){
                $output = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, $iv);
        }
        return $output;
    }
    
    function getSingleCookie($cookieName){
        return $this->encryptDecrypt( 'decrypt', $_COOKIE[$cookieName] );
    }
    function getBrowserCookies(){
        if( !isset($_COOKIE['Set-Cookie0']) || $_COOKIE['Set-Cookie0']===null){
            $cookies = null;
        }else{
            $cookie0 = $this->getSingleCookie('Set-Cookie0');
            $cookie1 = $this->getSingleCookie('Set-Cookie1');
            $cookies[0] = $this->splitCookie( $cookie0 );
            $cookies[1] = $this->splitCookie( $cookie1 );
        }
        return $cookies;
    }
    function splitCookie($string){
        $stringComponents = explode(';',$string);
        foreach ($stringComponents as $value) {
            $exploded = explode('=', $value);
            $exploded[0] = trim($exploded[0]);
            if( $exploded[0] != 'Path' && $exploded[0] != 'Expires' && $exploded[0] != "HttpOnly" ){
                $cookieData[ $exploded[0] ] = $exploded[1];
            }
        }
        return $cookieData;
    }
    function removeCookies(){
        // set the expiration date to one hour ago
        setCookie("Set-Cookie0", "", time() - 3600, '/');
        setCookie("Set-Cookie1", "", time() - 3600, '/');
        $reporterCookieName = 'myCookie';
        setCookie($reporterCookieName, "", time() - 3600, '/');
    }
    function getAuthentication(){
        if( !isset($GLOBALS['authenticationType']) ){
            $authenticationType = 'cookie';
        }else{
            $authenticationType = $GLOBALS['authenticationType'];
        }
        switch ($authenticationType){
            case 'cookie':
                $cookies = $this->getBrowserCookies();
                if ($cookies === null){
                    return ['type'=>'password', 'details'=>[ 'user'=>$_POST['user'], 'password'=>$_POST['password'] ]];
                }else{
                    return ['type'=>'cookie',  'details'=>$cookies ];
                }
                break;
            case 'password':
                return ['type'=>'password', 'details'=>[ 'user'=>$_POST['user'], 'password'=>$_POST['password'] ]];
                break;
            case 'file':
                if($GLOBALS['user']){
                    $user = $GLOBALS['user'];
                }else{
                    echo 'error: user not set in file and authentication set to file'.$GLOBALS['newline'];
                    die();
                }
                if($GLOBALS['password']){
                    $password = $GLOBALS['password'];
                }else{
                    echo 'error: user not set in file and authentication set to file'.$GLOBALS['newline'];
                    die();
                }
                return ['type'=>'password','details'=>['user'=>$user, 'password'=>$password]];
                break;
            default:
               echo "Authentication type not recognised: Please update the customSettings.php file";
        }
    }
    function redirectBackToPage(){
        $url = $_SERVER['HTTP_REFERER'];
        header( "Location: $url" );
        die();
    }
}
