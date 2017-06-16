<?php
/**
 * Created by PhpStorm.
 * User: kpham
 * Date: 2017-06-08
 * Time: 17:35 KIM PHAM - A WORK IN PROGRESS. handler change to islandora_web_annotations_handler_htmlrender if need to render html
 */

/**
 * Implements hook_views_data_alter().
 */
//
//function islandora_web_annotations_views_data_alter(&$data) {
//    watchdog("test", "views inc");
//    $data['islandora_solr']['annotation_value']['field']['handler'] = 'views_handler_field';
//    return $data;
//}

function islandora_web_annotations_views_data_alter(&$data) {
    watchdog("test", "views inc");
//    $data = array();
    $data['islandora_solr']['annotation_value_render'] = array(
        'title' => t('Render html field'),
        'help' => t('Will render in text html.'),
        'field' => array(
            'handler' => 'islandora_web_annotations_handler_htmlrender',
        ),
    );
//    return $data;
}