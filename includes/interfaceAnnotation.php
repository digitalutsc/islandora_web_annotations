<?php
/**
 * @file
 * Annotation intreface based on Web Annotation Protocol
 */

interface interfaceAnnotation {

    public function createAnnotation($annotationContainerID, $annotationInfo);
    public function updateAnnotation($annotationID);
    public function deleteAnnotation($annotationID);
}