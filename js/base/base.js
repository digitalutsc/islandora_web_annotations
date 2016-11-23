/**
 * @file
 * Contains AJAX calls to the Islandora Annotation Server/backend
 * Can be used against multiple content types or viewers
 * Aim is to develop this as a base class
 *
 */

var annotationContainerID = null;

function createAnnotation(targetObjectId, annotationData) {
    var annotation = {
        targetPid: targetObjectId,
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
            alert("Successfully created annotation: " + data);
        }
    });

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

function updateAnnotation(annotationData) {
    var annotationPID = annotationData.pid;
    var annotation = {
        annotationPID: annotationPID,
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