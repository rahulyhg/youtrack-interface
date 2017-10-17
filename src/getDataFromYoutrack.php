<?php

namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/getCustomSettings.php';


use Guzzle\Http\Client;

/**
 * Class getDataFromYoutrack get/post data from Youtrack api.
 */
class getDataFromYoutrack
{
    /**
     * send request to Youtrack api, dosnt check the cache. Use the rest function in this class instead.
     *
     * @param string $url       request url
     * @param string $postOrGet get|post|put request type
     * @param array  $headers
     * @param string $body
     * @param array  $options
     *
     * @return object request object
     */
    public function restResponse($url, $postOrGet = 'get', $headers = null, $body = null, $options = null)
    {
        $client = new Client();
        $authenticationAndSecurity = new authenticationAndSecurity();
        $authentication = $authenticationAndSecurity->getAuthentication();
        if ($authentication['type'] !== 'password' && $authentication['type'] !== 'cookie' && $authentication['type'] !== 'file') {
            echo 'authentication type unknown. please check its set in the customSettings.php file';
            return;
        }
        if (!isset($options)) {
            if ($authentication['type'] === 'password') {
                $options = ['auth' => [$authentication['details']['user'], $authentication['details']['password']]];
            } else {
                $options = [];
            }
        }
        if ($postOrGet === 'get') {
            $request = $client->get($url, $headers, $options);
        } elseif ($postOrGet === 'post') {
            $request = $client->post($url, $headers, $body, $options);
        } elseif ($postOrGet === 'put') {
            $request = $client->put($url, $headers, $body, $options);
        }else{
            error_log("Caught exception: rest request type not allowed ".__FILE__.': '.__LINE__, 0);
            return;
        }
        if ($authentication['type'] === 'cookie' && $authentication['details']) {
            foreach ($authentication['details'] as $singleCookie) {
                foreach ($singleCookie as $cookieName => $cookieValue) {
                    $request->addCookie($cookieName, $cookieValue);
                }
            }
        }
        try {
            $request->send();
        }  catch (Exception $e) {
            error_log("Caught exception: error sending request, ".$e->getMessage(), 0);
            return;
        }

        return $request;
    }

