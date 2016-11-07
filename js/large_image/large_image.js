
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


    var addButton = jQuery('<button id="add-annotation-button" onclick="anno.activateSelector();">Add Annotation</button>');
    addButton.appendTo(jQuery("#openseadragon-wrapper"));

    var os_viewer = Drupal.settings.islandora_open_seadragon_viewer;
    anno.makeAnnotatable(os_viewer);

});