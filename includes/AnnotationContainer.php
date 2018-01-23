<?php

/**
 * @file
 * AnnotaionContainer implementation based on Web Annotation Protocol
 */

require_once(__DIR__ . '/AnnotationConstants.php');
require_once(__DIR__ . '/AnnotationUtil.php');
require_once(__DIR__ . '/interfaceAnnotationContainer.php');
require_once(__DIR__ . '/Annotation.php');
require_once(__DIR__ . '/AnnotationContainerTracker.php');
require_once(__DIR__ . '/AnnotationFormatTranslator.php');
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
      $annotation_namespace = variable_get('islandora_web_annotations_namespace', 'annotation');
        try {
            $target = $annotationData["context"];
            $object = $this->repository->constructObject($annotation_namespace);
            $object->label = "AnnotationContainer for " . $targetObjectID;
            $object->models = array(AnnotationConstants::WADMContainer_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', AnnotationConstants::WADMContainer_CONTENT_MODEL);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', AnnotationConstants::ANNOTATION_COLLECTION_NS);
            $object->relationships->add(FEDORA_RELS_EXT_URI, 'isAnnotationContainerOf', $targetObjectID);
            $dsid = AnnotationConstants::WADMContainer_DSID;
            $annotationContainerPID =  $object->id;

            $ds = $object->constructDatastream($dsid, 'M');
            $ds->label = $dsid;
            $ds->mimetype = AnnotationConstants::ANNOTATION_MIMETYPE;

            $targetPageID = $target . "/annotations/?page=0";
            $test = $this->getAnnotationContainerJsonLD($annotationContainerPID, $targetObjectID, $targetPageID);
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
                try{
                    $object = $this->repository->getObject($items[$i]);
                    $WADMObject = $object->getDatastream(AnnotationConstants::WADM_DSID);
                } catch(Exception $e){
                    watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : getAnnotationContainer: Unable to find annotation object with id ' . $items[$i]);
                    continue;
                }
                $dsContent = (string)$WADMObject->content;
                $checksum = $WADMObject->checksum;
                if($dsContent != "NotFound") {
                  $dsContentJson = json_decode($dsContent);
                  $body = conver_W3C_to_lib_annotation_datamodel($dsContentJson);
                  $body->checksum = $checksum;
                  $body->pid = $items[$i];
                  $body->id = $items[$i];
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
        $annotationContainerTracker = new AnnotationContainerTracker();
        $annotationContainerID = $annotationContainerTracker->getAnnotationContainer($this, $targetObjectID, $annotationData);

        // Add annotation
        $oAnnotation = new Annotation($this->repository);
        $annotationInfo = $oAnnotation->createAnnotation($annotationContainerID, $annotationData, $annotationMetadata);

        // Update the container
        $this->addContainerItem($annotationContainerID, $annotationInfo[0]);

        return $annotationInfo[1];
    }

    public function deleteAnnotation($annotationContainerID, $annotationID){
        // Delete the object
        $oAnnotation = new Annotation($this->repository);
        $oAnnotation->deleteAnnotation($annotationID);

        // Return message
        $output = array('status' => "success", "data"=> "The following annotation was deleted: " . $annotationID);
        $output = json_encode($output);

        return $output;
    }

    // When an annotation is deleted, the item must be removed the Container
    // This function will be called by the islandora_web_annotations_islandora_object_alter hook
    public function removeItem($annotationContainerID, $annotationID){
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
        $contentJson["total"] = $contentJson["total"] - 1;
        $updatedContent = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $WADMObject->content = $updatedContent;

        watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer: deleteAnnotation: Annotation with id @annotationID was removed from annotationContainer with id @annotationContainerID', array('@annotationID'=>$annotationID, '@annotationContainerID'=>$annotationContainerID), WATCHDOG_INFO);

        // If there are no annotations, delete the annotationContainer
        if (count($items) == 0) {
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer: deleteAnnotation:  Zero annotations remaining, removing annotationContainer with id @annotationContainerID', array('@annotationContainerID'=>$annotationContainerID), WATCHDOG_INFO);
            $this->deleteAnnotationContainer($annotationContainerID);
        }
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
            $contentJson["total"] = $contentJson["total"] + 1;
            $newContent = json_encode($contentJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $WADMObject->content = $newContent;
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : addContainerItem: Added the following item to the container: @annotationContainerID' , array("@annotationContainerID" => $annotationContainerID), WATCHDOG_INFO);
        } catch(Exception $e){
            watchdog(AnnotationConstants::MODULE_NAME, 'AnnotationContainer : addContainerItem: Failed to add new item to the container: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
            throw $e;
        }


    }

    private function getAnnotationContainerJsonLD($annotationContainerPID, $targetObjectID, $targetPageID)
    {
      global $base_url;
      $containerID = $base_url . "/islandora/object/" . $annotationContainerPID;

      $data = array(
            "@context" => array(AnnotationConstants::ONTOLOGY_CONTEXT_ANNOTATION, AnnotationConstants::ONTOLOGY_CONTEXT_LDP),
            "@id" => $containerID,
            "@type" => array("BasicContainer", "AnnotationCollection"),
            "total" => "0",
            "label" => "annotationContainer for " . $targetObjectID,
            "first" => (object) array("@id" => $targetPageID, "@type" => AnnotationConstants::ANNOTATION_CLASS_2, "items" => array())
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
    public function getAnnotationContainerPID($objectPID)
    {
        $url = parse_url(variable_get('islandora_solr_url', 'localhost:8080/solr'));
        $isAnnotationContainerOfSolrField = variable_get('islandora_web_annotations_isannotationcontainerof_solr_field', 'RELS_EXT_isAnnotationContainerOf_uri_s');
        $qualifier = $isAnnotationContainerOfSolrField . ':' . '"' . "info:fedora/" . $objectPID . '"';
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