<?php

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/getDataFromYoutrack.php';
require_once __DIR__.'/authenticationAndSecurity.php';
use Ddeboer\DataImport\Writer\WriterInterface;

class ApiWriter implements WriterInterface
{
    protected $xml;

    public function createXML($item)
    {
        global $youtrackUrl;

        $getDataFromYoutrack = new getDataFromYoutrack();

        if (!isset($item['project']) || !isset($item['summary']) || !isset($item['reporterName'])) {
            echo 'required columns missing, please read <a href="../uploads/test.csv">minimum required csv</a>';
            exit();
        }

        // if created time not set
        if (!isset($item['created'])) {
            $item['created'] = time().'000';
        }

        // if no issue no. set
        if (!isset($item['numberInProject'])) {
            $url = $youtrackUrl.'/rest/issue/?filter=project%3A%7B'.$item['project'].'%7D+order+by%3A%7Bissue+id%7D+desc&max=1&with=numberInProject';
            $res = $getDataFromYoutrack->rest($url, 'get', null, null, null, false);
            preg_match('~<value>(.*?)</value>~', $res, $numberInProject);
            $numberInProject = $numberInProject[1] + 1;
        } else {
            $numberInProject = $item['numberInProject'];
        }
        // remove date fields that are not set
        foreach ($item as $key => $value) {
            if ($value == 'NaN') {
                unset($item[$key]);
            }
        }

        // php xml writer documentation
        //http://board.phpbuilder.com/showthread.php?10356853-A-quick-PHP-XMLWriter-Class-Tutorial-%28XML-amp-RSS%29

        // THIS IS ABSOLUTELY ESSENTIAL - DO NOT FORGET TO SET THIS
        @date_default_timezone_set('GMT');

        $this->xml = new XMLWriter();
        $this->xml->openMemory();
        // Output directly to the user
        $this->xml->startDocument('1.0');
        $this->xml->setIndent(4);

        // -- create xml ---
        $this->xml->startElement('issues');
        $this->xml->startElement('issue');
        $this->xml->startElement('field');
        $this->xml->writeAttribute('name', 'numberInProject');
        $this->xml->startElement('value');
        $this->xml->text($numberInProject);
        $this->xml->endElement();
        $this->xml->endElement();

        foreach ($item as $key => $value) {
            if ($key != 'project') {
                $this->xml->startElement('field');
                $this->xml->writeAttribute('name', trim($key));

                if ($key == 'description' or $key == 'summary') {
                    $valueSplit = [$value];
                } else {
                    $valueSplit = explode(',', $value);
                }

                foreach ($valueSplit as $value) {
                    $this->xml->startElement('value');
                    $this->xml->text(trim($value));
                    $this->xml->endElement();
                }
                $this->xml->endElement();
            }
        }

        $this->xml->endElement();
        $this->xml->endElement(); // close issues
        $this->xml->endDocument(); //close doc
        $Myxml = $this->xml->outputMemory();

        return [$Myxml, $numberInProject];
    }

    public function sendToTracker($Myxml, $item)
    {
        global $youtrackUrl;
        $authenticationAndSecurity = new authenticationAndSecurity();
        $getDataFromYoutrack = new getDataFromYoutrack();
        $project = $item['project'];
        $url = $youtrackUrl.'/rest/import/'
            .$project
            .'/issues';
        // test mode cant work for standard users, so being depreciated
//        if(null !== $authenticationAndSecurity->getPost('test')){
//            $url .= '?test=true';
//        }
        $getDataFromYoutrack->rest($url, 'put', ['Content-Type' => 'application/xml'], $Myxml);
    }

    public function updateTracker(array $item)
    {
        list($Myxml, $numberInProject) = $this->createXML($item);
        //----------
        // form http://confluence.jetbrains.com/display/YTD6/Import+Issues
        // PUT /rest/import/{project}/issues?{assigneeGroup}&{test}
        // requires admin level permissions
        //----------
        $this->sendToTracker($Myxml, $item);

        echo $item['project'].'-'.$numberInProject.':   '.$item['summary'];
        echo $GLOBALS['newline'];
    }

