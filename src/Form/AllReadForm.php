<?php

namespace MakinaCorpus\Drupal\APubSub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Notification\NotificationService;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AllReadForm extends FormBase
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
        return 'notification_all_read_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $unreadCount = null)
    {
        if (null !== $unreadCount && !$unreadCount) {
            return;
        }

        $form['actions'] = [
            '#type'   => 'actions',
        ];
        $form['actions']['disable'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Mark all read"),
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this
            ->service
            ->getSubscriber($this->currentUser()->id())
            ->fetch()
            ->update([Field::MSG_UNREAD => false])
        ;
    }
}
