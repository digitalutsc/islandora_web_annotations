<?php

/**
 * @file
 * Annotation implementation based on Web Annotation Protocol
 */

require_once('AnnotationConstants.php');
require_once('AnnotationUtil.php');
require_once('interfaceAnnotation.php');
require_once('AnnotationFormatTranslator.php');
require_once('derivatives.inc');

class Annotation implements interfaceAnnotation
{
    var $repository;

    function __construct($i_repository) {
        if(isset($i_repository)  && $i_repository != null){
            $this->repository = $i_repository;
        } else {
            $connection = islandora_get_tuque_connection();
            $this->repository = $connection->repository;
        }
    }

    public function createAnnotation($annotationContainerID, $annotationData, $annotationMetadata){
        $annotation_namespace = variable_get('islandora_web_annotations_namespace', 'annotation');
        try {
            $object = $this->repository->constructObject($annotation_namespace);

            $target = $annotationData["context"];
            $target = str_replace("%3A",":",$target);
            $target = str_replace("#","",$target)
            ;
            $annotationData["context"] = $target;

            $targetPID = substr($target, strrpos($target, '/') + 1);

            $object->label = "Annotation for " . $targetPID;
            $object->models = array(AnnotationConstants::WADM_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', AnnotationConstants::WADM_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', AnnotationConstants::ANNOTATION_COLLECTION_NS);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfAnnotationContainer', $annotationContainerID);
            $dsid = AnnotationConstants::WADM_DSID;

            $annotationPID =  $object->id;
            $annotationData['pid'] = $annotationPID;

            $annotationJsonLDData = $this->getAnnotationJsonLD("create", $annotationData, $annotationMetadata);
            $annotationJsonLD = json_encode($annotationJsonLDData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $ds = $object->constructDatastream($dsid, 'M');
            $ds->label = $dsid;
            $ds->mimetype = AnnotationConstants::ANNOTATION_MIMETYPE;
            $ds->setContentFromString($annotationJsonLD);
            $object->ingestDatastream($ds);
            $this->repository->ingestObject($object);

            // Get WADM ds checksum
            $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
            $checksum =  $WADMObject->checksum;

            $annotationJsonLDData["checksum"] = $checksum;
            $annotationJsonLDData["pid"] = $annotationPID;
            $annotationJsonLD = json_encode($annotationJsonLDData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            watchdog(AnnotationConstants::MODULE_NAME , 'Annotation : createAnnotation: Added new annotation @annotationPID', array("@annotationPID" => $annotationPID), WATCHDOG_INFO);
        } catch (Exception $e) {
            watchdog(AnnotationConstants::MODULE_NAME, 'Error adding annotation object: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
            throw $e;
        }

        return array($annotationPID, $annotationJsonLD);
    }

    public function updateAnnotation($annotationData, $annotationMetadata){
        $annotationPID = $annotationData["pid"];

        $annotationJsonLDData = $this->getAnnotationJsonLD("update", $annotationData, $annotationMetadata);
        $updatedContent = json_encode($annotationJsonLDData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $object = $this->repository->getObject($annotationPID);
        $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
        $WADMObject->content = $updatedContent;

        // Get WADM ds checksum
        $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
        $checksum =  $WADMObject->checksum;
        $annotationJsonLDData["checksum"] = $checksum;

        // Update Derivative
        // islandora_web_annotations_create_wadm_derivative($object);
        return $annotationJsonLDData;
    }

    public function deleteAnnotation($annotationID){
        $this->repository->purgeObject($annotationID);
        watchdog(AnnotationConstants::MODULE_NAME, 'Annotation: deleteAnnotation: Annotation with id @annotationID was deleted from repoistory.', array('@annotationID'=>$annotationID), WATCHDOG_INFO);
    }

    /**
     * Gets the current checksum and compares it with the ETag checksum to check if the datastream has been alstered
     * @param $annotationID
     * @param $ETag
     * @return bool - If conflict exists, return true, else false
     */
    public function checkEditConflict($annotationID, $ETag){
        $object = $this->repository->getObject($annotationID);
        $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
        $checksum = $WADMObject->checksum;

        if (strcmp($checksum, $ETag) !== 0) {
            return false;
        } else {
            return true;
        }
    }

    private function getAnnotationJsonLD($actionType, $annotationData, $annotationMetadata)
    {
        $pid = isset($annotationData['pid']) ? $annotationData['pid'] : 'New';
        global $base_url;
        $annotationIRI = $base_url . "/islandora/object/" . $pid;

        $userURL = $base_url . "/users/" . $annotationMetadata["creator"];


        $data = array(
          "@context" => array(AnnotationConstants::ONTOLOGY_CONTEXT_ANNOTATION),
          "@id" => $annotationIRI,
          "@type" => AnnotationConstants::ANNOTATION_CLASS_1
        );

        if ($annotationMetadata["targetFormat"] == "image") {
          $data = convert_annotorious_to_W3C_annotation_datamodel($annotationData, $data);
        } elseif ($annotationMetadata["targetFormat"] == "video") {
          $data = convert_ova_to_W3C_annotation_datamodel($annotationData, $data);
        }

        $utc_now = AnnotationUtil::utcNow();
        if($actionType == "create") {
            $now = $utc_now;
            $metadata = array('creator' => $userURL, 'created' => $now);
        } elseif($actionType == "update") {
            $now = $utc_now;
            $metadata = array('creator' => $userURL, 'created' => $annotationMetadata["created"], 'modified' => $now);
        }

        $data = array_merge($data, $metadata);
        return $data;
    }
}