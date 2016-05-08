<?php
$getDataFromYoutrack = new getDataFromYoutrack;

$customFieldList = [];
$customFieldListFull = $getDataFromYoutrack->get_custom_fields();
$key = array_search('Spent time', $customFieldListFull);
unset($customFieldListFull[$key]);
foreach( $customFieldListFull as $customField ):
    if( !isset($createByFormSettings['IgnoreCustomFields']) || !in_array($customField, $createByFormSettings['IgnoreCustomFields']) ):
        array_push( $customFieldList, $customField);
    endif;   
endforeach;
$projectList = $getDataFromYoutrack->getProjectsList();
$projectAssignees = $getDataFromYoutrack->getProjectAssignees($projectList[0]);
$customFieldTypeAndBundle = $getDataFromYoutrack->getCustomFieldTypeAndBundle($customFieldList, $projectList[0]);
$customFieldDetails = $getDataFromYoutrack->get_custom_fields_details($customFieldList, $projectList[0], $customFieldTypeAndBundle);