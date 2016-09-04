<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/authenticationAndSecurity.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/getCustomSettings.php';

class getDataFromYoutrack {
    function restResponse($url, $postOrGet = 'get', $headers = null, $body = null, $options = null){
        $client = new \Guzzle\Http\Client();
        $authenticationAndSecurity = new authenticationAndSecurity;        
        $authentication =  $authenticationAndSecurity->getAuthentication();
        if(  $authentication['type'] !== 'password' && $authentication['type'] !== 'cookie' && $authentication['type'] !== 'file'){
            echo 'authentication type unknown. please check its set in the customSettings.php file';
            return;
        }
        if( !isset($options) ){
            if( $authentication['type'] === 'password'){
                $options = ['auth' => [ $authentication['details']['user'], $authentication['details']['password'] ]];
            }else{
                $options = [];
            }
        }
        if($postOrGet === 'get'){
           $request = $client->get($url, $headers , $options );
        } elseif($postOrGet === 'post') {
           $request = $client->post($url, $headers , $body, $options );
        } elseif($postOrGet === 'put') {
           $request = $client->put($url, $headers , $body, $options );
        }
//        if( $postOrGet === 'put' && isset($headers) ){
//            foreach($headers as $key => $value){
//                $request->addHeader($key,$value);
//            }
//        }
        if($authentication['type'] === 'cookie'){
            foreach($authentication['details'] as $singleCookie){
                foreach($singleCookie as $cookieName => $cookieValue){
                    $request->addCookie( $cookieName, $cookieValue );
                }
            }
        }
        $request->send(); 
        return $request;
    }
    function rest($url, $postOrGet = 'get', $headers = null, $body = null, $options = null, $cachable = true ){
        if( $cachable == true ){
            if( $GLOBALS['cache'] && $postOrGet == 'get' ){
                $cacheClass = new cache;
                $cached = $cacheClass->getCached($url);
            }else{
                $cached = false;
            }
        }else{
            $cached = false;
        }
        if( !$cached  ){
            $res = $this->restResponse($url, $postOrGet, $headers, $body, $options);
            $res = $res->getResponse();
            $response = trim($res->getBody());
            if( $cachable && $GLOBALS['cache'] && $postOrGet == 'get' ){
                $cacheClass->createCache($url, $response);
            }
           return $response;
        }else{
            return $cached;
        }
    }
    
