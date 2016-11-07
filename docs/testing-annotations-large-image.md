#Testing large image annotations - OpenSeaDragon Viewer


## Insatllation
* Install [context] (https://www.drupal.org/project/context) drupal module
* Install [context_addassets] (https://www.drupal.org/project/context_addassets) drupal module
* Install [islandora_context] (https://github.com/mjordan/islandora_context) drupal module

When you isntall this module, you will get a Notices such as "Notice: Use of undefined constant ISLANDORA_REST_OBJECT_GET_PERM - assumed 'ISLANDORA_REST_OBJECT_GET_PERM'".  Please ignore that for now.  There is an issue created with the module about this.

* Install [islandora_web_annotations] (https://github.com/digitalutsc/islandora_web_annotations) drupal module

* annotorious.github.io library does not work with the OpenSeaDragon lower than 1.0.  It is recommended that to use [v2.0.0 OpenSeaDragon] (https://github.com/openseadragon/openseadragon/releases/tag/v2.0.0).  Note that this is NOT the recommended version for islandora_openseadragon module.  

## Creating the context needed by basic_image annotations
Go to admin/structure/context and import the following context.
