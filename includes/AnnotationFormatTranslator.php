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

  $shapes = $annotationData["shapes"];
  $target_source = $annotationData["src"];

  $target_source_relative_path = parse_url($target_source)["path"];
  $bodytext = $annotationData["text"];

  $target = array('source' => $target_source_relative_path, 'format' => 'image', 'selector' => array('shapes' => $shapes));

  $data["target"] = $target;
  $data["body"] = array('type' => 'TextualBody', 'bodytext' => $bodytext, 'format' => 'text/plain');

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

  $bodytext = $annotationData["text"];
  $media = $annotationData["media"];
  $rangeTime = $annotationData["rangeTime"];

  $target_org = $annotationData["target"];
  $target_source = $target_org["src"];

  $target = array('source' => $target_source, 'format' => $media, 'selector' => array('rangeTime' => $rangeTime));
  $data["target"] = $target;

  $data["body"] = array('type' => 'TextualBody', 'bodytext' => $bodytext, 'format' => 'text/html');

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
  $annoObject->text = $dsContentJson->body->bodytext;
  $annoObject->media = $dsContentJson->target->format;
  $annoObject->pid = $dsContentJson->{"@id"};
  $annoObject->creator = $dsContentJson->creator;
  $annoObject->created = $dsContentJson->created;

  if ($targetFormat == "video") {
    $annoObject->rangeTime = $dsContentJson->target->selector->rangeTime;
    $annoObject->user = $dsContentJson->creator;
    $target_source = $dsContentJson->target->source;
    $target = array('src' => $target_source, 'ext' => $target_source, 'container' => 'islandora_videojs');
    $annoObject->target = $target;
  } else if ($targetFormat == "image") {
    $annoObject->shapes = $dsContentJson->target->selector->shapes;
    $annoObject->src = $dsContentJson->target->source;
  }
  return $annoObject;
}
