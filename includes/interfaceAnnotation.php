<?php
/**
 * Created by PhpStorm.
 * User: nat
 * Date: 15/11/16
 * Time: 11:50 AM
 */

interface interfaceAnnotation {

    public function createAnnotation($annotationContainerID, $annotationInfo);
    public function updateAnnotation($annotationID);
    public function deleteAnnotation($annotationID);
    public function getAnnotation($annotationID);

}