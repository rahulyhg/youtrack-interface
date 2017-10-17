<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';

/***
 * Class authenticationAndSecurity security
 */
class authenticationAndSecurity
{
    /**
     * is the server using https.
     */
    public function usingHttps()
    {
        if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
            return true;
        }

        return false;
    }

    /**
     * redirect back to the homepage.
     */
    public function redirectBackToIndex()
    {
        if ($this->usingHttps()) {
            $url = 'https://';
        } else {
            $url = 'http://';
        }
        $url .= (string) filter_input(INPUT_SERVER, 'HTTP_HOST').(string) filter_input(INPUT_SERVER, 'REQUEST_URI');
        $url = explode('/', $url);
        array_pop($url);
        array_pop($url);
        $url = implode('/', $url);
        header("Location: $url/index.php", true, 302); // cant use other than 301 and 302 or browsers don't redirect
        exit;
    }

    /**
     *  redirect to the index page if the user is not logged in.
     */
    public function redirectIfNotLoggedIn()
    {
        $authentication = $this->getAuthentication();
        if ($authentication['type'] === 'cookie' && $authentication['details'] === false) {
            $this->redirectBackToIndex();
        }
    }

    /**
     * set the session cookie.
     *
     * @param string $name   cookie name
     * @param string $value  cookie value
     * @param int    $expire expiry Unix timestamp
     * @param string $path   The path on the server in which the cookie will be available on
     */
    public function setCookie($name, $value, $expire = 0, $path = '/')
    {
        $encryptedValue = $this->encryptDecrypt('encrypt', $value);
        setcookie($name, $encryptedValue, $expire, $path);
    }

    /**
     * encrypt or decrypt string.
     *
     * @param string $action encrypt|decrypt
     * @param string $string subject
     *
     * @return string encrypted/decrypted string
     */
    public function encryptDecrypt($action, $string)
    {
        $output = false;
        $key = $GLOBALS['cookieEncryptionKey'];
        // initialization vector
        $iv = md5(md5($key));
        if ($action == 'encrypt') {
            $output = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, $iv);
            $output = base64_encode($output);
        } elseif ($action == 'decrypt') {
            $output = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, $iv);
        }

        return $output;
    }

    /**
     * get a decrypted cookie value.
     *
     * @param $cookieName
     *
     * @return string
     */
    public function getSingleCookie($cookieName)
    {
        $authenticationAndSecurity = new self();

        return $this->encryptDecrypt('decrypt', $authenticationAndSecurity->getcookie($cookieName));
    }
    /**
     * get the cookies values extracted.
     *
     * @return null|array array of cookie data
     */
    public function getBrowserCookies()
    {
        $authenticationAndSecurity = new self();
        if (null === $authenticationAndSecurity->getCookie('Set-Cookie0') || $authenticationAndSecurity->getCookie('Set-Cookie0') === null) {
            $cookies = null;
        } else {
            $cookie0 = $this->getSingleCookie('Set-Cookie0');
            $cookie1 = $this->getSingleCookie('Set-Cookie1');
            $cookies[0] = $this->splitCookie($cookie0);
            $cookies[1] = $this->splitCookie($cookie1);
        }

        return $cookies;
    }
    /**
     * explode cookie value.
     *
     * @param string $string decrypted cookie value
     *
     * @return array cookie value's data
     */
    public function splitCookie($string)
    {
        $stringComponents = explode(';', $string);
        foreach ($stringComponents as $value) {
            $exploded = explode('=', $value);
            $exploded[0] = trim($exploded[0]);
            if (isset($exploded[1])
            && $exploded[0] != 'Path'
            && $exploded[0] != 'Expires'
            && $exploded[0] != 'HttpOnly') {
                $cookieData[ $exploded[0] ] = $exploded[1];
            }
        }
        return $cookieData;
    }
    /**
     * remove the cookies.
     */
    public function removeCookies()
    {
        // set the expiration date to one hour ago
        setcookie('Set-Cookie0', '', time() - 3600, '/');
        setcookie('Set-Cookie1', '', time() - 3600, '/');
        $reporterCookieName = 'myCookie';
        setcookie($reporterCookieName, '', time() - 3600, '/');
    }
    /**
     * get the Youtrack authentication details.
     *
     * @return array Youtrack authentication details
     */
    public function getAuthentication()
    {
        if (!isset($GLOBALS['authenticationType']) || $GLOBALS['authenticationType'] === null) {
            $authenticationType = 'cookie';
        } else {
            $authenticationType = $GLOBALS['authenticationType'];
        }
        switch ($authenticationType) {
            case 'cookie':
                $cookies = $this->getBrowserCookies();
                if ($cookies === null) {
                    if (!$this->getPost('user') === 'user' && !$this->getPost('password') === 'password' && !$this->getPost('user') === '' && !$this->getPost('password') === '') {
                        return ['type' => 'password', 'details' => ['user' => $this->getPost('user'), 'password' => $this->getPost('password')]];
                    } else {
                        return ['type' => 'cookie',  'details' => false];
                    }
                } else {
                    return ['type' => 'cookie',  'details' => $cookies];
                }
                break;
            case 'password':
                return ['type' => 'password', 'details' => ['user' => $this->getPost('user'), 'password' => $this->getPost('password')]];
                break;
            case 'file':
                if ($GLOBALS['user']) {
                    $user = $GLOBALS['user'];
                } else {
                    echo 'error: user not set in file and authentication set to file'.$GLOBALS['newline'];
                    die();
                }
                if ($GLOBALS['password']) {
                    $password = $GLOBALS['password'];
                } else {
                    echo 'error: user not set in file and authentication set to file'.$GLOBALS['newline'];
                    die();
                }

                return ['type' => 'password', 'details' => ['user' => $user, 'password' => $password]];
                break;
            default:
               echo 'Authentication type not recognised: Please update the customSettings.php file';
        }
    }

    /**
     * filter input for security
     * dosnt work with trying to return arrays.
     *
     * @param string $type    post|cookie|get
     * @param string $varName name of the variable to filter
     *
     * @return false|null|string filtered string, or false if filter_input fails,
     *                           or null if variable not set or no length
     */
    public function filterInput($type, $varName)
    {
        switch ($type) {
            //$_POST[]
            case 'post':
                $typeCode = INPUT_POST;
            break;
            //$COOKIE[]
            case 'cookie':
                $typeCode = INPUT_COOKIE;
            break;
            //$GET[]
            case 'get':
                $typeCode = INPUT_GET;
            break;
        }
        $var = (string) filter_input($typeCode, $varName);
        if ($var !== '') {
            return $var;
        } else {
            return null;
        }
    }
    /**
     * get all post data with input filtered.
     *
     * @return array post data
     */
    public function getAllPosts()
    {
        $keys = array_keys($_POST);
        $post = array_map(
           function ($key) {
               return filter_input(INPUT_POST, $key);
           },
           $keys
        );

        return array_combine($keys, $post);
    }

    /**
     * get post value.
     *
     * @param string $name name of the value
     *
     * @return false|null|string filtered string, or false if filter_input fails,
     *                           or null if variable not set or no length
     */
    public function getPost($name)
    {
        return $this->filterInput('post', $name);
    }
    /**
     * get cookie value.
     *
     * @param string $name name of the value
     *
     * @return false|null|string filtered string, or false if filter_input fails,
     *                           or null if variable not set or no length
     */
    public function getCookie($name)
    {
        return $this->filterInput('cookie', $name);
    }
    /**
     * get get value.
     *
     * @param string $name name of the value
     *
     * @return false|null|string filtered string, or false if filter_input fails,
     *                           or null if variable not set or no length
     */
    public function getGet($name)
    {
        return $this->filterInput('get', $name);
    }
}
