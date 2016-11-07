
jQuery(document).ready(function() {
    $ = jQuery;
    //Options to load in Open Video Annotation, for all the plugins

    jQuery("#islandora_videojs_html5_api").addClass("video-js");
    jQuery("#islandora_videojs_html5_api").attr("preload", "none");


    preload="none"


    var options = {
        optionsAnnotator: {
            permissions: { },

            //auth: {tokenUrl:'http://catch.aws.af.cm/annotator/token'},

            store: {
                // The endpoint of the store on your server.
                //prefix: 'http://afstore.aws.af.cm/annotator',
                prefix: 'http://danielcebrian.com/annotations/api',

                annotationData: {uri:'http://danielcebrian.com/annotations/demo.html'},

                /*urls: {
                 // These are the default URLs.
                 create:  '/create',
                 read:    '/read/:id',
                 update:  '/update/:id',
                 destroy: '/destroy/:id',
                 search:  '/search'
                 },*/

                loadFromSearch:{
                    limit:10000,
                    uri: 'http://danielcebrian.com/annotations/demo.html',
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
    var ova = new OpenVideoAnnotation.Annotator(jQuery("div[class='islandora-video-content']")[0], options);

    //change the user (Experimental)
    ova.setCurrentUser("Nat");
    $('#username').change(function () {
        ova.setCurrentUser($(this).val());
    });

});