/**
 *
 * @file
 * Contains annotations related functions specific to basic images
 *
 */

var g_contentType = "basic-image";

jQuery(document).ready(function() {

    if(Drupal.settings.islandora_web_annotations.view == true) {
        var loadAnnotationsButton = jQuery('<button id="load-annotation-button" title="Load Annotations" class="annotator-adder-actions__button h-icon-annotate" onclick="getAnnotationsBasicImage()"></button>');
        loadAnnotationsButton.appendTo(jQuery(".islandora-basic-image-content")[0]);
    }

    if(Drupal.settings.islandora_web_annotations.create == true) {
        var addButton = jQuery('<button id="add-annotation-button" class="annotator-adder-actions__button h-icon-add" title="Add Annotation" onclick="initBasicImageAnnotation();"></button>');
        addButton.appendTo(jQuery(".islandora-basic-image-content")[0]);
    }

    executeCommonLoadOperations();

});


function initBasicImageAnnotation(){
    var m_image = jQuery("div[class='islandora-basic-image-content']").find("img[typeof='foaf:Image']").first();
    jQuery(m_image).unwrap();

    anno.makeAnnotatable(m_image[0]);

    anno.addHandler("onAnnotationCreated", function(annotation) {
        if(Drupal.settings.islandora_web_annotations.create == true) {
            var objectPID = getBasicImagePID();
            annotation.pid = "New";
            createAnnotation(objectPID, annotation);
        } else {
            alert("You do not have permissions to save annotations for basic image.");
        }
    });

    anno.addHandler("onAnnotationUpdated", function(annotation) {
        updateAnnotation(annotation);
    });

    anno.addHandler("onAnnotationRemoved", function(annotation) {
        deleteAnnotation(annotation);
    });

    jQuery("#add-annotation-button").remove();
    jQuery(".annotorious-hint").css("left", "45px");

}

function getAnnotationsBasicImage() {
    if(jQuery(".annotorious-hint-icon").is(":visible")=== false) {
        initBasicImageAnnotation();
    }
    var objectPID = getBasicImagePID();
    getAnnotations(objectPID);
}

function getBasicImagePID() {
    var objectURL = window.location.href;
    var objectPID = objectURL.substr(objectURL.lastIndexOf('/') + 1);
    objectPID = objectPID.replace("%3A", ":");
    objectPID = objectPID.replace("#", "");
    return objectPID;
}

