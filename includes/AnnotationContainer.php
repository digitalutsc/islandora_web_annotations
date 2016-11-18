<?php

/**
 * @file
 * AnnotaionContainer implementation based on Web Annotation Protocol
 */

require_once('interfaceAnnotationContainer.php');
require_once('Annotation.php');
module_load_include('inc', 'islandora', 'includes/solution_packs');


class AnnotationContainer implements interfaceAnnotationContainer
{
    public function createAnnotation($annotationID){
        $targetObjectID = isset($_POST['targetPid']) ? $_POST['targetPid'] : '';
        $annotationData =  isset($_POST['annotationData']) ? $_POST['annotationData'] : '';


        // If annotationContainer does not exist, create the container
        $annotationContainerID = $this->getAnnotationContainerPID($targetObjectID);
        if($annotationContainerID == "None"){
            $annotationContainerID = $this->createAnnotationContainer($targetObjectID);
            watchdog('islandora_web_annotations', 'Annotation container created ' . $annotationContainerID);
        }
        else {
            watchdog('islandora_web_annotations', 'Annotation container already exists ' . $annotationContainerID);
        }

        // Add annotation
        $oAnnotation = new Annotation();
        $annotationPID = $oAnnotation->createAnnotation($annotationContainerID, $annotationData);

        // Update Container
        $this->addContainerItem($annotationContainerID, $annotationPID);

        //ToDo: Have to error check
        $output = array('status' => "Success", "msg"=> "annotation created", "$annotationContainerID" => $annotationContainerID);
        $output = json_encode($output);
        return $output;

    }

    public function deleteAnnotation($annotationContainerID, $annotationID){

        // Remove it from container
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;

        // Get Datastream content
        $object = $repository->getObject($annotationContainerID);
        $WADMObject = $object->getDatastream("WADMContainer");
        $content = (string)$WADMObject->content;
        $contentJson = json_decode($content, true);
        $items = $contentJson["first"]["items"];


        // Remove the annotation from items
        if(($key = array_search($annotationID, $items)) !== false) {
            unset($items[$key]);
        }
        $items = array_values($items);

        // Update the datastream
        $contentJson["first"]["items"] = $items;
        $updatedContent = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $WADMObject->content = $updatedContent;


        watchdog('islandora_web_annotations', 'Deleted ' . $annotationID . "from the annotation container: " . $annotationContainerID);

        // Delete the object
        // Add annotation
        $oAnnotation = new Annotation();
        $annotationPID = $oAnnotation->deleteAnnotation($annotationID);

        // Return message
        $output = array('status' => "Success", "msg"=> "The following annotation was deleted: " . $annotationID);
        $output = json_encode($output);

        return $output;

    }

    public function createAnnotationContainer($targetObjectID){

        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;

        try {

            $object = $repository->constructObject("islandora");
            $object->label = "sample title";
            $object->models = array('islandora:WADMContainerCModel');
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'islandora:WADMContainerCModel');
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isAnnotationContainerOf', $targetObjectID);
            $dsid = 'WADMContainer';

            $ds = $object->constructDatastream($dsid, 'M');
            $ds->label = $dsid;
            $ds->mimetype = 'application/ld+json';

            $test = $this->getAnnotationContainerJsonLD("example annotation container");

            $ds->setContentFromString($test);
            $object->ingestDatastream($ds);
            $repository->ingestObject($object);

            watchdog('islandora_web_annotations', 'Add new annotation container: %t', array('%t' => $object->id), WATCHDOG_INFO);
        }
        catch (Exception $e) {
            watchdog('islandora_web_annotations', 'Error adding annotation container object: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
        }

        return $object->id;
    }

    public function deleteAnnotationContainer($annotationContainerID){
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;
        $repository->purgeObject($annotationContainerID);
    }

