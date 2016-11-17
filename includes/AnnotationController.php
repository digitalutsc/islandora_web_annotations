<?php
/**
 * Created by PhpStorm.
 * User: nat
 * Date: 15/11/16
 * Time: 1:50 PM
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