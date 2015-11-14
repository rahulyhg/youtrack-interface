<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
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
        $getDataFromYoutrack = new getDataFromYoutrack;
        $project = $item['project'];
        $url = $youtrack_url . '/rest/import/'
            . $project
            .'/issues';
        if(isset($_POST['test'])){
            $url .= '?test=true';
        }
        $getDataFromYoutrack->rest($url,'put',['Content-Type'=>'application/xml'],$Myxml);
    }
    
    function updateTracker(array $item){
        list( $Myxml, $numberInProject ) = $this->createXML($item);
        //----------
        // form http://confluence.jetbrains.com/display/YTD6/Import+Issues
        // PUT /rest/import/{project}/issues?{assigneeGroup}&{test}
        //----------
        $this->sendToTracker($Myxml,$item);
        //
        echo $item['project'].'-'.$numberInProject.':   '.$item['summary'];
        echo $GLOBALS["newline"];
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