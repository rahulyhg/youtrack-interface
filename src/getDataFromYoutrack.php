<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__.'/getCustomSettings.php';

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
    function extract_data_xml( $xml, $node, $attribute='', $whereAttr=[]){
        $return_data = [];
        $reader = new XMLReader();
        $reader->xml($xml);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT) {
                $exp = $reader->expand();
                if ($exp->nodeName == $node){
                   $data = $this->extract_data_xml_use_node($exp,$attribute,$whereAttr);
                   if($data){ 
                       $return_data[] = $data;
                   }
                }
            }
        }
        return $return_data;
    }
    /**
     * extract data from xml node
     * @param DOMNode $exp xml node
     * @param array|string $attribute
     * @param string $whereAttr array of required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]
     * @return array|string
     */
    function extract_data_xml_use_node($exp,$attribute='',$whereAttr=[]){
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

    
    function get_custom_fields($project='' ){
        global $youtrack_url;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $url = $youtrack_url.'/rest/admin/project/'.$project.'/customfield'; 
        $youtrack_project_customfields_xml = $this->rest($url,'get');
        $youtrack_project_customfields = $this->extract_data_xml( $youtrack_project_customfields_xml, 'projectCustomField', 'name');
        $key = array_search('Assignee', $youtrack_project_customfields);
        if( $key !== false) {
            unset($youtrack_project_customfields[$key]); // Assignee is not a custom field
        }
        return $youtrack_project_customfields;
    }
    function getCustomFieldTypeAndBundle($youtrack_fields_list = '', $project=''){
        global $youtrack_url;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrack_fields_list == '' ){
            $youtrack_fields_list = $this->get_custom_fields($project);
        }
        foreach($youtrack_fields_list as $field){
            $url = $youtrack_url.'/rest/admin/project/'.$project.'/customfield/'.$field; 
            $customField = $this->rest($url, 'get');
            $CustomFieldtypeArray = $this->extract_data_xml( $customField, 'projectCustomField', 'type');
            $customFieldSettings[$field]['fieldType'] = $CustomFieldtypeArray[0];
            // if dropdown field
            if( strpos($CustomFieldtypeArray[0], '[') !== false ){
                $bundle = $this->extract_data_xml( $customField, 'param', 'value', ['name'=>'bundle'] );
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
    function get_custom_fields_details($youtrack_fields_list='', $project='', $customFieldTypeAndBundle='' ){
        global $youtrack_url;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrack_fields_list == '' ){
            $youtrack_fields_list = $this->get_custom_fields($project);
        }
        if( $customFieldTypeAndBundle == '' ){
            $customFieldTypeAndBundle = $this->getCustomFieldTypeAndBundle($youtrack_fields_list, $project);      
        }
        
        foreach($youtrack_fields_list as $field){
            // if dropdown field
            if( isset($customFieldTypeAndBundle[$field]) && strpos($customFieldTypeAndBundle[$field]['fieldType'], '[') !== false ){
                $fieldTypeShort = explode('[',$customFieldTypeAndBundle[$field]['fieldType']) [0];
                $fieldTypeShort = strtolower($fieldTypeShort);
                if($fieldTypeShort == 'enum'){
                    $url = $youtrack_url.'/rest/admin/customfield/bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrack_fields[$field] = $this->extract_data_xml( $bundleXml, 'value'); 
                }elseif($fieldTypeShort == 'ownedfield'){
                    $url = $youtrack_url.'/rest/admin/customfield/ownedFieldBundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrack_fields[$field] = $this->extract_data_xml( $bundleXml, 'ownedField'); 
                }else{
                    $url = $youtrack_url.'/rest/admin/customfield/'.$fieldTypeShort.'Bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrack_fields[$field] = $this->extract_data_xml( $bundleXml, $fieldTypeShort); 
                }
            }else{
                $youtrack_fields[$field]='';
            }
        }
        return $youtrack_fields;
    }
    function get_custom_fields_with_details($project=''){
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $youtrack_fields_list = $this->get_custom_fields($project);
        $customFieldDetails = $this->getCustomFieldTypeAndBundle($youtrack_fields_list, $project);
        $youtrack_fields = $this->get_custom_fields_details($youtrack_fields_list, $project, $customFieldDetails);
        return [$youtrack_fields_list, $youtrack_fields];
    }
   

    
    function getProjectsList(){
        global $youtrack_url;
        $url = $youtrack_url.'/rest/admin/project'; 
        $youtrack_projects_list_xml = $this->rest($url, 'get');
        $youtrack_projects_list = $this->extract_data_xml( $youtrack_projects_list_xml, 'project', 'id');
        return $youtrack_projects_list;
    }
    function getProjectAssignees($project){
        global $youtrack_url;
        $url = $youtrack_url.'/rest/admin/project/'.$project.'/assignee';
        $youtrack_project_assignees_xml = $this->rest($url, 'get');
        $youtrack_project_assignees = $this->extract_data_xml( $youtrack_project_assignees_xml, 'assignee', 'login');
        natcasesort($youtrack_project_assignees);
        return $youtrack_project_assignees;
    }
    
    function get_users(){
        global $youtrack_url;
        $user_list = [];
        $request_end = '';
        $loop = true;
        $users_no = 0;
        while( $loop == true ){
            $request_end = '?start='.$users_no;
            $url = $youtrack_url.'/rest/admin/user'.$request_end; 
            $user_list_xml = $this->rest($url, 'get');
            $new_user_list = $this->extract_data_xml( $user_list_xml, 'user', 'login');
            for($i=0;$i<count($new_user_list);$i++){
                $user_list[] = $new_user_list[$i];
            }
            $users_no += 10;
            if( count($new_user_list) === 0 ){
                $loop = false;
            }
        }
        return $user_list;
    }
    
    function getTicketSummary($ticket){
        global $youtrack_url;
        $ticketSummary = '';
        $url = $youtrack_url . '/rest/issue/'.$ticket;
        try {
            $ticketXml = $this->rest($url, 'get');
            $ticketSummary = $this->extract_data_xml( $ticketXml,'field', '', $whereAttr=['name'=>'summary']);
        } catch (Exception $e) {
            error_log($e);
        }
        return $ticketSummary;
    }
    function getTicketWorkTypes($project){
        global $youtrack_url;
        $worktypes = '';

        $url = $youtrack_url . '/rest/admin/project/'.$project.'/timetracking/worktype';
        try {
            $workTypeXml = $this->rest($url, 'get');
            $workTypes = $this->extract_data_xml($workTypeXml, 'name');
        } catch( Exception $e ){
            error_log($e);
        }
        return $workTypes;
    }

    
    
    
    /**
     * 
     * @global type $youtrack_url
     * @param type $projectId
     * @param type $query
     * @param type $maximumReturned maximum returned tickets
     */
    function getTicketsFromSearch($projectId,$query,$maximumReturned=100,$after=0){
        global $youtrack_url;
        $filter = urlencode('project:{'.$projectId.'} ');
        $url = $youtrack_url . '/rest/issue?filter='.$filter.$query.'&max='.$maximumReturned.'&after='.$after;
        try {
            $ticketXml = $this->rest($url, 'get');
            $explodedTicketXml = preg_split('/<\s*\/\s*issue\s*>/i',$ticketXml);
            for( $i=0; $i < count($explodedTicketXml); $i++ ){
                $explodedTicketXml[$i] = preg_replace('/<\s*\/*\s*issuecompacts\s*>/i', '', $explodedTicketXml[$i]);
                $explodedTicketXml[$i] = trim($explodedTicketXml[$i]);
                if(strlen($explodedTicketXml[$i])>0){
                    $explodedTicketXml[$i] = $explodedTicketXml[$i].'</issue>';
                    $ticketID = $this->extract_data_xml($explodedTicketXml[$i], 'issue', 'id');
                    $ticketSummary = $this->extract_data_xml($explodedTicketXml[$i], 'field','',['name'=>'summary']);
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
    * @global type $youtrack_url
    * @return type
    */
    function getLinkTypes(){
        global $youtrack_url;
        $url = $youtrack_url.'/rest/admin/issueLinkType';
        $youtrackLinkTypesXml = $this->rest($url, 'get');
        $youtrackLinkTypes = $this->extract_data_xml( $youtrackLinkTypesXml, 'issueLinkType', ['name','outwardName','inwardName']);
        return $youtrackLinkTypes;
    }
}