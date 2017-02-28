<?php


/**
 * @file
 * AnnotationContainerTracker implements logic to handle simultaneous editing
 */

class AnnotationContainerTracker {
  const MODULE_NAME = "Islandora Web Annotations";
  private $containerList = "";

  function __construct() {
  }

  public function getAnnotationContainer($annotationContainer, $targetObjectID, $annotationData){

    $annotationContainerID = $annotationContainer->getAnnotationContainerPID($targetObjectID);

    $this->containerList  = variable_get('islandora_web_annotations_inprocess');
    $pos = strpos($this->containerList, $targetObjectID);

    // Case 1: If AnnotationContainer found, return that container id.
    if($annotationContainerID != "None") {
        watchdog(AnnotationConstants::MODULE_NAME, "Case1: If AnnotationContainer found, return that container id");

    // Case 2: If AnnotationContainer ID not found, and not in the InProcess List, then create it and return it.
    } else if ($pos === false) {
      watchdog(AnnotationConstants::MODULE_NAME, "Case 2: If AnnotationContainer ID not found, and not in the InProcess List, then create it and return it.");

      $list = $this->containerList + "||" +  $targetObjectID;
      variable_set('islandora_web_annotations_inprocess', $list);
      $annotationContainerID = $annotationContainer->createAnnotationContainer($targetObjectID, $annotationData);

      // Case 3 : If AnnotationContainer ID not found, and is in the InProcess List, then wait until it gets index and then return it.
    } else {
      watchdog(AnnotationConstants::MODULE_NAME, "Case 3 : If AnnotationContainer ID not found, and is in the InProcess List, then wait until it gets index and then return it.");
      $annotationContainerID = $this->pollSolr($annotationContainer, $targetObjectID);
    }

    return $annotationContainerID;
  }

  private function pollSolr($annotationContainer, $targetObjectID){
    $containerNotFound = true;
    sleep(3);

    $start = microtime(true);

    while($containerNotFound){
      $annotationContainerID = $annotationContainer->getAnnotationContainerPID($targetObjectID);
      if($annotationContainerID == "None"){
        sleep(3);

        // If it takes too long to get indexed info, probably something wrong, log and throw exception
        $time_elapsed_secs = microtime(true) - $start;
        watchdog(AnnotationConstants::MODULE_NAME, "Testing Container Not yet found for object " . $targetObjectID . " Elapsed time " . $time_elapsed_secs);

        if($time_elapsed_secs > 15){
          $msg = "Timeout Exception.  Taking too long to find solr index of the AnnotationContainer for target object with PID " . $targetObjectID;
          watchdog(AnnotationConstants::MODULE_NAME, 'Error in AnnotationTracker: %t', array('%t' => $msg), WATCHDOG_ERROR);
          throw new Exception($msg);
        }

      } else {
        $this->cleanupInProcessList($targetObjectID);
        $containerNotFound = false;
      }
    }

    return $annotationContainerID;
  }

  private function cleanupInProcessList($targetObjectID){
    $currentList  = variable_get('islandora_web_annotations_inprocess');
    $pids = explode("||", $currentList);
    $newList = array_diff($pids, array($targetObjectID));
    $updateList = implode("||", $newList);
    variable_set('islandora_web_annotations_inprocess', $updateList);
  }
}