    /**
     * @param $url string
     * @param $postOrGet string
     * @param $cachable bool
     * @return bool|string
     */
    function getCached($url, $postOrGet = 'get', $cachable = true){
        if ($cachable == true) {
            if ($GLOBALS['cache'] && $postOrGet == 'get') {
                $cacheClass = new cache();
               return $cacheClass->getCached($url);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * returns response from Youtrack api or from cache if cache-able abd available.
     *
     * @param string $url       request url
     * @param string $postOrGet get|post|put request type
     * @param null   $headers
     * @param null   $body
     * @param null   $options
     * @param bool   $cachable  should the response be cached
     *
     * @return string|false response from request
     */
    public function rest($url, $postOrGet = 'get', $headers = null, $body = null, $options = null, $cachable = true)
    {
        $cached = $this->getCached($url, $postOrGet, $cachable);
        if ($cached) {
            return $cached;
        }
        $res = $this->restResponse($url, $postOrGet, $headers, $body, $options);
        if (!$res) {
            return false;
        }
        $res = $res->getResponse();
        $response = trim($res->getBody());
        if ($cachable && isset($cacheClass)) {
            $cacheClass->createCache($url, $response);
        }
        return $response;
    }

    /**
     * extract and return data from xml.
     *
     * @param string $xml       the xml
     * @param string $node      the name of the node
     * @param string $attribute attribute on node to return, if empty string node value is used
     * @param array  $whereAttr array of required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]
     *
     * @return array data requested
     */
    public function extractDataXml($xml, $node, $attribute = '', $whereAttr = [])
    {
        $returnData = [];
        $reader = new \XMLReader();
        $reader->xml($xml);
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT) {
                $exp = $reader->expand();
                if ($exp->nodeName == $node) {
                    $data = $this->extractDataXmlUseNode($exp, $attribute, $whereAttr);
                    if ($data) {
                        $returnData[] = $data;
                    }
                }
            }
        }

        return $returnData;
    }
    /**
     * extract data from xml node.
     *
     * @param DOMNode      $exp       xml node
     * @param array|string $attribute
     * @param array        $whereAttr required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]
     *
     * @return array|string
     */
    public function extractDataXmlUseNode($exp, $attribute = '', $whereAttr = [])
    {
        if (count($whereAttr) > 0) {
            $continue = false;
            foreach ($whereAttr as $attr => $val) {
                if ($exp->getAttribute($attr) == $val) {
                    $continue = true;
                }
            }
        } else {
            $continue = true;
        }
        if ($continue) {
            if (is_array($attribute)) {
                foreach ($attribute as $singleAttribute) {
                    $attributeData[$singleAttribute] = $exp->getAttribute($singleAttribute);
                }

                return $attributeData;
            } else {
                return ($attribute) ? $exp->getAttribute($attribute) : $exp->nodeValue;
            }
        }
    }

    /**
     * get list of the custom youtrack fields.
     *
     * @param string $project project ref
     *
     * @return array
     */
    public function getCustomFields($project = '')
    {
        global $youtrackUrl;
        if ($project == '') {
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $url = $youtrackUrl.'/rest/admin/project/'.$project.'/customfield';
        $youtrackProjectCustomfieldsXml = $this->rest($url, 'get');
        $youtrackProjectCustomfields = $this->extractDataXml($youtrackProjectCustomfieldsXml, 'projectCustomField', 'name');
        $key = array_search('Assignee', $youtrackProjectCustomfields);
        if ($key !== false) {
            unset($youtrackProjectCustomfields[$key]); // Assignee is not a custom field
        }

        return $youtrackProjectCustomfields;
    }
    /**
     * get the custom field type and its bundle name. bundles are the list of options for the select and multi-selects.
     *
     * @param array  $youtrackFieldsList lst of youtrack fields
     * @param string $project            project reference
     *
     * @return array
     */
    public function getCustomFieldTypeAndBundle($youtrackFieldsList = [], $project = '')
    {
        global $youtrackUrl;
        if ($project == '') {
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if (count($youtrackFieldsList)) {
            $youtrackFieldsList = $this->getCustomFields($project);
        }
        foreach ($youtrackFieldsList as $field) {
            $url = $youtrackUrl.'/rest/admin/project/'.$project.'/customfield/'.$field;
            $customField = $this->rest($url, 'get');
            $CustomFieldtypeArray = $this->extractDataXml($customField, 'projectCustomField', 'type');
            $customFieldSettings[$field]['fieldType'] = $CustomFieldtypeArray[0];
            // if dropdown field
            if (strpos($CustomFieldtypeArray[0], '[') !== false) {
                $bundle = $this->extractDataXml($customField, 'param', 'value', ['name' => 'bundle']);
                $customFieldSettings[$field]['bundle'] = $bundle[0];
            } else {
                $customFieldSettings[$field]['bundle'] = '';
            }
        }

        return (isset($customFieldSettings)) ? $customFieldSettings : [];
    }
    /*
     * $customFieldTypeAndBundle array from getCustomFieldTypeAndBundle
     */
    /**
     * get the options for the custom field.
     *
     * @param array  $youtrackFieldsList
     * @param string $project
     * @param array  $customFieldTypeAndBundle
     *
     * @return array
     */
    public function getCustomFieldsDetails($youtrackFieldsList = [], $project = '', $customFieldTypeAndBundle = [])
    {
        global $youtrackUrl;
        if ($project == '') {
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if (count($youtrackFieldsList)) {
            $youtrackFieldsList = $this->getCustomFields($project);
        }
        if (count($customFieldTypeAndBundle)) {
            $customFieldTypeAndBundle = $this->getCustomFieldTypeAndBundle($youtrackFieldsList, $project);
        }

        foreach ($youtrackFieldsList as $field) {
            // if dropdown field
            if (isset($customFieldTypeAndBundle[$field]) && strpos($customFieldTypeAndBundle[$field]['fieldType'], '[') !== false) {
                $fieldTypeShort = explode('[', $customFieldTypeAndBundle[$field]['fieldType']) [0];
                $fieldTypeShort = strtolower($fieldTypeShort);
                if ($fieldTypeShort == 'enum') {
                    $url = $youtrackUrl.'/rest/admin/customfield/bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml($bundleXml, 'value');
                } elseif ($fieldTypeShort == 'ownedfield') {
                    $url = $youtrackUrl.'/rest/admin/customfield/ownedFieldBundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml($bundleXml, 'ownedField');
                } else {
                    $url = $youtrackUrl.'/rest/admin/customfield/'.$fieldTypeShort.'Bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    $youtrackFields[$field] = $this->extractDataXml($bundleXml, $fieldTypeShort);
                }
            } else {
                $youtrackFields[$field] = '';
            }
        }

        return $youtrackFields;
    }
    /**
     * get array of the custom fields with their selection options where appropriate and other data.
     *
     * @param string $project project reference
     *
     * @return array [ array of field names, array of fields data ]
     */
    public function getCustomFieldsWithDetails($project = '')
    {
        if ($project == '') {
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $youtrackFieldsList = $this->getCustomFields($project);
        $customFieldDetails = $this->getCustomFieldTypeAndBundle($youtrackFieldsList, $project);
        $youtrackFields = $this->getCustomFieldsDetails($youtrackFieldsList, $project, $customFieldDetails);

        return [$youtrackFieldsList, $youtrackFields];
    }

    /**
     * get a list of projects in youtrack.
     *
     * @return array of project references
     */
    public function getProjectsList()
    {
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/project';
        $youtrackProjectsListXml = $this->rest($url, 'get');
        $youtrackProjectsList = $this->extractDataXml($youtrackProjectsListXml, 'project', 'id');
        natcasesort($youtrackProjectsList);
        return $youtrackProjectsList;
    }
    /**
     * get assignees on given project.
     *
     * @param string $project project reference
     *
     * @return array assignee references
     */
    public function getProjectAssignees($project)
    {
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/project/'.$project.'/assignee';
        $youtrackProjectAssigneesXml = $this->rest($url, 'get');
        $youtrackProjectAssignees = $this->extractDataXml($youtrackProjectAssigneesXml, 'assignee', 'login');
        natcasesort($youtrackProjectAssignees);

        return $youtrackProjectAssignees;
    }

    /**
     * get an array of users from youtrack.
     *
     * @return array
     */
    public function getUsers()
    {
        global $youtrackUrl;
        $userList = [];
        $loop = true;
        $usersNo = 0;
        while ($loop == true) {
            $requestEnd = '?start='.$usersNo;
            $url = $youtrackUrl.'/rest/admin/user'.$requestEnd;
            $userListXml = $this->rest($url, 'get');
            $newUserList = $this->extractDataXml($userListXml, 'user', 'login');
            for ($i = 0; $i < count($newUserList); ++$i) {
                $userList[] = $newUserList[$i];
            }
            $usersNo += 10;
            if (count($newUserList) === 0) {
                $loop = false;
            }
        }

        return $userList;
    }

    /**
     * get the summary of a ticket.
     *
     * @param string $ticket ticket reference
     *
     * @return string
     */
    public function getTicketSummary($ticket)
    {
        global $youtrackUrl;
        $ticketSummary = '';
        $url = $youtrackUrl.'/rest/issue/'.$ticket;
        try {
            $ticketXml = $this->rest($url, 'get');
            $ticketSummary = $this->extractDataXml($ticketXml, 'field', '', $whereAttr = ['name' => 'summary']);
        } catch (Exception $e) {
            error_log($e);
        }

        return $ticketSummary;
    }
    /**
     * get ticket work types.
     *
     * @param string $project project reference
     *
     * @return array
     */
    public function getTicketWorkTypes($project)
    {
        global $youtrackUrl;
        $workTypes = [];

        $url = $youtrackUrl.'/rest/admin/project/'.$project.'/timetracking/worktype';
        try {
            $workTypesData= json_decode($this->rest($url, 'get'));
            for ($i=0; $i<count($workTypesData); $i++){
                $workTypes[] = $workTypesData[$i]->name;
            }
        } catch (Exception $e) {
           error_log($e);
        }

        return $workTypes;
    }

    /**
     * search for youtrack tickets.
     *
     * @param string $projectId       project reference
     * @param string $query           search query
     * @param int    $maximumReturned maximum returned tickets
     * @param int    $after           show results starting after this amount
     *
     * @return array [ tickets data, is it a partial set? ]
     */
    public function getTicketsFromSearch($projectId, $query, $maximumReturned = 100, $after = 0)
    {
        global $youtrackUrl;
        $filter = urlencode('project:{'.$projectId.'} ');
        $url = $youtrackUrl.'/rest/issue?filter='.$filter.$query.'&max='.$maximumReturned.'&after='.$after;
        try {
            $ticketXml = $this->rest($url, 'get');
            $explodedTicketXml = preg_split('/<\s*\/\s*issue\s*>/i', $ticketXml);
            for ($i = 0; $i < count($explodedTicketXml); ++$i) {
                $explodedTicketXml[$i] = preg_replace('/<\s*\/*\s*issuecompacts\s*>/i', '', $explodedTicketXml[$i]);
                $explodedTicketXml[$i] = trim($explodedTicketXml[$i]);
                if (strlen($explodedTicketXml[$i]) > 0) {
                    $explodedTicketXml[$i] = $explodedTicketXml[$i].'</issue>';
                    $ticketID = $this->extractDataXml($explodedTicketXml[$i], 'issue', 'id');
                    $ticketSummary = $this->extractDataXml($explodedTicketXml[$i], 'field', '', ['name' => 'summary']);
                    $tickets[$ticketID[0]] = $ticketSummary[0];
                }
            }
        } catch (Exception $e) {
            error_log($e);
        }

        $partialSet = ($maximumReturned < count($explodedTicketXml)) ? true : false;

        return ['tickets' => $tickets, 'partialSet' => $partialSet];
    }

    /**
     * get list of ticket links types e.g. "depends".
     *
     * @return array
     */
    public function getLinkTypes()
    {
        global $youtrackUrl;
        $url = $youtrackUrl.'/rest/admin/issueLinkType';
        $youtrackLinkTypesXml = $this->rest($url, 'get');
        $youtrackLinkTypes = $this->extractDataXml($youtrackLinkTypesXml, 'issueLinkType', ['name', 'outwardName', 'inwardName']);

        return $youtrackLinkTypes;
    }
}
