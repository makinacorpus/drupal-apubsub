<?php
/**
 * @file
 * Administration pages.
 */

/**
 * Notification types admin form.
 */
function notification_admin_notification_notification_types($form, &$form_state) {

  $service  = notification_service_get();
  $registry = $service->getFormatterRegistry();
  $typeList = $registry->getTypeList();

  $label_disabled = t("Disabled");
  $label_enabled  = '<strong>' . t("Enabled") . '</strong>';

  $options = array();
  foreach ($typeList as $type) {
    try {
      $options[$type]['type']   = $type;
      $options[$type]['status'] = $label_enabled;
      $options[$type]['status'] = true ? $label_enabled : $label_disabled; // FIXME
    } catch (Exception $e) {
      // @todo ?
    }
  }

  $form['types'] = array(
    '#type'    => 'tableselect',
    '#header'  => array(
      'type'        => t("Type"),
      'status'      => t("Status"),
    ),
    '#options' => $options,
  );

  $form['actions'] = array(
    '#type'   => 'actions',
  );
  $form['actions']['enable'] = array(
    '#type'   => 'submit',
    '#value'  => t("Enable"),
    '#submit' => array('notification_admin_notification_overview_submit_enable'),
  );
  $form['actions']['disable'] = array(
    '#type'   => 'submit',
    '#value'  => t("Disable"),
    '#submit' => array('notification_admin_notification_overview_submit_disable'),
  );

  return $form;
}

/**
 * Notification types admin form enable selected submit handler.
 */
function notification_admin_notification_overview_submit_enable($form, &$form_state) {

  $types = array();
  foreach ($form_state['values']['types'] as $type => $selected) {
    if ($type === $selected) {
      $types[] = $type;
    }
  }

  if (empty($types)) {
    drupal_set_message(t("Nothing to do"));
    return;
  }

  $disabled = variable_get(APB_VAR_ENABLED_TYPES, array());
  $disabled = array_diff($disabled, $types);

  if (empty($disabled)) {
    variable_set(APB_VAR_ENABLED_TYPES, NULL);
  } else {
    variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
  }

  drupal_set_message(t("Enabled selected types"));
}

/**
 * Notification types admin form disable selected submit handler.
 */
function notification_admin_notification_overview_submit_disable($form, &$form_state) {

  $types = array();
  foreach ($form_state['values']['types'] as $type => $selected) {
    if ($type === $selected) {
      $types[] = $type;
    }
  }

  if (empty($types)) {
    drupal_set_message(t("Nothing to do"));
    return;
  }

  $disabled = variable_get(APB_VAR_ENABLED_TYPES, array());
  $disabled += $types;

  if (empty($disabled)) {
    variable_set(APB_VAR_ENABLED_TYPES, NULL);
  } else {
    variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
  }

  drupal_set_message(t("Disabled selected types"));
}

/**
 * Global settings form.
 */
function notification_admin_notification_settings($form, &$form_state) {

  $form['interface'] = array(
    '#type'  => 'fieldset',
    '#title' => t("User interface"),
  );
  $form['interface'][APB_VAR_USER_PAGER_LIMIT] = array(
    '#type'          => 'select',
    '#title'         => t("User notification page limit"),
    '#options'       => drupal_map_assoc(array(5, 10, 20, 30, 50, 100)),
    '#description'   => t("Number of notifications being displayed per page in user notification page."),
    '#default_value' => variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT),
  );
  $form['interface'][APB_VAR_USER_BLOCK_LIMIT] = array(
    '#type'          => 'select',
    '#title'         => t("User notification block limit"),
    '#options'       => drupal_map_assoc(array(3, 5, 8, 10, 13, 15, 20)),
    '#description'   => t("Number of notifications being displayed per in the user notification block."),
    '#default_value' => variable_get(APB_VAR_USER_BLOCK_LIMIT, APB_DEF_USER_BLOCK_LIMIT),
  );
  $form['interface'][APB_VAR_IMG_STYLE] = array(
    '#type'          => 'select',
    '#title'         => t("Notification image style"),
    '#options'       => image_style_options(TRUE),
    '#description'   => t("Number of notifications being displayed per page in user notification page."),
    '#default_value' => variable_get(APB_VAR_IMG_STYLE, APB_DEF_IMG_STYLE),
  );

  $form['advanced'] = array(
    '#type'  => 'fieldset',
    '#title' => t("Advanced / Performance"),
  );
  $form['advanced'][APB_VAR_ENABLE_PROD] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Enable production mode"),
    '#description'   => t("Will suppress some warnings, errors and exceptions. Always check this option on a production site."),
    '#default_value' => variable_get(APB_VAR_ENABLE_PROD, APB_DEF_ENABLE_PROD),
  );
  $form['advanced'][APB_VAR_ENABLE_FORMATTED_CONTENT] = array(
    '#type'          => 'checkbox',
    '#title'         => t("Store content inside messages"),
    '#description'   => t("If checked, formatted messages will be sent in the message contents instead of being formatted at view time. This will bypass translation but allows you to display those notifications outside of the originating website. If checked generated links inside formatted notifications will all be absolute. Note that if you change this setting and data has already been sent some notifications might not be displayed correctly."),
    '#default_value' => variable_get(APB_VAR_ENABLE_FORMATTED_CONTENT, APB_DEF_ENABLE_FORMATTED_CONTENT),
  );

  return system_settings_form($form);
}

