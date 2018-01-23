
var user = "anonymous";

jQuery(document).ready(function() {
    $ = jQuery;
    user = Drupal.settings.islandora_web_annotations.user;

    // Hide Lib related permission fields
    jQuery(".annotator-checkbox").hide();


    jQuery("#islandora_videojs_html5_api").addClass("video-js");
    jQuery("#islandora_videojs_html5_api").attr("preload", "none");

    preload="none"

    var objectPID = Drupal.settings.islandora_web_annotations.pid;
    var objectUri = location.protocol + '//' + location.host + "/islandora/object/" + objectPID

    var options = {
        optionsAnnotator: {
            permissions: { },
            store: {
                // The endpoint of the store on your server.
                prefix: location.protocol + '//' + location.host + '/islandora_web_annotations',
                emulateJSON: true,
                annotationData: {uri:objectUri},
                urls: {
                    create: '/create',
                    update: '/update',
                    destroy: '/delete'
                },
                loadFromSearch:{
                    limit:100,
                    uri: objectUri,
                }
            },
            richText: {
                tinymce:{
                    selector: "li.annotator-item textarea",
                    plugins: "media image insertdatetime link code",
                    menubar: false,
                    toolbar_items_size: 'small',
                    extended_valid_elements : "iframe[src|frameborder|style|scrolling|class|width|height|name|align|id]",
                    toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media rubric | code ",
                }
            },
            annotator: {}, //Annotator core
        },
        optionsVideoJS: {techOrder: ["html5","flash"]},
        optionsRS: {},
        optionsOVA: {posBigNew:'none'/*,NumAnnotations:20*/},
    }

    //Load the plugin Open Video Annotation
    try {

        var targetDiv = jQuery(".video-js").first().parent().parent();
        ova = new OpenVideoAnnotation.Annotator(targetDiv, options);
    }
    catch(e){
        alert(e)
    }

    // Apply Permissions after OpenVideoAnnotation elements are loaded
    if(Drupal.settings.islandora_web_annotations.view === false){
        jQuery(".vjs-showannotations-annotation").hide();
        jQuery(".vjs-statistics-annotation").hide();
    }

    if(Drupal.settings.islandora_web_annotations.create == false) {
        jQuery(".vjs-new-annotation").hide();
    }


    //change the user (Experimental)
    ova.setCurrentUser(user);
    $('#username').change(function () {
        ova.setCurrentUser($(this).val());
    });

    ova.annotator.subscribe('annotationViewerShown', function(viewer, annotations){
        applyPermissionsOnView(annotations);
        if(jQuery(".annotator-hl.active").length > 0) {
            var left = jQuery(".annotator-hl.active").first().find("div").first().css("left");
            left = left.substr(0, left.length - 2);
            var width = jQuery(".annotator-hl.active").first().find("div").first().width();
            var newleft = Number(left) + Number(width) / 2;
            jQuery(".annotator-viewer").first().css({left: newleft + "px"});

            var top = jQuery(".annotator-hl.active").first().find("div").first().css("top");
            top = top.substr(0, top.length - 2);
            top = Number(top) + 30;
            jQuery(".annotator-viewer").first().css({top: top + "px"});

        } else {
            if(jQuery(".vjs-selectionbar-RS").first().is(":visible") === true) {
                positionAnnotatorForm(".annotator-viewer");
            }
        }
    });

    ova.annotator.subscribe('annotationEditorShown', function(viewer, annotations){

        // Remove the list items which contain default permission checkboxes
        // from the Open Video Annotation editor.  We do not use these.
        jQuery('li.annotator-checkbox').remove();
        // issue-174
        jQuery(".mce-i-rubric").parent().hide();

        if(jQuery(".islandora-oralhistories-object").length > 0){
            positionAnnotatorForm(".annotator-editor");

        }
    });

    ova.annotator.subscribe('beforeAnnotationUpdated', function(annotation){
        annotation.author = ova.currentUser;
        applyBlock("updated");
    });

    ova.annotator.subscribe('annotationCreated', function(annotation) {
        applyBlock("created");
    });

    askIfUserWantsToPlay();
});

