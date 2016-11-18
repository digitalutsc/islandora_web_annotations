<?php
/**
 * @file
 * AnnotaionContainer intreface based on Web Annotation Protocol
 */

interface interfaceAnnotationContainer {

    /**
     * Creates the Annotation Container / Collection
     * It will check if the object has an annotation, if not it will create it
     *
     * @param $objectID
     * @return json  - container representation
     */
    public function createAnnotationContainer($objectID);

    /**
     * Deletes the Annotation Container, including all annotations listed in the container
     *
     * @param $annotationContainerID
     * @return string - status info
     */
    public function deleteAnnotationContainer($annotationContainerID);


    /**
     * eturns a representation of the container containing all the annotations (not ordered)
     *
     * @param $annotationContainerID
     * @return mixed
     */
    public function getAnnotationContainer($annotationContainerID);

    /**
     * Adds the annotation to the AnnotationContainer/Collection, then creates an annotation object by calling the Annotation class
     *
     * @param $annotationInfo
     * @return mixed
     */
    public function createAnnotation($annotationInfo);


    /**
     * Deletes the annotation from the AnnotationContainer/Collection, then deletes the annotation object
     *
     * @param $annotationID
     * @return mixed
     */
    public function deleteAnnotation($annotaionContainerID, $annotationID);

}