    /**
     * create issue on youtrack, creates $GLOBALS['createByFormAjax'] with upload success data.
     *
     * @param array $item      youtrack ticket data
     * @param int   $ticketRow ticket row no. added to data array
     *
     * @return string new ticket ref
     */
    public function stdUserCreateIssue($item, $ticketRow = null)
    {
        global $youtrackUrl;
        $getDataFromYoutrack = new getDataFromYoutrack();
        $authenticationAndSecurity = new authenticationAndSecurity();

        //https://confluence.jetbrains.com/display/YTD65/Create+New+Issue
        // PUT /rest/issue?{project}&{summary}&{description}&{attachments}&{permittedGroup}
        $url = $youtrackUrl.'/rest/issue?project='.$item['project'].'&summary='.urlencode($item['summary']).'&description='.urlencode($item['description']);
        $res = $getDataFromYoutrack->restResponse($url, 'put');
        $res = $res->getResponse();
        $location = $res->getHeader('location');
        foreach ($location as $_location) {
            $singleLocation = $_location;
        }
        $ticketRef = explode('/', $singleLocation);
        $ticketRef = $ticketRef[sizeof($ticketRef) - 1];

        $isAjax = $authenticationAndSecurity->getGet('ajax');
        if ($isAjax !== 'true') {
            echo 'created: <a href="'.$youtrackUrl.'/issue/'.$ticketRef.'">'.$ticketRef.':   '.$item['summary'].'</a>';
            echo $GLOBALS['newline'];
        } else {
            $GLOBALS['createByFormAjax'][$ticketRef] = [
                'uploaded' => true,
                'summary' => $item['summary'],
                'url' => $youtrackUrl.'/issue/'.$ticketRef,
                'ticketRef' => $ticketRef,
            ];
            if ($ticketRow) {
                $GLOBALS['createByFormAjax'][$ticketRef]['row'] = $ticketRow;
            }
        }

        return $ticketRef;
    }
    /**
     * @global string $youtrackUrl
     *
     * @param string $issueRef
     * @param array  $item
     */
    public function stdUserUpdateIssue($issueRef, $item)
    {
        global $youtrackUrl;
        $getDataFromYoutrack = new getDataFromYoutrack();
        $authenticationAndSecurity = new authenticationAndSecurity();
        $customFieldsDetails = $getDataFromYoutrack->getCustomFieldTypeAndBundle('', $item['project']);
        // https://confluence.jetbrains.com/display/YTD65/Apply+Command+to+an+Issue
        // POST /rest/issue/{issue}/execute?{command}&{comment}&{group}&{disableNotifications}&{runAs}
        $cmd = '';
        foreach ($item as $key => $value) {
            switch (trim($key)) {
                case 'project':
                case 'summary':
                case 'description':
                case 'Spent time':
                case 'reporterName':
                    break;
                case 'assignee':
                    $cmd .= ' '.$key.' '.$value;
                    break;
                case 'links':
                    $cmd .= ' '.$value;
                    break;
                default:
                    // convert into required date format from the xml's import required timestamp format ... youtrack api inconsistant
                    if (!isset($customFieldsDetails[$key])) {
                        // for Scheduled Date, Invoice Id which dont seem to exist for this project so why is itg loading in form????
                      //  $cmd .= ' '.$key.' '.$value;
                    } elseif ($customFieldsDetails[$key]['fieldType'] === 'date') {
                        $value = substr($value, 0, -3);
                        date_default_timezone_set('Europe/London');
                        $value = date('Y-m-d', $value);
                        if ($value) {
                            $cmd .= ' '.$key.' '.$value;
                        }
                    } elseif ($customFieldsDetails[$key]['fieldType'] === 'string') {
                        $cmd .= ' '.$key.' "'.$value.'"';
                    } else {
                        $cmd .= ' '.$key.' '.$value;
                    }
                    break;
            }
        }
     //  $url = 'http://tracker.juno.is/youtrack/rest/issue/test-57/execute?command= State Open';
        $url = $youtrackUrl.'/rest/issue/'.$issueRef.'/execute?command='.$cmd;
        $getDataFromYoutrack->rest($url, 'post');

        $isAjax = $authenticationAndSecurity->getGet('ajax');
        if ($isAjax !== 'true') {
            echo 'updated : <a href="'.$youtrackUrl.'/issue/'.$issueRef.'">'.$issueRef.'</a>';
            echo $GLOBALS['newline'];
        } else {
            $GLOBALS['createByFormAjax'][$issueRef]['updated'] = true;
        }
    }

    /**
     * upload ticket to youtrack with standard user permissions.
     *
     * @param array $item      ticket item
     * @param int   $ticketRow ticket row no. added to data array
     */
    public function stdUserUpdateTracker(array $item, $ticketRow)
    {
        try {
            $issueRef = $this->stdUserCreateIssue($item, $ticketRow);
            $this->stdUserUpdateIssue($issueRef, $item);
            $this->stdUserUpdateAttachments($issueRef, $item);
        } catch (Exception $e) {
            error_log($e);
            echo 'IMPORT ISSUE FAILED:: unable to import ticket to '.$item['project'].' with summary "'.$item['summary'].'"'.$GLOBALS['newline'];
            $posts[$postskey] = array_merge(['upload success' => 'failed'], $posts[$postskey]);
        }
    }

    /**
     * send attachment files to the youtrack.
     *
     * @global type $youtrackUrl
     *
     * @param string $issueRef
     * @param array  $item
     */
    public function stdUserUpdateAttachments($issueRef, $item)
    {
        global $youtrackUrl;
        $getDataFromYoutrack = new getDataFromYoutrack();
        $files = (isset($item['attachmentFiles'])) ? $item['attachmentFiles'] : null;
        if ($files && $count = count($files['name'])) {
            for ($i = 0; $i < $count; ++$i) {
                if (!strlen($files['name'][$i])) {
                    continue;
                }
                $header = ['Content-Type' => 'multipart/form-data'];
                $body = [
                    'name' => $files['name'][$i],
                    'file' => '@'.$files['tmp_name'][$i],
                    'Content-Type' => $files['type'][$i],
                ];

                // POST /rest/issue/{issue}/attachment
                $url = $youtrackUrl.'/rest/issue/'.$issueRef.'/attachment';
                $getDataFromYoutrack->rest($url, 'post', $header, $body);
            }
            $GLOBALS['createByFormAjax'][$issueRef]['attachmentsUpdated'] = true;
        }
    }

    /**
     * Prepare the writer before writing the items.
     *
     * @return $this
     */
    public function prepare()
    {
    }

    /**
     * Write one data item.
     *
     * @param array $item The data item with converted values
     *
     * @return $this
     */
    public function writeItem(array $item)
    {
        $this->updateTracker($item);
    }

    /**
     * Wrap up the writer after all items have been written.
     *
     * @return $this
     */
    public function finish()
    {
    }
}