    public function getAnnotationContainer($objectID){
        $targetObjectID = isset($_GET['targetPid']) ? $_GET['targetPid'] : '';
        $annotationContainerID = $this->getAnnotationContainerPID($targetObjectID);

        // If annotationContainer does not exist, create the container
        if($annotationContainerID == "None"){
            $output = array('status' => "Success", "annotationContainerPID" => $annotationContainerID, "targetObjectID" => $targetObjectID);
            $output = json_encode($output);
            return $output;
        }
        else {

            $connection = islandora_get_tuque_connection();
            $repository = $connection->repository;

            // Get Datastream content
            $object = $repository->getObject($annotationContainerID);
            $WADMObject = $object->getDatastream("WADMContainer");
            $content = (string)$WADMObject->content;
            $contentJson = json_decode($content, true);
            $items = $contentJson["first"]["items"];

            $newArray = array();
            for($i = 0; $i < count($items); $i++) {

                $dsContent = $this->getDatastreamContent($repository, $items[$i], "WADM");
                if($dsContent != "NotFound")
                {
                    $dsContentJson = json_decode($dsContent);
                    $data = $dsContentJson->data;
                    $data->pid = $items[$i];
                    array_push($newArray, $data);
                }
            }

            $contentJson["first"]["items"] = $newArray;
            $contentJson["@id"] = $annotationContainerID;

            $annotationContainerWithItems  = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            watchdog('islandora_web_annotations', 'getAnnotationContainer json' . $annotationContainerWithItems);
            return $annotationContainerWithItems;
        }
    }

    private function addContainerItem($annotationContainerID, $annotationPID)
    {
        // Get annotation container
        // Add annotationpid to the items
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;

        // Get Datastream content
        $object = $repository->getObject($annotationContainerID);
        $WADMObject = $object->getDatastream("WADMContainer");
        $content = (string)$WADMObject->content;
        $contentJson = json_decode($content, true);

        // Update the Datastream content
        $items = $contentJson["first"]["items"];
        array_push($items, $annotationPID);
        $contentJson["first"]["items"] = $items;
        $newContent = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $WADMObject->content = $newContent;

        watchdog('islandora_web_annotations', 'addContainerItem' . $newContent);
    }

    private function getDatastreamContent($repository, $pid, $datastreamID){
        try{
            $object = $repository->getObject($pid);
            $WADMObject = $object->getDatastream($datastreamID);
            $content = (string)$WADMObject->content;

            watchdog('islandora_web_annotations', $pid . 'ds content' . $content);
            return $content;
        }
        catch (Exception $e) {
            watchdog('islandora_web_annotations', 'ds content not found for ' . $pid);
            return "NofFound";
        }

    }

    private function getAnnotationContainerJsonLD($containerID)
    {
        $data = array(
            "@context" => array("http://www.w3.org/ns/anno.jsonld", "http://www.w3.org/ns/ldp.jsonld"),
            "@id" => $containerID,
            "@type" => array("BasicContainer", "AnnotationCollection"),
            "total" => "0",
            "label" => "A Container for Web Annotations",
            "first" => (object) array("id" => $containerID, "type" => "AnnotationPage", "items" => array())
        );
        $annotationContainerJsonLD = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $annotationContainerJsonLD;

    }

    private function getAnnotationContainerPID($objectPID)
    {

        $url = parse_url('localhost:8080/solr');

        $qualifier = 'RELS_EXT_isAnnotationContainerOf_uri_s:' . '"' . "info:fedora/" . $objectPID . '"';
        $query = "$qualifier";
        $fields = array('PID');


        $solr = new Apache_Solr_Service($url['host'], $url['port'], $url['path'] . '/');
        $solr->setCreateDocuments(FALSE);

        $params = array(
            'fl' => $fields,
            'qt' => 'standard',
        );


        try {
            $results = $solr->search($query, 0, 1000, $params);
            $json = json_decode($results->getRawResponse(), TRUE);
        }
        catch (Exception $e) {
            watchdog_exception('Islandora Oralhistories', $e, 'Got an exception while searching transcripts for callback.', array(), WATCHDOG_ERROR);
        }

        $annotationConatinerPID = "None";
        if(count($json['response']['docs']) >= 1)
        {
            $annotationConatinerPID = $json['response']['docs'][0]["PID"];
        }

        $dump = print_r($json['response'], true);

        watchdog('islandora_web_annotations', 'Result from solr search: ' . $dump);
        return $annotationConatinerPID;
    }
}