/**
 *
 * @file
 * Contains annotations related functions specific to basic images
 *
 */

jQuery(document).ready(function() {

    var m_image = jQuery("div[class='islandora-basic-image-content']").find("img[typeof='foaf:Image']").first();
    jQuery(m_image).unwrap();

    var loadAnnotationsButton = jQuery('<button id="load-annotation-button" title="Load Annotations" class="annotator-adder-actions__button h-icon-annotate" onclick="getAnnotationsBasicImage()"></button>');
    loadAnnotationsButton.appendTo(jQuery(".islandora-basic-image-content")[0]);

    anno.makeAnnotatable(m_image[0]);

    anno.addHandler("onAnnotationCreated", function(annotation) {
        var objectPID = getBasicImagePID();
        createAnnotation(objectPID, annotation);
    });

    anno.addHandler("onAnnotationUpdated", function(annotation) {
        updateAnnotation(annotation);
    });

    anno.addHandler("onAnnotationRemoved", function(annotation) {
        deleteAnnotation(annotation);
    });

    jQuery(".annotorious-hint").css("left", "45px");

});


function getAnnotationsBasicImage() {
    var objectPID = getBasicImagePID();
    getAnnotations(objectPID);
}


function getBasicImagePID() {
    var objectURL = Drupal.settings.urlIsAjaxTrusted;
    objectURL = Object.keys(objectURL)[0];
    var objectPID = objectURL.substr(objectURL.lastIndexOf('/') + 1);
    objectPID = objectPID.replace("%3A", ":");
    return objectPID;
}