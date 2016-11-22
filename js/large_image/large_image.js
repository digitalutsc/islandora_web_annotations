
var annotationContainerID = null;

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


    var saveButton = jQuery('<button id="load-annotation-button" title="Load Annotations" class="annotator-adder-actions__button h-icon-annotate" onclick="getAnnotations()"></button>');
    saveButton.appendTo(jQuery("#openseadragon-wrapper"));


    var addButton = jQuery('<button id="add-annotation-button" title="Add Annotation" class="annotator-adder-actions__button h-icon-add" onclick="anno.activateSelector();"></button>');
    addButton.appendTo(jQuery("#openseadragon-wrapper"));


    var os_viewer = Drupal.settings.islandora_open_seadragon_viewer;
    anno.makeAnnotatable(os_viewer);

    anno.addHandler('onEditorShown', function(annotation) {
        window.pageYOffset = 0;
    });

    anno.addHandler("onAnnotationCreated", function(annotation) {
       createAnnotation(annotation);
    });

    anno.addHandler("onAnnotationUpdated", function(annotation) {
        updateAnnotation(annotation);
    });

    anno.addHandler("onAnnotationRemoved", function(annotation) {
        deleteAnnotation(annotation);
    });


    // for testing
    annotationsStore = new Lawnchair({name:'annotationsStore'}, function(store) {
    });

});

function createAnnotation(annotation)
{
    var targetObjectId = Drupal.settings.islandoraOpenSeadragon.pid;
    var annotation = {
        targetPid: targetObjectId,
        annotationData: annotation
    };

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/create',
        dataType: 'json',
        type: 'POST',
        data: annotation,
        error: function() {
            alert("Error in creating annotation.");
        },
        success: function(data) {
            alert("Successfully created annotation: " + data);
        }
    });
}

function updateAnnotation(annotation)
{
    var annotationPID = annotation.pid;
    var annotation = {
        annotationPID: annotationPID,
        annotationData: annotation
    };

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/update',
        dataType: 'json',
        type: 'PUT',
        data: annotation,
        error: function() {
            alert("Error in updating annotation.");
        },
        success: function(data) {
            alert("Successfully updated the annotation: " + data);
        }
    });

}


function deleteAnnotation(annotation)
{
    var annotationID = annotation.pid;
    var annotation = {
        annotationID: annotationID,
        annotationContainerID: annotationContainerID,
        annotationData: annotation
    };

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/delete',
        dataType: 'json',
        type: 'DELETE',
        data: annotation,
        error: function() {
            alert("Error in deleting annotation.");
        },
        success: function(data) {
            alert("Successfully deleted the annotation: " + data);
        }
    });
}

function getAnnotations()
{

    var targetObjectId = Drupal.settings.islandoraOpenSeadragon.pid;
    var annotation = {
        targetPid: targetObjectId
    };

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/get',
        dataType: 'json',
        type: 'GET',
        data: annotation,
        error: function() {
            alert("Error in loading annotations");
        },
        success: function(data) {
            var jsonData = JSON.parse(data);

            annotationContainerID = jsonData["@id"];

            var annotations = jsonData.first.items;

            for(var i = 0; i< annotations.length; i++)
            {

                var src = annotations[i].src;
                var text = annotations[i].text;
                var context = annotations[i].context;
                var type = annotations[i].shapes[0].type;
                var x1 = annotations[i].shapes[0].geometry.x;
                var y1 = annotations[i].shapes[0].geometry.y;
                var width1 = annotations[i].shapes[0].geometry.width;
                var height1 = annotations[i].shapes[0].geometry.height;
                var pid = annotations[i].pid;


                var myAnnotation = {
                    src: src,
                    text: text,
                    pid: pid,
                    shapes: [{
                        type: type,
                        geometry: { x: Number(x1), y: Number(y1), width: Number(width1), height: Number(height1) }
                    }],
                    editable: true,
                    context: context
                };
                anno.addAnnotation(myAnnotation);

            }
        }
    });

}



// for testing
function saveAnnotations()
{

    var annotations = anno.getAnnotations();

    annotationsStore.remove("test1");

    // create an object
    var me = {key:'test1', value:annotations};

    // save it
    annotationsStore.save(me);

}

// for testing
function loadAnnotations2()
{

    // access it later... yes even after a page refresh!
    annotationsStore.get("test1", function(obj) {
        for(var i = 0; i< obj.value.length; i++)
        {
            anno.addAnnotation(obj.value[i])
        }
    });
}