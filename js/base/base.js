/**
 * @file
 * Contains AJAX calls to the Islandora Annotation Server/backend
 * Can be used against multiple content types or viewers
 * Aim is to develop this as a base class
 *
 */

var annotationContainerID = null;
var label_prefix = "label_anno";

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
            var checksum = jsonData.checksum;

            updateNewAnnotationInfo(pid, creator, created, checksum);
            insertLabelForNewAnnotation(pid, annotationData);
            alert("Successfully created annotation: " + data);
        }
    });


}

// We need to update the current annnotation datastore with pid and other info to preform operations and enforce access
function updateNewAnnotationInfo(pid, creator, created, checksum) {
    var annotations = anno.getAnnotations()
    for(var j = 0; j < annotations.length; j++){
        if(annotations[j].pid == "New") {
            annotations[j].pid = pid;
            annotations[j].creator = creator;
            annotations[j].created = created;
            annotations[j].checksum = checksum;
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
            var htmlBlock = "";

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
                    var checksum = annotations[i].checksum;

                    var myAnnotation = {
                        src: src,
                        text: text,
                        pid: pid,
                        creator: creator,
                        created: created,
                        checksum: checksum,
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
            insertUpdateAnnotationDataBlock(htmlBlock);
        }
    });
}

function updateAnnotation(annotationData) {

    // Get metadata
    var user = Drupal.settings.islandora_web_annotations.user;
    var creator = annotationData.creator;
    var created = annotationData.created;
    var checksum = annotationData.checksum;

    var metadata = {}
    metadata.author = user;
    metadata.creator = creator;
    metadata.created = created;

    // Do not want data duplicated
    delete annotationData.creator;
    delete annotationData.created;
    delete annotationData.checksum;


    var annotationPID = annotationData.pid;
    var annotation = {
        annotationPID: annotationPID,
        metadata: metadata,
        annotationData: annotationData

    };

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/update',
        beforeSend: function (request)
        {
            request.setRequestHeader("If-Match", checksum);
        },
        dataType: 'json',
        type: 'PUT',
        data: annotation,
        error: function() {
            alert("Error in updating annotation.");
        },
        success: function(data) {
            var jsonData = JSON.parse(data);
            var status = jsonData.status;
            var annoInfo = jsonData.data;
            var checksum = annoInfo.checksum;
            var updatedText = annoInfo.body.text;
            updateAnnotationInfo(annotationPID, checksum, updatedText);

            if(status == "success") {
                alert("Successfully updated the annotation: " + JSON.stringify(annoInfo));
            } else if(status == "conflict"){
                alert("There was an edit conflict.  Please copy your changes, reload the annotations and try again");
            } else {
                alert("Unable to update.  Error info: " . JSON.stringify(annoInfo));
            }
        }
    });

}

// Update AnnotatoinDatastore
function updateAnnotationInfo(pid, checksum, updatedText) {
    var annotations = anno.getAnnotations()
    for(var j = 0; j < annotations.length; j++){
        if(annotations[j].pid == pid) {
            annotations[j].checksum = checksum;

            var currentText = document.getElementById("block_label_" + pid).innerHTML;
            var labelNumber = currentText.substring(0, 4);
            document.getElementById("block_label_" + pid).innerHTML = labelNumber + " " + updatedText;
            break;
        }
    }
}


function deleteAnnotation(annotationData) {
    var annotationID = annotationData.pid;
    var checksum = annotationData.checksum;

    var annotation = {
        annotationID: annotationID,
        annotationContainerID: annotationContainerID,
        annotationData: annotationData
    };

    // annotationData data - in case it fails to delete
    delete annotationData.checksum;

    jQuery.ajax({
        url: 'http://localhost:8000/islandora_web_annotations/delete',
        beforeSend: function (request)
        {
            request.setRequestHeader("If-Match", checksum);
        },
        dataType: 'json',
        type: 'DELETE',
        data: annotation,
        error: function() {
            alert("Error in deleting annotation.");
        },
        success: function(data) {
            var jsonData = JSON.parse(data);
            var status = jsonData.status;
            var annoInfo = jsonData.data;
            if(status == "success") {
                alert("Success: " + JSON.stringify(annoInfo));
            } else if(status == "conflict"){
                alert("There was an edit conflict.  Please reload the annotations to view the changes.  You can try again to delete.");
            } else {
                alert("Unable to delete.  Error info: " . JSON.stringify(annoInfo));
            }
        }
    });

    deleteLabelAndDataBlockItem(annotationID);
}


/**
 *
 * @param htmlBlock
 * @param contentType
 */
function insertUpdateAnnotationDataBlock(htmlBlock) {
    // If not already installed
    if(jQuery("#annotation-list").length == 0) {
        jQuery('<div><h2>Annotations</h2><ul id="annotation-list" style="list-style-type: none;">' + htmlBlock + '</ul></div>').appendTo(jQuery(".islandora-" + g_contentType + "-metadata"));
    } else {
        jQuery(htmlBlock).appendTo(jQuery("#annotation-list"));
    }
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
    var htmlItem = "<li id='block_label_"+ pid + "'> [" + i + "] " + text + "</li>";
    insertUpdateAnnotationDataBlock(htmlItem);
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
    if(jQuery(document.getElementById(label_prefix + pid)).length >= 1){
        return;
    }

    if(contentType == "large-image") {
        var eleSpan = document.createElement("span");
        var eleText = document.createTextNode(i);     // Create a text node
        eleSpan.appendChild(eleText);
        eleSpan.className = "marker-large-mage";
        eleSpan.setAttribute("id", label_prefix + pid);

        Drupal.settings.islandora_open_seadragon_viewer.addOverlay({
            element: eleSpan,
            location: new OpenSeadragon.Rect(decimalX - 0.035, decimalY - 0.035, 0.035, 0.035)
        });
    } else {
        var canvasWidth = canvas.width;
        var canvasHeight = canvas.height;

        var pixelX = (decimalX * canvasWidth) - 20;
        var pixelY = (decimalY * canvasHeight) - 20;

        jQuery('<span class="marker" id="' + label_prefix + pid +'">' + i + '</span>').css({
            top: pixelY,
            left: pixelX
        }).appendTo(jQuery(jQuery(canvas).parent()));
    }
}

function deleteLabelAndDataBlockItem(annotationID) {
    // Remove Label
    if(g_contentType == "large-image"){
        Drupal.settings.islandora_open_seadragon_viewer.removeOverlay(label_prefix+ annotationID);
    } else {
        jQuery(document.getElementById(label_prefix + annotationID)).remove();
    }
    // Remove Block Item
    jQuery(document.getElementById("block_label_" + annotationID)).remove();
}

function deleteAllLabelsAndBlockItems(){
    if(g_contentType == "large-image"){
        var labels = jQuery('span[id^="'+ label_prefix + '"]');
        for (var j = 0; j < labels.length; j++){
            var labelID =  labels[j].id;
            Drupal.settings.islandora_open_seadragon_viewer.removeOverlay(labelID);
        }
    } else {
        jQuery('span[id^="'+ label_prefix + '"]').remove();
    }

    jQuery("#annotation-list").parent().remove();
}