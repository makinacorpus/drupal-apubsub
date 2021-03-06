<?php

namespace MakinaCorpus\Drupal\APubSub\Notification;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Sample usage of the AbstractNotificationFormatter
 *
 * In order to use it, place into your services.yml file:
 *
 * @code
 * services:
 *   my_module.notification.node_updated:
 *   class: MakinaCorpus\Drupal\APubSub\Notification\NodeUpdated
 *   arguments: ["@entity.manager"]
 *   tags: [{ name: apb.notification.formatter, event: "node:update", auto: true }]
 * @endcode
 *
 * And write in some hook the following code:
 *
 * @code
 * function ucms_notification_node_update($node) {
 *   // @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface
 *   $dispatcher = \Drupal::service('event_dispatcher');
 *   $dispatcher->dispatch('node:update', new GenericEvent($node, ['id' => $node->nid]));
 * }
 * @endcode
 */
class NodeUpdated extends AbstractNotificationFormatter
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getURI(NotificationInterface $interface)
    {
        $nodeIdList = $interface->getResourceIdList();

        if (count($nodeIdList) === 1) {
            return 'node/' . reset($nodeIdList);
        }
    }

    /**
     * {inheritdoc}
     */
    protected function getTitles($idList)
    {
        $ret = [];

        foreach ($this->entityManager->getStorage('node')->load($idList) as $node) {
            $ret[$node->nid] = entity_label('node', $node);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    protected function getTypeLabelVariations($count)
    {
        return [
            "@count content",
            "@count contents",
        ];
    }

    /**
     * {inheritdoc}
     */
    protected function getVariations(NotificationInterface $notification, array &$args = [])
    {
        if ($name = $this->getUserAccountName($notification)) {
            $args['@name'] = $name;
            return [
                "@title has been modified by @name",
                "@title have been modified by @name",
            ];
        } else {
            return [
                "@title has been modified",
                "@title have been modified",
            ];
        }
    }
}
