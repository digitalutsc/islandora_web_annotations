
jQuery(document).ready(function() {

    var m_image = jQuery("div[class='islandora-basic-image-content']").find("img[typeof='foaf:Image']").first();

    jQuery(m_image).unwrap();

    anno.makeAnnotatable(m_image[0]);

});