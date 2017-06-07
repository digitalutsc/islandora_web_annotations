/**
 *
 * @file
 * Contains annotations related functions specific to large images (open sea dragon viewer)
 *
 */

var g_contentType = "large-image";
var g_targetURL = window.location.href;

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

    if(Drupal.settings.islandora_web_annotations.remove_clipper == true) {
        // Remove clipper icon element(s) provided with large image solution pack.
        jQuery('#clip').remove();
    }

    if(Drupal.settings.islandora_web_annotations.view == true) {
        var saveButton = jQuery('<button id="load-annotation-button" title="Toggle Annotations" class="annotator-adder-actions__button h-icon-annotate" onclick="getAnnotationsLargeImage()"></button>');
        saveButton.appendTo(jQuery("#openseadragon-wrapper"));

        if(Drupal.settings.islandora_web_annotations.load_true == true){
            setContentURI();
            getAnnotationsLargeImage();
        }
    }

    if(Drupal.settings.islandora_web_annotations.create == true) {
        var addButton = jQuery('<button id="add-annotation-button" title="Add Annotation" class="annotator-adder-actions__button h-icon-add" onclick="anno.activateSelector();"></button>');
        addButton.appendTo(jQuery("#openseadragon-wrapper"));
        setContentURI();
    }


    var os_viewer = Drupal.settings.islandora_open_seadragon_viewer;
    anno.makeAnnotatable(os_viewer);


    // Position issue
    var isChrome = !!window.chrome;
    if(!isChrome){
        window.pageYOffset = 0;
    }

    // This is a fix to address the annotation positioning (#6) related to issue in FireFox, not an issue in Chrome
    anno.addHandler('onEditorShown', function(annotation) {
        var isChrome = !!window.chrome;
        if(!isChrome){
            window.pageYOffset = 0;
        }
    });

    anno.addHandler("onAnnotationCreated", function(annotation) {
        var targetObjectId = Drupal.settings.islandoraOpenSeadragon.pid;
        annotation.pid = "New";
        createAnnotation(targetObjectId, annotation);
    });

    anno.addHandler("onAnnotationUpdated", function(annotation) {
        updateAnnotation(annotation);
    });

    anno.addHandler("beforeAnnotationRemoved", function(annotation) {
        var isConfirmed = confirm("This annotation will be deleted. This action cannot be reversed. Are you sure?");
        if (isConfirmed) {
            deleteAnnotation(annotation);
        } else {
            return false;
        }
    });

    executeCommonLoadOperations();
});


function getAnnotationsLargeImage() {
    var objectPID = Drupal.settings.islandora_web_annotations.pid;
    getAnnotations(objectPID);
}

function setContentURI() {
    var objectPID = Drupal.settings.islandora_web_annotations.pid;
    g_targetURL = location.protocol + '//' + location.host + "/islandora/object/" + objectPID
}