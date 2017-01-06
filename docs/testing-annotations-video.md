# Testing annotations - video

## Context
```
$context = new stdClass();
$context->disabled = FALSE; /* Edit this to true to make a default context disabled initially */
$context->api_version = 3;
$context->name = 'video2';
$context->description = '';
$context->tag = '';
$context->conditions = array(
  'islandora_context_condition_collection_member' => array(
    'values' => array(
      0 => TRUE,
    ),
    'options' => array(
      'islandora_collection_membership' => array(
        'islandora:video_collection' => 'islandora:video_collection',
        'islandora:entity_collection' => 0,
        'islandora:audio_collection' => 0,
        'islandora:bookCollection' => 0,
        'islandora:sp_disk_image_collection' => 0,
        'islandora:newspaper_collection' => 0,
        'islandora:compound_collection' => 0,
        'islandora:sp_large_image_collection' => 0,
        'islandora:sp_web_archive_collection' => 0,
        'islandora:sp_basic_image_collection' => 0,
        'islandora:sp_pdf_collection' => 0,
        'ir:citationCollection' => 0,
      ),
    ),
  ),
);
$context->reactions = array(
  'css_path' => array(
    'sites/all/libraries/web-annotaions/libs/annotorious/js' => array(),
    'sites/all/libraries/web-annotaions/libs/annotorious/css' => array(),
    'sites/all/modules/islandora_web_annotations/lib' => array(
      'sites/all/modules/islandora_web_annotations/lib/css/video-js/video-js.css' => 'sites/all/modules/islandora_web_annotations/lib/css/video-js/video-js.css',
      'sites/all/modules/islandora_web_annotations/lib/css/annotator.min.css' => 'sites/all/modules/islandora_web_annotations/lib/css/annotator.min.css',
      'sites/all/modules/islandora_web_annotations/lib/css/rangeslider.min.css' => 'sites/all/modules/islandora_web_annotations/lib/css/rangeslider.min.css',
      'sites/all/modules/islandora_web_annotations/lib/css/richText-annotator.min.css' => 'sites/all/modules/islandora_web_annotations/lib/css/richText-annotator.min.css',
      'sites/all/modules/islandora_web_annotations/lib/css/ova.css' => 'sites/all/modules/islandora_web_annotations/lib/css/ova.css',
    ),
    'sites/all/modules/islandora_web_annotations/css' => array(),
  ),
  'js_path' => array(
    'sites/all/libraries/web-annotaions/libs/annotorious/js' => array(),
    'sites/all/modules/islandora_web_annotations/lib' => array(
      'sites/all/modules/islandora_web_annotations/lib/js/annotator-full.min.js' => 'sites/all/modules/islandora_web_annotations/lib/js/annotator-full.min.js',
      'sites/all/modules/islandora_web_annotations/lib/js/tinymce.min.js' => 'sites/all/modules/islandora_web_annotations/lib/js/tinymce.min.js',
      'sites/all/modules/islandora_web_annotations/lib/js/ova.js' => 'sites/all/modules/islandora_web_annotations/lib/js/ova.js',
      'sites/all/modules/islandora_web_annotations/lib/js/richText-annotator.min.js' => 'sites/all/modules/islandora_web_annotations/lib/js/richText-annotator.min.js',
      'sites/all/modules/islandora_web_annotations/lib/js/rangeslider.min.js' => 'sites/all/modules/islandora_web_annotations/lib/js/rangeslider.min.js',
    ),
    'sites/all/modules/islandora_web_annotations/js' => array(
      'sites/all/modules/islandora_web_annotations/js/video/video.js' => 'sites/all/modules/islandora_web_annotations/js/video/video.js',
    ),
  ),
);
$context->condition_mode = 0;

```
