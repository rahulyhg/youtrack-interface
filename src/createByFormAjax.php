<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$project = htmlspecialchars($authenticationAndSecurity->getGet("project"));

if(!isset($customFieldList)){
    $customFieldList = '';
}

$customFieldTypeAndBundle = $getDataFromYoutrack->getCustomFieldTypeAndBundle($customFieldList, $project);
unset($customFieldTypeAndBundle['Spent time']);
$response = $customFieldTypeAndBundle;

$response['assignee']=[
    'fieldType'=>'enum[1]',
    'innerHtml'=>''
]; 
$projectAssignees = $getDataFromYoutrack->getProjectAssignees($project);
foreach( $projectAssignees as $assignee ){
    $response['assignee']['innerHtml'] .= '<option value="'.$assignee.'">'.$assignee.'</option>';
}

$customFieldDetails = $getDataFromYoutrack->get_custom_fields_details($customFieldList, $project, $customFieldTypeAndBundle);
foreach( $customFieldDetails as $key => $list ){
    if( gettype($list) == 'array'){
        $response[$key]['innerHtml'] = '<option value=""></option>'; 
        foreach( $customFieldDetails[$key] as $option ){ 
            $response[$key]['innerHtml'] .= '<option value="'.$option.'">'.$option.'</option>';
        }
    }
}

echo json_encode($response);