    /**
     * extract and return data from xml
     * @param string $xml the xml
     * @param string $node the name of the node
     * @param string $attribute [optional] attribute on node to return, if empty string node value is used 
     * @param array $whereAttr [optional] array of required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]  
     * @param string $keyAttribute [optional] uses this attrbute as the key for storing the data in the return array
     * @return array data requested
     */
    function extractDataXml($xml, $node, $attribute='', $whereAttr=[]){
        $returnData = [];
        $reader = new XMLReader();
        $reader->xml($xml);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT) {
                $exp = $reader->expand();
                if ($exp->nodeName == $node){
                   $data = $this->extractDataXmlUseNode($exp,$attribute,$whereAttr);
                   if($data){ 
                       $returnData[] = $data;
                   }
                }
            }
        }
        return $returnData;
    }
    /**
     * extract data from xml node
     * @param DOMNode $exp xml node
     * @param array|string $attribute
     * @param string $whereAttr array of required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]
     * @return array|string
     */
    function extractDataXmlUseNode($exp, $attribute='', $whereAttr=[]){
        if(count($whereAttr)>0){
            $continue = false;
            foreach ( $whereAttr as $attr => $val ){
                if( $exp->getAttribute($attr) == $val ){
                    $continue = true;
                }
            }
        }else{
            $continue = true;
        }
        if( $continue ){
            if(is_array($attribute)){
                foreach($attribute as $singleAttribute){
                    $attributeData[$singleAttribute] = $exp->getAttribute($singleAttribute);
                }
                return $attributeData;
            }else {
                return ($attribute) ? $exp->getAttribute($attribute) : $exp->nodeValue;
            }
        }
    }

    
    function getCustomFields($project='' ){
        global $youtrackUrl;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $url = $youtrackUrl.'/rest/admin/project/'.$project.'/customfield';
        $youtrackProjectCustomfieldsXml = $this->rest($url,'get');
        $youtrackProjectCustomfields = $this->extractDataXml( $youtrackProjectCustomfieldsXml, 'projectCustomField', 'name');
        $key = array_search('Assignee', $youtrackProjectCustomfields);
        if( $key !== false) {
            unset($youtrackProjectCustomfields[$key]); // Assignee is not a custom field
        }
        return $youtrackProjectCustomfields;
    }
    function getCustomFieldTypeAndBundle($youtrackFieldsList = '', $project=''){
        global $youtrackUrl;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrackFieldsList == '' ){
            $youtrackFieldsList = $this->getCustomFields($project);
        }
        foreach($youtrackFieldsList as $field){
            $url = $youtrackUrl.'/rest/admin/project/'.$project.'/customfield/'.$field;
            $customField = $this->rest($url, 'get');
            $CustomFieldtypeArray = $this->extractDataXml( $customField, 'projectCustomField', 'type');
            $customFieldSettings[$field]['fieldType'] = $CustomFieldtypeArray[0];
            // if dropdown field
            if( strpos($CustomFieldtypeArray[0], '[') !== false ){
                $bundle = $this->extractDataXml( $customField, 'param', 'value', ['name'=>'bundle'] );
                $customFieldSettings[$field]['bundle'] = $bundle[0];
            }else{
                $customFieldSettings[$field]['bundle'] = '';
            }
        }
        return $customFieldSettings;
    }
    /*
     * $customFieldTypeAndBundle array from getCustomFieldTypeAndBundle
     */
    function getCustomFieldsDetails($youtrackFieldsList='', $project='', $customFieldTypeAndBundle='' ){
        global $youtrackUrl;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrackFieldsList == '' ){
            $youtrackFieldsList = $this->getCustomFields($project);
        }
        if( $customFieldTypeAndBundle == '' ){
            $customFieldTypeAndBundle = $this->getCustomFieldTypeAndBundle($youtrackFieldsList, $project);
        }
        
        foreach($youtrackFieldsList as $field){
            // if dropdown field
            if( isset($customFieldTypeAndBundle[$field]) && strpos($customFieldTypeAndBundle[$field]['fieldType'], '[') !== false ){
                $fieldTypeShort = explode('[',$customFieldTypeAndBundle[$field]['fieldType']) [0];
                $fieldTypeShort = strtolower($fieldTypeShort);
                if($fieldTypeShort == 'enum'){
                    $url = $youtrackUrl.'/rest/admin/customfield/bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml( $bundleXml, 'value');
                }elseif($fieldTypeShort == 'ownedfield'){
                    $url = $youtrackUrl.'/rest/admin/customfield/ownedFieldBundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml( $bundleXml, 'ownedField');
                }else{
                    $url = $youtrackUrl.'/rest/admin/customfield/'.$fieldTypeShort.'Bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml( $bundleXml, $fieldTypeShort);
                }
            }else{
                $youtrackFields[$field]='';
            }
        }
        return $youtrackFields;
    }
    function getCustomFieldsWithDetails($project=''){
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $youtrackFieldsList = $this->getCustomFields($project);
        $customFieldDetails = $this->getCustomFieldTypeAndBundle($youtrackFieldsList, $project);
        $youtrackFields = $this->getCustomFieldsDetails($youtrackFieldsList, $project, $customFieldDetails);
        return [$youtrackFieldsList, $youtrackFields];
    }

    
    function getProjectsList(){
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/project';
        $youtrackProjectsListXml = $this->rest($url, 'get');
        $youtrackProjectsList = $this->extractDataXml( $youtrackProjectsListXml, 'project', 'id');
        return $youtrackProjectsList;
    }
    function getProjectAssignees($project){
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/project/'.$project.'/assignee';
        $youtrackProjectAssigneesXml = $this->rest($url, 'get');
        $youtrackProjectAssignees = $this->extractDataXml( $youtrackProjectAssigneesXml, 'assignee', 'login');
        natcasesort($youtrackProjectAssignees);
        return $youtrackProjectAssignees;
    }
    
    function getUsers(){
        global $youtrackUrl;
        $userList = [];
        $requestEnd = '';
        $loop = true;
        $usersNo = 0;
        while( $loop == true ){
            $requestEnd = '?start='.$usersNo;
            $url = $youtrackUrl.'/rest/admin/user'.$requestEnd;
            $userListXml = $this->rest($url, 'get');
            $newUserList = $this->extractDataXml( $userListXml, 'user', 'login');
            for($i=0;$i<count($newUserList);$i++){
                $userList[] = $newUserList[$i];
            }
            $usersNo += 10;
            if( count($newUserList) === 0 ){
                $loop = false;
            }
        }
        return $userList;
    }
    
    function getTicketSummary($ticket){
        global $youtrackUrl;
        $ticketSummary = '';
        $url = $youtrackUrl . '/rest/issue/'.$ticket;
        try {
            $ticketXml = $this->rest($url, 'get');
            $ticketSummary = $this->extractDataXml( $ticketXml,'field', '', $whereAttr=['name'=>'summary']);
        } catch (Exception $e) {
            error_log($e);
        }
        return $ticketSummary;
    }
    function getTicketWorkTypes($project){
        global $youtrackUrl;
        $worktypes = '';

        $url = $youtrackUrl . '/rest/admin/project/'.$project.'/timetracking/worktype';
        try {
            $workTypeXml = $this->rest($url, 'get');
            $workTypes = $this->extractDataXml($workTypeXml, 'name');
        } catch( Exception $e ){
            error_log($e);
        }
        return $workTypes;
    }

    
    
    
    /**
     * 
     * @global type $youtrackUrl
     * @param type $projectId
     * @param type $query
     * @param type $maximumReturned maximum returned tickets
     */
    function getTicketsFromSearch($projectId,$query,$maximumReturned=100,$after=0){
        global $youtrackUrl;
        $filter = urlencode('project:{'.$projectId.'} ');
        $url = $youtrackUrl . '/rest/issue?filter='.$filter.$query.'&max='.$maximumReturned.'&after='.$after;
        try {
            $ticketXml = $this->rest($url, 'get');
            $explodedTicketXml = preg_split('/<\s*\/\s*issue\s*>/i',$ticketXml);
            for( $i=0; $i < count($explodedTicketXml); $i++ ){
                $explodedTicketXml[$i] = preg_replace('/<\s*\/*\s*issuecompacts\s*>/i', '', $explodedTicketXml[$i]);
                $explodedTicketXml[$i] = trim($explodedTicketXml[$i]);
                if(strlen($explodedTicketXml[$i])>0){
                    $explodedTicketXml[$i] = $explodedTicketXml[$i].'</issue>';
                    $ticketID = $this->extractDataXml($explodedTicketXml[$i], 'issue', 'id');
                    $ticketSummary = $this->extractDataXml($explodedTicketXml[$i], 'field','',['name'=>'summary']);
                    $tickets[$ticketID[0]] = $ticketSummary[0];
                }
            }
            
        } catch (Exception $e) {
            error_log($e);
        }
        
        $partialSet =  ($maximumReturned<count($explodedTicketXml)) ? true : false;
        
        return ['tickets'=>$tickets, 'partialSet'=>$partialSet];
    }
    
    
   /**
    * get list of ticket links types e.g. "depends"
    * @global type $youtrackUrl
    * @return type
    */
    function getLinkTypes(){
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/issueLinkType';
        $youtrackLinkTypesXml = $this->rest($url, 'get');
        $youtrackLinkTypes = $this->extractDataXml( $youtrackLinkTypesXml, 'issueLinkType', ['name','outwardName','inwardName']);
        return $youtrackLinkTypes;
    }
}