<?php

namespace MakinaCorpus\Drupal\APubSub\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NotificationConfigForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'notification_admin_config_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#form_horizontal'] = true;

        $form['interface'] = [
            '#type'  => 'fieldset',
            '#title' => $this->t("User interface"),
        ];
        $form['interface'][APB_VAR_USER_PAGER_LIMIT] = [
            '#type'          => 'select',
            '#title'         => $this->t("User notification page limit"),
            '#options'       => drupal_map_assoc([5, 10, 20, 30, 50, 100]),
            '#description'   => $this->t("Number of notifications being displayed per page in user notification page."),
            '#default_value' => variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT),
        ];
        $form['interface'][APB_VAR_USER_BLOCK_LIMIT] = [
            '#type'          => 'select',
            '#title'         => $this->t("User notification block limit"),
            '#options'       => drupal_map_assoc([3, 5, 8, 10, 13, 15, 20]),
            '#description'   => $this->t("Number of notifications being displayed per in the user notification block."),
            '#default_value' => variable_get(APB_VAR_USER_BLOCK_LIMIT, APB_DEF_USER_BLOCK_LIMIT),
        ];
        $form['interface'][APB_VAR_IMG_STYLE] = [
            '#type'          => 'select',
            '#title'         => $this->t("Notification image style"),
            '#empty_option'  => $this->t("Do not display image"),
            '#options'       => image_style_options(true),
            '#description'   => $this->t("Number of notifications being displayed per page in user notification page."),
            '#default_value' => variable_get(APB_VAR_IMG_STYLE, APB_DEF_IMG_STYLE),
        ];

        $form['advanced'] = [
            '#type'  => 'fieldset',
            '#title' => $this->t("Advanced / Performance"),
        ];
        $form['advanced'][APB_VAR_ENABLE_PROD] = [
            '#type'          => 'checkbox',
            '#title'         => $this->t("Enable production mode"),
            '#description'   => $this->t("Will suppress some warnings, errors and exceptions. Always check this option on a production site."),
            '#default_value' => variable_get(APB_VAR_ENABLE_PROD, APB_DEF_ENABLE_PROD),
        ];
        $form['advanced'][APB_VAR_ENABLE_FORMATTED_CONTENT] = [
            '#type'          => 'checkbox',
            '#title'         => $this->t("Store content inside messages"),
            '#description'   => $this->t("If checked, formatted messages will be sent in the message contents instead of being formatted at view time. This will bypass translation but allows you to display those notifications outside of the originating website. If checked generated links inside formatted notifications will all be absolute. Note that if you change this setting and data has already been sent some notifications might not be displayed correctly."),
            '#default_value' => variable_get(APB_VAR_ENABLE_FORMATTED_CONTENT, APB_DEF_ENABLE_FORMATTED_CONTENT),
        ];

        return system_settings_form($form);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
