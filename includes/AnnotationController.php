<?php
/**
 * @file
 * Handles the hookmenu calls from the web annotation module
 */

require_once('Annotation.php');
require_once('AnnotationContainer.php');

function createAnnotation()
{
    $oAnnotationContainer = new AnnotationContainer();
    $output = $oAnnotationContainer->createAnnotation("test");
    drupal_json_output($output);
    drupal_exit();
}

function getAnnotationContainer()
{
    $oAnnotationContainer = new AnnotationContainer();
    $output = $oAnnotationContainer->getAnnotationContainer("test");
    drupal_json_output($output);
    drupal_exit();
}


function updateAnnotation()
{
    parse_str(file_get_contents("php://input"),$putVars);
    $annotationData = $putVars['annotationData'] ? $putVars['annotationData'] : '';

    $oAnnotation = new Annotation();
    $output = $oAnnotation->updateAnnotation($annotationData);
    drupal_json_output($output);
    drupal_exit();
}

function deleteAnnotation()
{
    parse_str(file_get_contents("php://input"),$deleteVars);
    $annotationID = $deleteVars['annotationID'] ? $deleteVars['annotationID'] : '';
    $annotationContainerID = $deleteVars['annotationContainerID'] ? $deleteVars['annotationContainerID'] : '';

    $oAnnotationContainer = new AnnotationContainer();
    $output = $oAnnotationContainer->deleteAnnotation($annotationContainerID, $annotationID);
    drupal_json_output($output);
    drupal_exit();
}