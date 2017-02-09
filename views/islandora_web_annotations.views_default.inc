<?php
/**
 * @file
 * Default views.
 */

/**
 * Implements hook_views_default_views().
 */
function islandora_web_annotations_views_default_views() {

$view = new view();
$view->name = 'islandora_web_annotations';
$view->description = '';
$view->tag = 'default';
$view->base_table = 'islandora_solr';
$view->human_name = 'Islandora Web Annotation Block';
$view->core = 7;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Master */
$handler = $view->new_display('default', 'Master', 'default');
$handler->display->display_options['title'] = 'Islandora Web Annotation Block';
$handler->display->display_options['use_more_always'] = FALSE;
$handler->display->display_options['access']['type'] = 'none';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['pager']['type'] = 'full';
$handler->display->display_options['pager']['options']['items_per_page'] = '100';
$handler->display->display_options['pager']['options']['offset'] = '0';
$handler->display->display_options['pager']['options']['id'] = '0';
$handler->display->display_options['pager']['options']['quantity'] = '9';
$handler->display->display_options['style_plugin'] = 'list';
$handler->display->display_options['style_options']['type'] = 'ol';
$handler->display->display_options['row_plugin'] = 'fields';
/* Field: Islandora Solr: annotation_value */
$handler->display->display_options['fields']['annotation_value']['id'] = 'annotation_value';
$handler->display->display_options['fields']['annotation_value']['table'] = 'islandora_solr';
$handler->display->display_options['fields']['annotation_value']['field'] = 'annotation_value';
$handler->display->display_options['fields']['annotation_value']['label'] = '';
$handler->display->display_options['fields']['annotation_value']['element_label_colon'] = FALSE;
$handler->display->display_options['fields']['annotation_value']['link_to_object'] = 0;
/* Sort criterion: Islandora Solr: fgs_createdDate_dt */
$handler->display->display_options['sorts']['fgs_createdDate_dt']['id'] = 'fgs_createdDate_dt';
$handler->display->display_options['sorts']['fgs_createdDate_dt']['table'] = 'islandora_solr';
$handler->display->display_options['sorts']['fgs_createdDate_dt']['field'] = 'fgs_createdDate_dt';
/* Contextual filter: Islandora Solr: annotation_parent */
$handler->display->display_options['arguments']['annotation_parent']['id'] = 'annotation_parent';
$handler->display->display_options['arguments']['annotation_parent']['table'] = 'islandora_solr';
$handler->display->display_options['arguments']['annotation_parent']['field'] = 'annotation_parent';
$handler->display->display_options['arguments']['annotation_parent']['default_action'] = 'default';
$handler->display->display_options['arguments']['annotation_parent']['default_argument_type'] = 'raw';
$handler->display->display_options['arguments']['annotation_parent']['default_argument_options']['index'] = '2';
$handler->display->display_options['arguments']['annotation_parent']['summary']['number_of_records'] = '0';
$handler->display->display_options['arguments']['annotation_parent']['summary']['format'] = 'default_summary';
$handler->display->display_options['arguments']['annotation_parent']['summary_options']['items_per_page'] = '25';

/* Display: Block */
$handler = $view->new_display('block', 'Block', 'block_1');

$views['usage_collection'] = $view;

  return $views;
}

