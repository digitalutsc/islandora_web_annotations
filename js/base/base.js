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
            insertLabelForNewAnnotation(pid, annotationData);
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

            // In case user has inserted items before loading, remove them to reload those items
            anno.removeAll();
            deleteAllLabelsAndBlockItems();

            // Label related
            var canvas = jQuery(".islandora-"+ g_contentType + "-content").find("canvas")[0]
            var htmlBlock = "<ul id='annotation-list' style='list-style-type: none;'>";

            // Extract data
            var jsonData = JSON.parse(data);
            annotationContainerID = jsonData["@id"];
            var annotations = jsonData.first.items;

            for(var i = 0; i< annotations.length; i++)
            {
                try {
                    var pid = annotations[i].pid;
                    var src = annotations[i].src;
                    var text = annotations[i].text;

                    htmlBlock = htmlBlock + "<li id='block_label_"+ pid + "'> [" + (i+1) + "] " + text + "</li>";

                    var context = annotations[i].context;
                    var type = annotations[i].shapes[0].type;
                    var x1 = Number(annotations[i].shapes[0].geometry.x);
                    var y1 = Number(annotations[i].shapes[0].geometry.y);
                    var width1 = Number(annotations[i].shapes[0].geometry.width);
                    var height1 = Number(annotations[i].shapes[0].geometry.height);
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
                            geometry: {x: x1, y: y1, width: width1, height: height1}
                        }],
                        editable: true,
                        context: context
                    };
                    anno.addAnnotation(myAnnotation);
                } catch(e){
                    alert("Error in inserting an annotation");
                }
                insertLabel(g_contentType, i+1, pid, canvas, x1, y1, width1, height1);
            }

            htmlBlock = htmlBlock + "</ul>";
            insertAnnotationDataBlock(htmlBlock, g_contentType);
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

    deleteLabelAndDataBlockItem(annotationID);
}


/**
 *
 * @param htmlBlock
 * @param contentType
 */
function insertAnnotationDataBlock(htmlBlock, contentType) {
    // If not already installed
    if(jQuery("#annotation-list").length == 0) {
        jQuery('<div><h2>Annotations</h2>' + htmlBlock + '</div>').appendTo(jQuery(".islandora-" + contentType + "-metadata"));
    }
}

function insertAnnotationDataBlockItem(pid, i, text) {
    var htmlItem = "<li id='block_label_"+ pid + "'> [" + i + "] " + text + "</li>";
    jQuery(htmlItem).appendTo(jQuery("#annotation-list"));
}

function insertLabelForNewAnnotation(pid, annotation) {
    var i = anno.getAnnotations().length;
    var x1 = Number(annotation.shapes[0].geometry.x);
    var y1 = Number(annotation.shapes[0].geometry.y);
    var width1 = Number(annotation.shapes[0].geometry.width);
    var height1 = Number(annotation.shapes[0].geometry.height);
    var canvas = jQuery(".islandora-"+ g_contentType + "-content").find("canvas")[0]

    insertLabel(g_contentType, i, pid, canvas, x1, y1, width1, height1);

    var text = annotation.text;
    insertAnnotationDataBlockItem(pid, i, text)
}

/**
 *
 * @param contentType: basic-image, large-image
 * @param i - index of the annotation
 * @param canvas - canvas of the image, used to calculate size
 * @param x1 - x position of the annotation
 * @param y1 - y position of the annotation
 * @param width1 - width of the annotation box
 * @param height1 - height of the annotation box
 */
function insertLabel(contentType, i, pid, canvas, x1, y1, width1, height1) {
    var decimalX = x1 + width1;
    var decimalY = y1 + height1;

    // If already exists, then return
    if(jQuery(document.getElementById("label_" + pid)).length >= 1){
        return;
    }

    if(contentType == "large-image") {
        var eleSpan = document.createElement("span");
        var eleText = document.createTextNode(i);     // Create a text node
        eleSpan.appendChild(eleText);
        eleSpan.className = "marker-large-mage";
        eleSpan.setAttribute("id", "label_" + pid);

        Drupal.settings.islandora_open_seadragon_viewer.addOverlay({
            element: eleSpan,
            location: new OpenSeadragon.Rect(decimalX - 0.035, decimalY - 0.035, 0.035, 0.035)
        });
    } else {
        var canvasWidth = canvas.width;
        var canvasHeight = canvas.height;

        var pixelX = (decimalX * canvasWidth) - 20;
        var pixelY = (decimalY * canvasHeight) - 20;

        jQuery('<span class="marker" id="label_'+ pid +'">' + i + '</span>').css({
            top: pixelY,
            left: pixelX
        }).appendTo(jQuery(jQuery(canvas).parent()));
    }
}

function deleteLabelAndDataBlockItem(annotationID) {
    // Remove Label
    if(g_contentType == "large-image"){
        Drupal.settings.islandora_open_seadragon_viewer.removeOverlay("label_" + annotationID);
    } else {
        jQuery(document.getElementById("label_" + annotationID)).remove();
    }
    // Remove Block Item
    jQuery(document.getElementById("block_label_" + annotationID)).remove();
}

function deleteAllLabelsAndBlockItems(){
    if(g_contentType == "large-image"){
        var labels = jQuery('span[id^="label_islandora"]');
        for (var j = 0; j < labels.count; j++){
            var labelID =  labels[j];
            Drupal.settings.islandora_open_seadragon_viewer.removeOverlay(labelID);
        }
    } else {
        jQuery('span[id^="label_islandora"]').remove();
    }

    jQuery("#annotation-list").remove();
}