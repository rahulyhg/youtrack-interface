<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
require_once __DIR__.'/authenticationAndSecurity.php';
use Juno\Workflow;
use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Guzzle\Client;
use Ddeboer\DataImport\Writer\WriterInterface;

class ApiWriter implements WriterInterface
{
    protected $xml;

    function createXML($item){
        global $youtrack_url;

        $getDataFromYoutrack = new getDataFromYoutrack;
        //
        if( !isset($item['project']) || !isset($item['summary']) || !isset($item['reporterName']) ){
           echo 'required columns missing, please read <a href="../uploads/test.csv">minimum required csv</a>';
           exit();
        }
        //
        // if created time not set
        if(!isset($item["created"])){
            $item["created"] = time().'000';
        }
        //
        // if no issue no. set
        if(!isset($item['numberInProject'])){
            $url = $youtrack_url.'/rest/issue/?filter=project%3A%7B'.$item["project"].'%7D+order+by%3A%7Bissue+id%7D+desc&max=1&with=numberInProject';
            $res = $getDataFromYoutrack->rest($url,'get', null, null, null, false );
            preg_match('~<value>(.*?)</value>~', $res, $numberInProject);
            $numberInProject = $numberInProject[1] + 1;
        }else{
            $numberInProject = $item['numberInProject'];
        }
        // remove date fields that are not set 
        foreach($item as $key => $value ){
            if($value == 'NaN'){
                unset($item[$key]); 
            }
        }
        
        // php xml writer documentation
        //http://board.phpbuilder.com/showthread.php?10356853-A-quick-PHP-XMLWriter-Class-Tutorial-%28XML-amp-RSS%29
        //
        // THIS IS ABSOLUTELY ESSENTIAL - DO NOT FORGET TO SET THIS
        @date_default_timezone_set("GMT");
        //
        $this->xml = new XMLWriter();
        $this->xml->openMemory();
        // Output directly to the user
        $this->xml->startDocument('1.0');
        $this->xml->setIndent(4);
        //
        // -- create xml ---
        $this->xml->startElement('issues');
            $this->xml->startElement('issue');
                $this->xml->startElement('field');
                $this->xml->writeAttribute( 'name','numberInProject');
                    $this->xml->startElement('value');
                        $this->xml->text($numberInProject);
                    $this->xml->endElement();
                $this->xml->endElement();
                //
                foreach($item as $key => $value ){
                    if($key != 'project'){
                        $this->xml->startElement('field');
                        $this->xml->writeAttribute( 'name', trim($key) );
                        //
                        if($key == 'description' or $key == 'summary'){
                          $value_split = [$value];
                        }else{
                          $value_split = explode(',',$value);
                        }
                        //
                        foreach($value_split as $value){
                                    $this->xml->startElement('value');
                                        $this->xml->text( trim($value) );
                                    $this->xml->endElement();
                        }
                        $this->xml->endElement();
                    }
                }
            //
            $this->xml->endElement();
        $this->xml->endElement(); // close issues
        $this->xml->endDocument(); //close doc
        $Myxml = $this->xml->outputMemory();
        return [$Myxml, $numberInProject];
    }
    
    function sendToTracker($Myxml,$item){
        global $youtrack_url;
        $authenticationAndSecurity = new authenticationAndSecurity;
        $getDataFromYoutrack = new getDataFromYoutrack;
        $project = $item['project'];
        $url = $youtrack_url . '/rest/import/'
            . $project
            .'/issues';
        if(null !== $authenticationAndSecurity->getPost('test')){
            $url .= '?test=true';
        }
        $getDataFromYoutrack->rest($url,'put',['Content-Type'=>'application/xml'],$Myxml);
    }
    
    function updateTracker(array $item){
        list( $Myxml, $numberInProject ) = $this->createXML($item);
        //----------
        // form http://confluence.jetbrains.com/display/YTD6/Import+Issues
        // PUT /rest/import/{project}/issues?{assigneeGroup}&{test}
        // requires admin level permissions
        //----------
        $this->sendToTracker($Myxml,$item);
        //
        echo $item['project'].'-'.$numberInProject.':   '.$item['summary'];
        echo $GLOBALS["newline"];
    }
    
