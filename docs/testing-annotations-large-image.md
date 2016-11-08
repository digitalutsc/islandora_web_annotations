#Testing large image annotations - OpenSeaDragon viewer

## Known Issues
* If the user scrolls down on the page and then creates the annotations, then the annotations positions are not vertically alinged properly.  

## Insatllation
* Install [context] (https://www.drupal.org/project/context) drupal module
* Install [context_addassets] (https://www.drupal.org/project/context_addassets) drupal module
* Install [islandora_context] (https://github.com/mjordan/islandora_context) drupal module

When you isntall this module, you will get a Notices such as "Notice: Use of undefined constant ISLANDORA_REST_OBJECT_GET_PERM - assumed 'ISLANDORA_REST_OBJECT_GET_PERM'".  Please ignore that for now.  There is an issue created with the module about this.

* Install [islandora_web_annotations] (https://github.com/digitalutsc/islandora_web_annotations) drupal module

* annotorious.github.io library does not work with the OpenSeaDragon versions less than 1.0.  [v2.0.0 OpenSeaDragon] (https://github.com/openseadragon/openseadragon/releases/tag/v2.0.0) is recommended.  Note that this is NOT the recommended version for islandora_openseadragon module.  

## Creating the context needed by basic_image annotations
Go to admin/structure/context and import the following context.

```php
$context = new stdClass();
$context->disabled = FALSE; /* Edit this to true to make a default context disabled initially */
$context->api_version = 3;
$context->name = 'large_image';
$context->description = '';
$context->tag = '';
$context->conditions = array(
  'islandora_context_condition_collection_member' => array(
    'values' => array(
      0 => TRUE,
    ),
    'options' => array(
      'islandora_collection_membership' => array(
        'islandora:sp_large_image_collection' => 'islandora:sp_large_image_collection',
        'islandora:entity_collection' => 0,
        'islandora:audio_collection' => 0,
        'islandora:bookCollection' => 0,
        'islandora:sp_disk_image_collection' => 0,
        'islandora:newspaper_collection' => 0,
        'islandora:compound_collection' => 0,
        'islandora:video_collection' => 0,
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
      'sites/all/modules/islandora_web_annotations/lib/css/theme-dark/annotorious-dark.css' => 'sites/all/modules/islandora_web_annotations/lib/css/theme-dark/annotorious-dark.css',
    ),
    'sites/all/modules/islandora_web_annotations/css' => array(
      'sites/all/modules/islandora_web_annotations/css/large_image/large_image.css' => 'sites/all/modules/islandora_web_annotations/css/large_image/large_image.css',
    ),
  ),
  'js_path' => array(
    'sites/all/libraries/web-annotaions/libs/annotorious/js' => array(),
    'sites/all/modules/islandora_web_annotations/lib' => array(
      'sites/all/modules/islandora_web_annotations/lib/js/annotorious.min.js' => 'sites/all/modules/islandora_web_annotations/lib/js/annotorious.min.js',
    ),
    'sites/all/modules/islandora_web_annotations/js' => array(
      'sites/all/modules/islandora_web_annotations/js/large_image/large_image.js' => 'sites/all/modules/islandora_web_annotations/js/large_image/large_image.js',
    ),
  ),
);
$context->condition_mode = 0;
```

## Create a sample image and see if you are able to create annotations
Review [OpenVideoAnnotation] (https://github.com/CtrHellenicStudies/OpenVideoAnnotation)  for detail feature list.
