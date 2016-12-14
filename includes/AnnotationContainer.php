<?php

/**
 * @file
 * AnnotaionContainer implementation based on Web Annotation Protocol
 */

require_once('AnnotationConstants.php');
require_once('AnnotationUtil.php');
require_once('interfaceAnnotationContainer.php');
require_once('Annotation.php');
module_load_include('inc', 'islandora', 'includes/solution_packs');


class AnnotationContainer implements interfaceAnnotationContainer
{
    var $repository;

    function __construct() {
        $connection = islandora_get_tuque_connection();
        $this->repository = $connection->repository;
    }

    /**
     *
     * @param $annotationData
     * @return none
     */
    public function createAnnotationContainer($targetObjectID, $annotationData){

        try {
            $target = $annotationData["context"];
            $object = $this->repository->constructObject("islandora");
            $object->label = "AnnotationContainer for " . $targetObjectID;
            $object->models = array(AnnotationConstants::WADMContainer_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', AnnotationConstants::WADMContainer_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isAnnotationContainerOf', $targetObjectID);
            $dsid = AnnotationConstants::WADMContainer_DSID;

            $ds = $object->constructDatastream($dsid, 'M');
            $ds->label = $dsid;
            $ds->mimetype = AnnotationConstants::ANNOTATION_MIMETYPE;

            $targetPageID = $target . "/annotations/?page=0";
            $test = $this->getAnnotationContainerJsonLD($targetObjectID, $targetPageID);
            $ds->setContentFromString($test);
            $object->ingestDatastream($ds);
            $this->repository->ingestObject($object);

            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : createAnnotationContainer: created a new annotationContainer: %t', array('%t' => $object->id), WATCHDOG_INFO);
        } catch (Exception $e) {
            watchdog(AnnotationConstants::MODULE_NAME, 'Error adding annotation container object: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
            throw $e;
        }

        return $object->id;
    }

    public function getAnnotationContainer($targetObjectID){

        $annotationContainerID = $this->getAnnotationContainerPID($targetObjectID);

        // If Container does not exist, respond with that message
        if($annotationContainerID == "None"){
            $response = array('status' => "Success", "msg" => "Annotation Container does not exist for " . $targetObjectID);
            $response = json_encode($response);
            return $response;
        } else {

            // Get Datastream content
            $object = $this->repository->getObject($annotationContainerID);
            $WADMObject = $object->getDatastream(AnnotationConstants::WADMContainer_DSID);
            $content = (string)$WADMObject->content;
            $contentJson = json_decode($content, true);
            $items = $contentJson["first"]["items"];

            // Loop through each item and get the item info
            $newArray = array();
            for($i = 0; $i < count($items); $i++) {
                $object = $this->repository->getObject($items[$i]);
                $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
                $dsContent = (string)$WADMObject->content;
                $checksum = $WADMObject->checksum;

                if($dsContent != "NotFound") {
                    $dsContentJson = json_decode($dsContent);
                    $body = $dsContentJson->body;
                    $body->pid = $items[$i];
                    $body->creator = $dsContentJson->creator;
                    $body->created = $dsContentJson->created;
                    $body->checksum = $checksum;
                    array_push($newArray, $body);
                }
            }

            $totalItems = count($items);
            $contentJson["first"]["items"] = $newArray;
            $contentJson["total"] = count($items);
            $contentJson["@id"] = $annotationContainerID;

            $annotationContainerWithItems  = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : getAnnotationContainer: Returning @totalItems annotations  for @targetObjectID', array('@totalItems' => $totalItems, '@targetObjectID'=> $targetObjectID), WATCHDOG_INFO);

            return $annotationContainerWithItems;
        }
    }

    public function deleteAnnotationContainer($annotationContainerID){
        $connection = islandora_get_tuque_connection();
        $repository = $connection->repository;
        $repository->purgeObject($annotationContainerID);
    }

    /**
     * Creates a new annotation and annotaion container if needed
     * Aims to conform to : https://www.w3.org/TR/annotation-protocol/#create-a-new-annotation
     *
     * @param $targetObjectID
     * @param $annotationData
     * @return jsonld of the annotation that was created
     */
    public function createAnnotation($targetObjectID, $annotationData, $annotationMetadata){
        // If annotationContainer does not exist, create the container
        $annotationContainerID = $this->getAnnotationContainerPID($targetObjectID);
        if($annotationContainerID == "None"){
            $annotationContainerID = $this->createAnnotationContainer($targetObjectID, $annotationData);
        } else {
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer: createAnnotation: Annotation container already exists: @$annotationContainerID', array('@annotationContainerID'=>$annotationContainerID), WATCHDOG_INFO);
        }

        // Add annotation
        $oAnnotation = new Annotation($this->repository);
        $annotationInfo = $oAnnotation->createAnnotation($annotationContainerID, $annotationData, $annotationMetadata);

        // Update the container
        $this->addContainerItem($annotationContainerID, $annotationInfo[0]);

        return $annotationInfo[1];
    }

    public function deleteAnnotation($annotationContainerID, $annotationID){
        // Get Datastream content
        $object = $this->repository->getObject($annotationContainerID);
        $WADMObject = $object->getDatastream(AnnotationConstants::WADMContainer_DSID);
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

        watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer: deleteAnnotation: Annotation with id @annotationID was removed from annotationContainer with id @annotationContainerID', array('@annotationID'=>$annotationID, '@annotationContainerID'=>$annotationContainerID), WATCHDOG_INFO);

        // Delete the object
        $oAnnotation = new Annotation();
        $oAnnotation->deleteAnnotation($annotationID);

        // Return message
        $output = array('status' => "success", "data"=> "The following annotation was deleted: " . $annotationID);
        $output = json_encode($output);

        return $output;
    }

    /**
     * Get annotation container datastream
     * Add annotationpid to the items
     *
     * @param $annotationContainerID
     * @param $annotationPID
     */
    private function addContainerItem($annotationContainerID, $annotationPID)
    {
        try {
            // Get Datastream content
            $object = $this->repository->getObject($annotationContainerID);
            $WADMObject = $object->getDatastream(AnnotationConstants::WADMContainer_DSID);
            $content = (string)$WADMObject->content;
            $contentJson = json_decode($content, true);

            // Update the Datastream content
            $items = $contentJson["first"]["items"];
            array_push($items, $annotationPID);
            $contentJson["first"]["items"] = $items;
            $newContent = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $WADMObject->content = $newContent;

            watchdog(AnnotationConstants::MODULE_NAME , 'AnnotationContainer : addContainerItem: Added the following item to the container: @annotationContainerID' , array("@annotationContainerID" => $annotationContainerID), WATCHDOG_INFO);
        } catch(Exception $e){
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : addContainerItem: Failed to add new item to the container: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
            throw $e;
        }


    }

    private function getAnnotationContainerJsonLD($targetObjectID, $targetPageID)
    {
        $containerID = AnnotationUtil::generateUUID();

        $data = array(
            "@context" => array(AnnotationConstants::ONTOLOGY_CONTEXT_ANNOTATION, AnnotationConstants::ONTOLOGY_CONTEXT_LDP),
            "@id" => $containerID,
            "@type" => array("BasicContainer", "AnnotationCollection"),
            "total" => "0",
            "label" => "annotationContainer for " . $targetObjectID,
            "first" => (object) array("id" => $targetPageID, "type" => AnnotationConstants::ANNOTATION_CLASS_2, "items" => array())
        );
        $annotationContainerJsonLD = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $annotationContainerJsonLD;

    }

    /**
     * Searches the Solr to see if the object already has an annotation container.  If so, it returns the annotation container PID.
     *
     * @param $objectPID
     * @return string
     */
    private function getAnnotationContainerPID($objectPID)
    {
        $url = parse_url(variable_get('islandora_solr_url', 'localhost:8080/solr'));

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
        } catch (Exception $e) {
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : getAnnotationContainerPID: Got an exception while quering Solr: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
            throw $e;
        }

        $annotationConatinerPID = "None";
        if(count($json['response']['docs']) >= 1) {
            $annotationConatinerPID = $json['response']['docs'][0]["PID"];
        }

        $responseInfo = print_r($json['response'], true);
        watchdog(AnnotationConstants::MODULE_NAME , 'AnnotationContainer : getAnnotationContainerPID: @responseInfo' , array("@responseInfo" => $responseInfo), WATCHDOG_INFO);

        return $annotationConatinerPID;
    }
}