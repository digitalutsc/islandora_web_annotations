<?php


/**
 * @file
 * AnnotationContainerTracker implements logic to handle simultaneous editing
 */

class AnnotationContainerTracker {
  const MODULE_NAME = "Islandora Web Annotations";

  function __construct() {
  }

  public function getAnnotationContainer($annotationContainer, $targetObjectID, $annotationData){
    $this->getLock();

    $annotationContainerID = $annotationContainer->getAnnotationContainerPID($targetObjectID);

    // Case 1: If AnnotationContainer found, return that container id.
    if($annotationContainerID != "None") {
      $this->cleanupInProcessList($targetObjectID);
    } else {
      $containerList  = variable_get('islandora_web_annotations_inprocess');
      $pos = strpos($containerList, $targetObjectID);

      // Case 2: If AnnotationContainer ID not found, and not in the InProcess List, then create it and return it.
      if ($pos === false) {
        $list = $containerList . "||" .  $targetObjectID;
        variable_set('islandora_web_annotations_inprocess', $list);
        $annotationContainerID = $annotationContainer->createAnnotationContainer($targetObjectID, $annotationData);

      // Case 3 : If AnnotationContainer ID not found, and is in the InProcess List, then wait until it gets index and then return it.
      } else {
        $annotationContainerID = $this->pollSolr($annotationContainer, $targetObjectID);
      }
    }

    lock_release('getAnnotationContainer');
    return $annotationContainerID;
  }

  private function getLock(){
    (bool) $isLockAvailable = lock_acquire('getAnnotationContainer');

    $start = microtime(true);
    while($isLockAvailable == false){
      sleep(2);
      $isLockAvailable = lock_acquire('getAnnotationContainer');
      $time_elapsed_secs = microtime(true) - $start;

      if($time_elapsed_secs > 20){
        $msg = "Timeout Exception.  Taking too long to get lock for getAnnotationContainer.";
        watchdog(AnnotationContainerTracker::MODULE_NAME, 'Error in AnnotationTracker -> getLock: %t', array('%t' => $msg), WATCHDOG_ERROR);
        throw new Exception($msg);
      }
    }
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

        if($time_elapsed_secs > 20){
          $msg = "Timeout Exception.  Taking too long to find solr index of the AnnotationContainer for target object with PID " . $targetObjectID;
          watchdog(AnnotationContainerTracker::MODULE_NAME, 'Error in AnnotationTracker -> pollSolr: %t', array('%t' => $msg), WATCHDOG_ERROR);
          // Lets clean up the list, in case it got into the list
          $this->cleanupInProcessList($targetObjectID);
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