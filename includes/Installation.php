<?php
/**
 * Created by PhpStorm.
 * User: nat
 * Date: 21/11/16
 * Time: 2:00 PM
 */
require_once('AnnotationConstants.php');
module_load_include('inc', 'islandora', 'includes/solution_packs');


function installIslandoraObjects()
{
    try{
        $connection = islandora_get_tuque_connection();

        $object1 = installObject($connection, AnnotationConstants::WADM_CONTENT_MODEL, AnnotationConstants::WADM_CONTENT_MODEL_LABEL, "webannotation_ds_composite_model.xml");
        $message1 = "Installed " . $object1;

        $object2 = installObject($connection, AnnotationConstants::WADMContainer_CONTENT_MODEL, AnnotationConstants::WADMContainer_CONTENT_MODEL_LABEL, "webannotation_ds_composite_model.xml");
        $message2 = "Installed " . $object2;


        return $message1 . ".  " . $message2 . ". ";
    } catch(Exception $e) {
        watchdog(AnnotationConstants::MODULE_NAME, 'Error in installIslandoraObject: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
    }
}


function installObject($connection, $contentModel, $contentModelLabel, $composite_model_fileName)
{
    try{
        $object = $connection->repository->constructObject($contentModel);
        $object->owner = 'fedoraAdmin';
        $object->label = $contentModelLabel;
        $object->models = array("fedora-system:ContentModel-3.0");

        $ds_composite_datastream = $object->constructDatastream('DS-COMPOSITE-MODEL', 'X');
        $ds_composite_datastream->label = 'DS-COMPOSITE-MODEL';
        $ds_composite_datastream->mimetype = 'text/xml';
        $moduleDir = realpath(__DIR__ . '/..');
        $ds_composite_datastream->setContentFromFile($moduleDir . "/xml/" . $composite_model_fileName, FALSE);
        $object->ingestDatastream($ds_composite_datastream);

        $connection->repository->ingestObject($object);
        $objectID =  $object->id;

        watchdog(AnnotationConstants::MODULE_NAME, 'installObject with pid: %t', array('%t' => $objectID), WATCHDOG_INFO);

        return $objectID;
    } catch(Exception $e) {
        watchdog(AnnotationConstants::MODULE_NAME, 'Error in installIslandoraObject: %t', array('%t' => $e->getMessage()), WATCHDOG_ERROR);
    }


}