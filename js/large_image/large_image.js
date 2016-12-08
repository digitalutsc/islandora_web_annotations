/**
 *
 * @file
 * Contains annotations related functions specific to large images (open sea dragon viewer)
 *
 */

var g_contentType = "large-image";

jQuery(document).ready(function() {

    // Replace id
    jQuery("#islandora-openseadragon").attr("id", "openseadragon-islandora");

    Drupal.settings.islandora_open_seadragon_viewer.id = "openseadragon-islandora";

    // Get the div DOM
    var openSeaDragonDiv = jQuery("#openseadragon-islandora");


    // Remove the class
    openSeaDragonDiv.removeClass( "islandora-openseadragon" );

    // Add a parent div with this class
    openSeaDragonDiv.wrap('<div class="islandora-openseadragon" id="openseadragon-wrapper"></div>');


    if(Drupal.settings.islandora_web_annotations.view == true) {
        var saveButton = jQuery('<button id="load-annotation-button" title="Load Annotations" class="annotator-adder-actions__button h-icon-annotate" onclick="getAnnotationsLargeImage()"></button>');
        saveButton.appendTo(jQuery("#openseadragon-wrapper"));
    }

    if(Drupal.settings.islandora_web_annotations.create == true) {
        var addButton = jQuery('<button id="add-annotation-button" title="Add Annotation" class="annotator-adder-actions__button h-icon-add" onclick="anno.activateSelector();"></button>');
        addButton.appendTo(jQuery("#openseadragon-wrapper"));
    }


    var os_viewer = Drupal.settings.islandora_open_seadragon_viewer;
    anno.makeAnnotatable(os_viewer);

    anno.addHandler('onEditorShown', function(annotation) {
        window.pageYOffset = 0;
    });

    anno.addHandler("onAnnotationCreated", function(annotation) {
        var targetObjectId = Drupal.settings.islandoraOpenSeadragon.pid;
        annotation.pid = "New";
        createAnnotation(targetObjectId, annotation);
    });

    anno.addHandler("onAnnotationUpdated", function(annotation) {
        updateAnnotation(annotation);
    });

    anno.addHandler("onAnnotationRemoved", function(annotation) {
            deleteAnnotation(annotation);
    });

    executeCommonLoadOperations();
});


function getAnnotationsLargeImage() {
    var targetObjectId = Drupal.settings.islandoraOpenSeadragon.pid;
    getAnnotations(targetObjectId);
}
