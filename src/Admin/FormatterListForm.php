<?php

namespace MakinaCorpus\Drupal\APubSub\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\NotificationService;

use Symfony\Component\DependencyInjection\ContainerInterface;

class FormatterListForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('apb.notification')
        );
    }

    /**
     * @var NotificationService
     */
    private $service;

    /**
     * Default constructor
     *
     * @param NotificationService $service
     */
    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'notification_admin_formatter_list_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $registry = $this->service->getFormatterRegistry();
        $typeList = $registry->getTypeList();

        $label_disabled = $this->t("Disabled");
        $label_enabled  = '<strong>' . $this->t("Enabled") . '</strong>';

        $options = [];
        foreach ($typeList as $type) {
            try {
                $options[$type]['type']   = $type;
                $options[$type]['status'] = $label_enabled;
                $options[$type]['status'] = true ? $label_enabled : $label_disabled; // FIXME
            } catch (Exception $e) {
                // @todo ?
            }
        }

        $form['types'] = [
            '#type'    => 'tableselect',
            '#header'  => [
                'type'   => $this->t("Type"),
                'status' => $this->t("Status"),
            ],
            '#options' => $options,
        ];

        $form['actions'] = [
            '#type'   => 'actions',
        ];
        $form['actions']['enable'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Enable"),
            '#submit' => ['::enableSubmit'],
        ];
        $form['actions']['disable'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Disable"),
            '#submit' => ['::disableSubmit'],
        ];

        return $form;
    }

    /**
     * Notification types admin form enable selected submit handler
     */
    public function enableSubmit(array &$form, FormStateInterface $form_state)
    {
        $types = [];
        foreach ($form_state->getValue('types') as $type => $selected) {
            if ($type === $selected) {
                $types[] = $type;
            }
        }

        if (empty($types)) {
            drupal_set_message($this->t("Nothing to do"));
            return;
        }

        $disabled = variable_get(APB_VAR_ENABLED_TYPES, []);
        $disabled = array_diff($disabled, $types);

        if (empty($disabled)) {
            variable_set(APB_VAR_ENABLED_TYPES, null);
        } else {
            variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
        }

        drupal_set_message($this->t("Enabled selected types"));
    }

    /**
     * Notification types admin form disable selected submit handler
     */
    public function disableSubmit(array &$form, FormStateInterface $form_state)
    {
        $types = array();
        foreach ($form_state->getValue('types') as $type => $selected) {
            if ($type === $selected) {
                $types[] = $type;
            }
        }

        if (empty($types)) {
            drupal_set_message($this->t("Nothing to do"));
            return;
        }

        $disabled = variable_get(APB_VAR_ENABLED_TYPES, []);
        $disabled += $types;

        if (empty($disabled)) {
            variable_set(APB_VAR_ENABLED_TYPES, null);
        } else {
            variable_set(APB_VAR_ENABLED_TYPES, array_unique($disabled));
        }

        drupal_set_message($this->t("Disabled selected types"));
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
