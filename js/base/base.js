/**
 * @file
 * Contains AJAX calls to the Islandora Annotation Server/backend
 * Can be used against multiple content types or viewers
 * Aim is to develop this as a base class
 *
 */

var annotationContainerID = null;

function executeCommonLoadOperations() {
    // Apply permissions to edit, delete annotations
    anno.addHandler("onPopupShown", function(annotation) {
        var user = Drupal.settings.islandora_web_annotations.user;
        var createdByMe = (user == annotation.creator) ? true:false;

        jQuery(".annotorious-popup-button-edit").hide();
        jQuery(".annotorious-popup-button-delete").hide();

        if(Drupal.settings.islandora_web_annotations.edit_any == true || (Drupal.settings.islandora_web_annotations.edit_own == true && createdByMe == true)) {
            jQuery(".annotorious-popup-button-edit").show();
        }
        if(Drupal.settings.islandora_web_annotations.delete_any == true || (Drupal.settings.islandora_web_annotations.delete_own == true && createdByMe == true)) {
            jQuery(".annotorious-popup-button-delete").show();
        }
    });
}

function createAnnotation(targetObjectId, annotationData) {
    var user = Drupal.settings.islandora_web_annotations.user;
    var metadata = {}
    metadata.creator = user;

    var annotation = {
        targetPid: targetObjectId,
        metadata: metadata,
        annotationData: annotationData
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
            var jsonData = JSON.parse(data);
            var pid = jsonData.pid;
            var creator = jsonData.creator;
            var created = jsonData.created;
            updateNewAnnotationInfo(pid, creator, created);
            alert("Successfully created annotation: " + data);
        }
    });
}

// We need to update the current annnotation datastore with pid and other info to preform operations and enforce access
function updateNewAnnotationInfo(pid, creator, created) {
    var annotations = anno.getAnnotations()
    for(var j = 0; j < annotations.length; j++){
        if(annotations[j].pid == "New") {
            annotations[j].pid = pid;
            annotations[j].creator = creator;
            annotations[j].created = created;
            break;
        }
    }
}

function getAnnotations(targetObjectId) {
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

                try {
                    var src = annotations[i].src;
                    var text = annotations[i].text;
                    var context = annotations[i].context;
                    var type = annotations[i].shapes[0].type;
                    var x1 = annotations[i].shapes[0].geometry.x;
                    var y1 = annotations[i].shapes[0].geometry.y;
                    var width1 = annotations[i].shapes[0].geometry.width;
                    var height1 = annotations[i].shapes[0].geometry.height;
                    var pid = annotations[i].pid;
                    var creator = annotations[i].creator;
                    var created = annotations[i].created;


                    var myAnnotation = {
                        src: src,
                        text: text,
                        pid: pid,
                        creator: creator,
                        created: created,
                        shapes: [{
                            type: type,
                            geometry: {x: Number(x1), y: Number(y1), width: Number(width1), height: Number(height1)}
                        }],
                        editable: true,
                        context: context
                    };
                    anno.addAnnotation(myAnnotation);
                } catch(e){
                    alert("Error in inserting an annotation");
                }



            }
        }
    });
}

function updateAnnotation(annotationData) {

    // Get metadata
    var user = Drupal.settings.islandora_web_annotations.user;
    var creator = annotationData.creator;
    var created = annotationData.created;
    var metadata = {}
    metadata.author = user;
    metadata.creator = creator;
    metadata.created = created;

    // Do not want data duplicated
    delete annotationData.creator;
    delete annotationData.created;

    var annotationPID = annotationData.pid;
    var annotation = {
        annotationPID: annotationPID,
        metadata: metadata,
        annotationData: annotationData
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

function deleteAnnotation(annotationData) {
    var annotationID = annotationData.pid;
    var annotation = {
        annotationID: annotationID,
        annotationContainerID: annotationContainerID,
        annotationData: annotationData
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