<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';




/**
 * Class createByFormSubmit create tickets from form data.
 */
class createByFormSubmit
{
    /**
     * get post data and organise by ticket.
     *
     * @return array post data in format $postsArray[ticket][field] = value
     */
    public function organisePosts()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();

        $postsArray = [];
        $posts = $authenticationAndSecurity->getAllPosts();

        foreach ($posts as $inputName => $Val) {
            $inputValue = $authenticationAndSecurity->getPost($inputName);
            if ($inputName != 'test' && $inputName != 'user' && $inputName != 'password') {
                $keyArray = [];
                $explode = explode('-', $inputName); // split all parts
                if (count($explode) > 0) {
                    $keyArray[1] = array_pop($explode); // removes the last element, and returns it
                    if (count($explode) > 0) {
                        $keyArray[0] = implode('-', $explode); // glue the remaining pieces back together
                        $keyArray[0] = str_replace('¬', ' ', $keyArray[0]);
                    }
                }
                $postsArray[ $keyArray[1] ][ $keyArray[0] ] = $inputValue;
            }
        }
        // remove hidden inputs form
        unset($postsArray[0]);

        return $postsArray;
    }
    /**
     * organise attachments in $posts, linking them to their ticket.
     *
     * @param array $posts
     *
     * @return array $posts
     */
    public function organiseAttachments($posts)
    {
        $keys = array_keys($_FILES);
        for ($i = 0; $i < count($keys); ++$i) {
            $singleKey = explode('-', $keys[$i]);
            if ($singleKey[1] > 0) {
                $posts[$singleKey[1]]['attachmentFiles'] = $_FILES['attachmentFiles-'.$singleKey[$i]];
            }
        }

        return $posts;
    }

    /**
     * send posts to Youtrack.
     *
     * @param array $posts
     *
     * @return array $posts
     */
    public function sendPostData($posts)
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        foreach ($posts as $postskey => $singlePost) {
            foreach ($singlePost as $key => $field) {
                if ($field == '') {
                    unset($singlePost[$key]);
                }
            }
            if (null !== $authenticationAndSecurity->getPost('user')) {
                $singlePost['reporterName'] = $authenticationAndSecurity->getPost('user');
            } else {
                $reporterCookieName = 'myCookie';
                if (null !== $authenticationAndSecurity->getcookie($reporterCookieName)) {
                    $singlePost['reporterName'] = $authenticationAndSecurity->getSingleCookie($reporterCookieName);
                } else {
                    echo 'Error: no reporter cookie or user set in customSettings'.$GLOBALS['newline'];
                }
            }
            $workflow = new ApiWriter();
            if($workflow->stdUserUpdateTracker($singlePost, $postskey)){
                $posts[$postskey] = array_merge(['upload success' => 'success'], $posts[$postskey]);
            }else{
                $posts[$postskey] = array_merge(['upload success' => 'failed'], $posts[$postskey]);
            }
        }

        return $posts;
    }
    /**
     * remove successful posts (tickets).
     *
     * @param array $posts
     *
     * @return array
     */
    public function removeSuccessfulPosts($posts)
    {
        foreach ($posts as $key => $singlePost) {
            if ($singlePost['upload success'] === 'success') {
                unset($posts[$key]);
            }
        }

        return $posts;
    }
    /**
     * create folder with folder permissions set in customsettings.php.
     *
     * @param string $folder folder path
     */
    public function createFolder($folder)
    {
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true); // need to set with 777 for some reason
            chmod($folder, $GLOBALS['folderPermissions']);
        }
    }
    /**
     * create tickets from form data.
     */
    public function submit()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        $csvClass = new csvClass();

        $isAjax = $authenticationAndSecurity->getGet('ajax');

        $GLOBALS['newline'] = '<br/>';
        $newLine = $GLOBALS['newline'];

        if ($isAjax !== 'true') {
            echo $newLine.$newLine.
             '------------------------------'.$newLine.
             '    Youtrack csv importer     '.$newLine.
             '------------------------------'.$newLine;
            if (null !== $authenticationAndSecurity->getPost('test')) {
                echo '-- Testing progress --'.$newLine;
            } else {
                echo '-- Progress --'.$newLine;
            }
        }

        $posts = $this->organisePosts();
        $posts = $this->organiseAttachments($posts);

        date_default_timezone_set('Europe/London');
        $csvLogFolder = __DIR__.'/../../log/createByForm/'.date('Y-m-d');
        $csvLogFileName = time().'.csv';

        // creates csv log before sending to guzzle as guzzle dosnt fail gracefully
        if ($GLOBALS['createByFormTransferLog']) {
            $this->createFolder($csvLogFolder);
            $csvClass->createCsv($posts, $csvLogFolder.'/'.$csvLogFileName);
        }

        $posts = $this->sendPostData($posts);

        if ($GLOBALS['createByFormTransferLog']) {
            $csvClass->createCsv($posts, $csvLogFolder.'/'.$csvLogFileName);
        } elseif ($GLOBALS['createByFormTransferErrorLog']) {
            $posts = $this->removeSuccessfulPosts($posts);
            $this->createFolder($csvLogFolder);
            $csvClass->createCsv($posts, $csvLogFolder.'/'.$csvLogFileName);
        }

        if ($isAjax !== 'true') {
            if (null !== $authenticationAndSecurity->getPost('test')) {
                echo $newLine.'---- Test Finished -----'.$newLine;
            } else {
                echo $newLine.'---- Upload Finished -----'.$newLine;
            }
        } else {
            echo json_encode($GLOBALS['createByFormAjax']);
        }
    }
}