<?php
/**
 * @file
 * Annotation intreface based on Web Annotation Protocol
 */

interface interfaceAnnotation {

    public function createAnnotation($annotationContainerID, $annotationInfo, $annotationMetadata);
    public function updateAnnotation($annotationID, $annotationMetadata);
    public function deleteAnnotation($annotationID);
}