/**
 * If the page gets loaded from search results, provide the user option to play the annotation.
 */
function askIfUserWantsToPlay() {

    jQuery.blockUI({
        message: "Would you like to play the video annotation? <br><span id='yes_play'>Yes</span> &nbsp;&nbsp;&nbsp;&nbsp;<span id='do_not_play'>No</span>",
        fadeIn: 700,
        fadeOut: 700,
        timeout: 3000,
        showOverlay: false,
        centerY: false,
        css: {
            height: '50px',
            border: 'none',
            padding: '5px',
            backgroundColor: '#000',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px',
            opacity: .6,
            color: '#fff'
        }
    });

    jQuery('#yes_play').click(function() {
        jQuery.unblockUI();
        var annotationPID = getParameterByName("annotationPID");
        ova.playTarget(annotationPID);
    });

    jQuery('#do_not_play').click(function() {
        jQuery.unblockUI();
    });
}

function applyPermissionsOnView(annotations){

    var createdByMe = (user == annotations[0].user) ? true:false;

    jQuery(".annotator-edit").hide();
    jQuery(".annotator-delete").hide();

    if(Drupal.settings.islandora_web_annotations.edit_any === true || (Drupal.settings.islandora_web_annotations.edit_own === true && createdByMe === true)) {
        jQuery(".annotator-edit").show();
    }
    if(Drupal.settings.islandora_web_annotations.delete_any === true || (Drupal.settings.islandora_web_annotations.delete_own === true && createdByMe === true)) {
        jQuery(".annotator-delete").show();
    }

}


/**
 * issue#123
 * Due to an bug in the annotator js library, the  annotationCreated does not return the pid of the created annotation.
 * We need to attach a POST listener to get this info and update the store.
 * This is required to enable the user to edit the annotation immediately after creating it.
 */
jQuery(document).ajaxComplete(function(event, jqXHR, ajaxOptions) {
    var jsonDataText = JSON.parse(jqXHR.responseText);

    if (ajaxOptions.type === 'POST' && /\/islandora_web_annotations/.test(ajaxOptions.url)) {
        jQuery('.annotator-wrapper').unblock();

        var jsonData = jsonDataText;

        // Basic error check
        if(typeof jsonData.rows  !== 'undefined'){
            var PID = jsonData.rows[0].pid;
            var checksum = jsonData.rows[0].checksum;
            // Set annotation PID
            ova.annotator.plugins["Store"].annotations[0].pid = PID;
            ova.annotator.plugins["Store"].annotations[0].checksum = checksum;

            var verbose_message = "Annotation successfully created: " + JSON.stringify(jsonData);
            var short_message = "Annotation successfully created.";
            verbose_alert(short_message, verbose_message);
        } else {
            var verbose_message = "Error in creating the annotation: " + JSON.stringify(jsonData);
            var short_message = "Error in creating the annotation.";
            verbose_alert(short_message, verbose_message);
        }

    } else if (ajaxOptions.type === 'PUT' && /\/islandora_web_annotations/.test(ajaxOptions.url)) {
        jQuery('.annotator-wrapper').unblock();

        var jsonData = jsonDataText;
        var status = jsonData.status;
        if(status === undefined){
            alert("Error in updating annotation.  Server failed to return valid response.");
            return;
        }

        var annoInfo = jsonData.data;
        if(status == "success") {
            var pid = annoInfo.pid;
            var checksum = annoInfo.checksum;
            updateChecksum(pid, checksum);

            var verbose_message = "Successfully updated the annotation: " + JSON.stringify(annoInfo);
            var short_message = "Update successful.";
            verbose_alert(short_message, verbose_message);
        } else if(status == "conflict"){
            var msg = "There was an edit conflict.  Please hover over the annotation you edited, copy the content, refresh annotations page and try updating again.";
            verbose_alert(msg, msg);
        } else {
            var verbose_message = "Unable to update.  Error info: " + JSON.stringify(annoInfo);
            var short_message = "Error: Unable to update.";
            verbose_alert(short_message, verbose_message);
        }

    } else if (ajaxOptions.type === 'DELETE' && /\/islandora_web_annotations/.test(ajaxOptions.url)) {
        var jsonData = JSON.parse(jsonDataText);
        var status = jsonData.status;
        if(status === undefined){
            alert("Error in deleting annotation.  Server failed to return valid response.");
            return;
        }
        var annoInfo = jsonData.data;

        if(status == "success") {
            var verbose_message = "Success! " + JSON.stringify(annoInfo);
            var short_message = "Annotation successfully deleted.";
            verbose_alert(short_message, verbose_message);
        } else if(status == "conflict"){
            var msg = "There was an edit conflict.  Please reload the annotations to view the changes.  You can try again to delete.";
            verbose_alert(msg, msg);
        } else {
            var verbose_message = "Unable to delete.  Error info: "  + JSON.stringify(annoInfo);
            var short_message = "Error: Unable to delete.";
            verbose_alert(short_message, verbose_message);
        }
    }
});

