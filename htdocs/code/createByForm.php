<?php
/**
 * gets data for the createByForm page
 */
$getDataFromYoutrack = new getDataFromYoutrack;

$customFieldList = [];
$customFieldListFull = $getDataFromYoutrack->getCustomFields();
$key = array_search('Spent time', $customFieldListFull);
unset($customFieldListFull[$key]);
$customFieldListFull = array_merge($customFieldListFull);
foreach( $customFieldListFull as $customField ){
    if( !isset($createByFormSettings['IgnoreCustomFields']) || !in_array($customField, $createByFormSettings['IgnoreCustomFields']) ):
        array_push( $customFieldList, $customField);
    endif;
}
$projectList = $getDataFromYoutrack->getProjectsList();
$projectAssignees = $getDataFromYoutrack->getProjectAssignees($projectList[0]);
$customFieldTypeAndBundle = $getDataFromYoutrack->getCustomFieldTypeAndBundle($customFieldList, $projectList[0]);
$customFieldDetails = $getDataFromYoutrack->getCustomFieldsDetails($customFieldList, $projectList[0], $customFieldTypeAndBundle);
$linkTypes = $getDataFromYoutrack->getLinkTypes();