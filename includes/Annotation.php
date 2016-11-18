<?php

/**
 * @file
 * Annotation implementation based on Web Annotation Protocol
 */

require_once('interfaceAnnotation.php');

class Annotation implements interfaceAnnotation
{


    public function createAnnotation($annotationContainerID, $annotationData){
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;

        try {
            $object = $repository->constructObject("islandora");
            $object->label = "sample title";
            $object->models = array('islandora:WADMCModel');
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'islandora:WADMCModel');
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfAnnotationContainer', $annotationContainerID);
            $dsid = 'WADM';

            $ds = $object->constructDatastream($dsid, 'M');
            $ds->label = $dsid;
            $ds->mimetype = 'application/ld+json';

            $test = $this->getAnnotationJsonLD($annotationData);

            $ds->setContentFromString($test);
            $object->ingestDatastream($ds);
            $repository->ingestObject($object);

            watchdog('islandora_web_annotations', 'Add new annotation: %t', array('%t' => $object->id), WATCHDOG_INFO);
        }
        catch (Exception $e) {
            watchdog('islandora_web_annotations', 'Error adding annotation object: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
        }

        return $object->id;
    }


    public function updateAnnotation($annotationData){

        $annotationPID = $annotationData["pid"];
        $updatedContent = $this->getAnnotationJsonLD($annotationData);

        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;
        $object = $repository->getObject($annotationPID);
        $WADMObject = $object->getDatastream("WADM");
        $WADMObject->content = $updatedContent;

        $output = array('status' => "Success", "msg"=> "annotation updated");
        $output = json_encode($output);
        return $output;
    }


    public function deleteAnnotation($annotationID){
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;
        $repository->purgeObject($annotationID);
    }


    public function getAnnotation($annotationID){


    }

    private function getAnnotationJsonLD($annotationData)
    {
        $data = array(
            "@context" => array("http://www.w3.org/ns/anno.jsonld"),
            "@id" => "sample id",
            "@type" => "Annotation",
            "data" => (object) $annotationData
        );
        $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $output;

    }

}