/**
 * After an annotation is added the checksum is updated in the UI.
 *
 * @param pid
 * @param checksum
 * return None
 */
function updateChecksum(pid, checksum) {
    var annosLength = ova.annotator.plugins["Store"].annotations.length;
    for(var j = 0; j < annosLength; j++){
        var annoPID = ova.annotator.plugins["Store"].annotations[j].pid;
        if(annoPID == pid) {
            ova.annotator.plugins["Store"].annotations[j].checksum = checksum;
            delete ova.annotator.plugins["Store"].annotations[j].status;
            delete ova.annotator.plugins["Store"].annotations[j].data;
            break;
        }
    }
}

function positionAnnotatorForm(formSelector){
    var left = jQuery(".vjs-selectionbar-RS").first().css("left");
    left = left.substr(0, left.length - 2);
    var width = jQuery(".vjs-selectionbar-RS").first().width();
    var newleft = Number(left) + Number(width) / 2;
    jQuery(formSelector).first().css({left: newleft + "px"});

    var height = jQuery(document.getElementsByTagName("video")[0]).height();
    var top = height - 20;

    if(formSelector === ".annotator-editor") {
        top = top - 25;
    }
    jQuery(formSelector).first().css({top: top + "px"});

}

function applyBlock(actionType){
    var msg = '<h1>Annotation is being ' + actionType + '.  Please wait.....</h1>';

    jQuery('.annotator-wrapper').block({
        message: msg,
        css: {
            border: 'none',
            width: '400px',
            padding: '15px',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px'
        }
    });
}

/**
 * Common Methods
 */

function verbose_alert(short_message, verbose_message) {
    var verbose_flag = Drupal.settings.islandora_web_annotations.verbose_messages;
    if (verbose_flag) {
        alert(verbose_message);
    } else {
        showGrowlMsg(short_message);
    }
}

function showGrowlMsg(i_msg) {

    jQuery.blockUI({
        message: i_msg,
        fadeIn: 700,
        fadeOut: 700,
        timeout: 2000,
        showOverlay: false,
        centerY: false,
        css: {
            width: '400px',
            bottom: '10px',
            top: '-100',
            left: '',
            right: '10px',
            height: '50px',
            border: 'none',
            padding: '5px',
            backgroundColor: '#000',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px',
            opacity: .6,
            color: '#fff'
        }
    });

}


/**
 * Utility method
 *
 * @param param_name
 * @param url
 * @returns {*}
 */
function getParameterByName(param_name, url) {
    if (!url) url = window.location.href;
    param_name = param_name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + param_name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}