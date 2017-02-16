<?php

/**
 * @file
 * Handles the display/submission of the admin settings form for this module.
 */

/**
 * Defines the admin settings form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 *
 * @return array
 *   The Drupal form definition.
 */
function islandora_web_annotations_admin(array $form, array &$form_state) {
  module_load_include('inc', 'islandora', 'includes/solution_packs');
  $form = array();
  $form['islandora_web_annotations_verbose'] = array(
      '#type' => 'checkbox',
      '#title' => t('Verbose Alert Messages'),
      '#description' => t('Provides verbose (alert) messages, useful when testing, debuging and developing.'),
      '#default_value' => variable_get('islandora_web_annotations_verbose', FALSE),
  );
  $form['islandora_web_annotations_namespace'] = array(
      '#type' => 'textfield',
      '#title' => t('Annotation Namespace'),
      '#description' => t('Sets the namespace to use for annotation objects.  Defaults to \'annotation.\''),
      '#default_value' => variable_get('islandora_web_annotations_namespace', 'annotation'),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset to defaults'),
      '#weight' => 1,
      '#submit' => array('islandora_web_annotations_admin_reset'),
  );

  $form = system_settings_form($form);
  return $form;

}

/**
 * Form reset for allowing the deletion of the viewer variable.
 */
function islandora_web_annotations_admin_reset($form, &$form_state)
{
  $op = $form_state['clicked_button']['#id'];
  switch ($op) {
    case 'edit-reset':
      variable_del('islandora_web_annotations_verbose');
      break;
  }
}