    function stdUserCreateIssue($item){
        global $youtrack_url;
        $getDataFromYoutrack = new getDataFromYoutrack;
        //https://confluence.jetbrains.com/display/YTD65/Create+New+Issue
        // PUT /rest/issue?{project}&{summary}&{description}&{attachments}&{permittedGroup}
        $url = $youtrack_url.'/rest/issue?project='.$item["project"].'&summary='.$item['summary'].'&description='.$item['description'];
        $res = $getDataFromYoutrack->restResponse($url, 'put');
        $res = $res->getResponse();
        $location = $res->getHeader('location') ;
        foreach($location as $_location){
           $singleLocation = $_location;
        }
        $ticketRef = explode('/',$singleLocation);
        $ticketRef = $ticketRef[sizeof($ticketRef) - 1];
        echo 'created: '.$ticketRef.':   '.$item['summary'];
        echo $GLOBALS["newline"];
        return $ticketRef;
    }
    /**
     * 
     * @global type $youtrack_url
     * @param string $issueRef
     * @param array $item
     */
    function stdUserUpdateIssue($issueRef,$item){
        global $youtrack_url;
        $authenticationAndSecurity = new authenticationAndSecurity;
        $getDataFromYoutrack = new getDataFromYoutrack;
        $customFieldsDetails = $getDataFromYoutrack->getCustomFieldTypeAndBundle('',$item['project']);
        // https://confluence.jetbrains.com/display/YTD65/Apply+Command+to+an+Issue
        // POST /rest/issue/{issue}/execute?{command}&{comment}&{group}&{disableNotifications}&{runAs} 
        $cmd = '';
        foreach($item as $key => $value){
            switch (trim($key)){
                case 'project':
                case 'summary':
                case 'description':
                    break;
                default:
                    // convert into required date format from the xml's import required timestamp format ... youtrack api inconsistant
                    if( !isset($customFieldsDetails[$key]) ){
                        // for asssignee, Scheduled Date, Invoice Id, reporterName
                      //  $cmd .= ' '.$key.' '.$value;
                    }elseif($customFieldsDetails[$key]['fieldType'] === 'date' ){
                        $value = substr($value, 0, -3);
                        $value = date('Y-m-d', $value);
                        $cmd .= ' '.$key.' '.$value;
                    }elseif($customFieldsDetails[$key]['fieldType'] === 'string' ){
                        $cmd .= ' '.$key.' "'.$value.'"';
                    }else{
                        $cmd .= ' '.$key.' '.$value;
                    }
                    break;
            }
        }
        $url = $youtrack_url.'/rest/issue/'.$issueRef.'/execute?command='.$cmd;
        
       $url = 'http://tracker.juno.is/youtrack/rest/issue/test-40/execute?command=State Open';
        $getDataFromYoutrack->rest($url,'post');//,['Content-Type'=>'application/x-www-form-urlencoded']);
        echo 'updated: '.$issueRef;
        echo $GLOBALS["newline"];
    }
    
    // update tracker if user, used only when the submiting user is not an admin
    function stdUserUpdateTracker(array $item){
        try {
            $issueRef = $this->stdUserCreateIssue($item);
            $this->stdUserUpdateIssue($issueRef,$item);
        } catch (Exception $e) {   
            error_log($e);
            echo 'IMPORT ISSUE FAILED:: unable to import ticket to '.$singlePost['project'].' with summary "'.$singlePost['summary'].'"'.$GLOBALS["newline"];
            $posts[$postskey] = array_merge( ['upload success' => 'failed'] , $posts[$postskey] );
        }
    }
    
    /**
     * Prepare the writer before writing the items
     *
     * @return $this
     */
    public function prepare()
    {
    }

    /**
     * Write one data item
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
     * Wrap up the writer after all items have been written
     *
     * @return $this
     */
    public function finish()
    {
    }
}