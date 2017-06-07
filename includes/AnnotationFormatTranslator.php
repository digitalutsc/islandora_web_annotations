<?php

/**
 * @file
 * Handles annotation data object data model format transformations.
 */

/**
 * Converts an annotorious annotation object into a W3C compliant data object.
 *
 * @param object $annotationData
 * @param object $data
 * @return object
 */
function convert_annotorious_to_W3C_annotation_datamodel($annotationData, $data) {

  // target
  $shapes = $annotationData["shapes"];
  $target_source = $annotationData["src"];

  $selector_ext_jsonld = "http://ontology.digitalscholarship.utsc.utoronto.ca/ns/anno_extension.jsonld";
  $target = array('@id' => $annotationData['context'], 'source' => $target_source, 'format' => 'image', 'selector' => array('@context' => $selector_ext_jsonld, 'shapes' => $shapes));
  $data["target"] = $target;

  // body
  $bodytext = $annotationData["text"];
  $data["body"] = array('@type' => 'TextualBody', 'bodyValue' => $bodytext, 'format' => 'text/plain');

  return $data;

}

/**
 * Converts an Open Video Annotation (annotator.js) annotation object into a W3C compliant data object.
 *
 * @param object $annotationData
 * @param object $data
 * @return object
 */

function convert_ova_to_W3C_annotation_datamodel($annotationData, $data) {

  // target
  $media = $annotationData["media"];
  $rangeTime = $annotationData["rangeTime"];
  $target_org = $annotationData["target"];
  $target_source = $target_org["src"];

  global $base_url;
  $target_source = $base_url . $target_source;

  $container = $target_org["container"];
  $selector_ext_jsonld = "http://ontology.digitalscholarship.utsc.utoronto.ca/ns/anno_extension.jsonld";
  $target = array('@id' => $annotationData['context'], 'source' => $target_source, 'format' => $media, 'selector' => array('@context' => $selector_ext_jsonld, 'rangeTime' => $rangeTime), 'container' => $container);
  $data["target"] = $target;

  // body
  $bodytext = $annotationData["text"];
  $data["body"] = array('@type' => 'TextualBody', 'bodyValue' => $bodytext, 'format' => 'text/html');

  return $data;
}

/**
 * Converts a W3C annotation data object into library specific (annotorious for image, ova for video) data object.
 *
 * @param $dsContentJson
 * @return \stdClass
 */
function conver_W3C_to_lib_annotation_datamodel($dsContentJson) {

  $targetFormat = $dsContentJson->target->format;

  $annoObject = new stdClass;
  $annoObject->text = $dsContentJson->body->bodyValue;
  $annoObject->media = $dsContentJson->target->format;
  $annoObject->pid = $dsContentJson->{"@id"};

  $userURL = $dsContentJson->creator;

  $userInfo = explode('/', $userURL);
  if(sizeof($userInfo) > 1) {
    $userName = $userInfo[sizeof($userInfo) - 1];
  } else {
    $userName = $userURL;
  }
  $annoObject->creator = $userName;
  $annoObject->created = $dsContentJson->created;

  if ((strpos($targetFormat, 'audio') !== false) || (strpos($targetFormat, 'video') !== false)) {
    $annoObject->rangeTime = $dsContentJson->target->selector->rangeTime;
    $annoObject->user = $userName;
    $target_source = $dsContentJson->target->source;
    $container = $dsContentJson->target->container;
    if ($container == null) {
      $container = "islandora_videojs";
    }
    $target = array('src' => $target_source, 'ext' => $target_source, 'container' => $container);
    $annoObject->target = $target;
  } else if (strpos($targetFormat, 'image') !== false) {
    $annoObject->shapes = $dsContentJson->target->selector->shapes;
    $annoObject->src = $dsContentJson->target->source;
  }
  return $annoObject;
}
