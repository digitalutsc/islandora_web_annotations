<?php
/**
 * @file
 * Create derivatives from WADM jsonld file.
 */


/**
 * Create WADM_SEARCH xml derivative for WADM jsonld.
 *
 * @param AbstractObject $object
 *   An AbstractObject representing an object within Fedora.
 *
 * @return array
 *   An array describing the outcome of the MP4 creation.
 *
 * @see hook_islandora_derivative()
 */
function islandora_web_annotations_create_wadm_derivative(AbstractObject $object){
  if ($object['WADM']) {
    try {
      $wadmJson = $object['WADM']->content;
      $wadmObj = json_decode($wadmJson, true);
      $contentXML = generateDerivativeContent($wadmObj);
      $result = createUpdateDerivative($object, $contentXML);
      return $result;
    } catch (exception $e) {
      watchdog(AnnotationConstants::MODULE_NAME, 'Unable to create WADM indexing datastream: ' . $e->getmessage());
    }
  } else {
    watchdog(AnnotationConstants::MODULE_NAME, 'Unable to create WADM indexing datastream: WADM datastream not found.');
  }
}

/*
 * Generate the xml.
 */
function generateDerivativeContent($wadmObj) {
  $targetURL = $wadmObj["@id"];
  $targetPID = AnnotationUtil::getPIDfromURL($targetURL);
  $textvalue = $wadmObj["body"]["bodyValue"];

  $creator = $wadmObj["creator"];
  $creator = substr($creator, strrpos($creator, '/') + 1);

  $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><annotation></annotation>');

  $xml->addChild('title', "Annotation for " . $targetPID);
  $xml->addChild('target', $targetPID);
  $xml->addChild('creator', $creator);
  addCDATAchild('textvalue', $textvalue, $xml);

  if ($wadmObj["target"]["format"] == "video") {
    $rangeTimeStart = $wadmObj["target"]["selector"]["rangeTime"]["start"];
    $xml->addChild('rangeTimeStart', $rangeTimeStart);
    $rangeTimeEnd = $wadmObj["target"]["selector"]["rangeTime"]["end"];
    $xml->addChild('rangeTimeEnd', $rangeTimeEnd);
  }

  $contentXML = $xml->asXML();
  return $contentXML;

}

/*
 * Create the WADM_SEARCH datastream.
 */
function createUpdateDerivative(AbstractObject $object, $contentXML){
  $dsid = 'WADM_SEARCH';
  $datastream = isset($object[$dsid]) ? $object[$dsid] : $object->constructDatastream($dsid);
  $datastream->label = 'WADM_SEARCH';
  $datastream->mimeType = 'text/xml';

  $filename = $dsid . '.xml';
  $dest = file_build_uri($filename);
  $file = file_save_data($contentXML, $dest, FILE_EXISTS_REPLACE);
  $result = AnnotationUtil::add_datastream($object, $dsid, $file->uri);
  file_delete($file);
  return $result;
}

/**
 * Adds a CDATA property to an XML document.
 *
 * @param string $name
 *   Name of property that should contain CDATA.
 * @param string $value
 *   Value that should be inserted into a CDATA child.
 * @param object $parent
 *   Element that the CDATA child should be attached too.
 */
function addCDATAchild($name, $value, &$parent) {
  $child = $parent->addChild($name);

  if ($child !== NULL) {
    $child_node = dom_import_simplexml($child);
    $child_owner = $child_node->ownerDocument;
    $child_node->appendChild($child_owner->createCDATASection($value));
  }
  return $child;
}


