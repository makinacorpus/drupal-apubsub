<?php

namespace MakinaCorpus\Drupal\APubSub\Notification;

use MakinaCorpus\APubSub\Notification\NotificationService;

class NotificationFactory
{
    public static function getNotificationService(NotificationService $instance)
    {
        // Register all "Drupal Way (TM)" notification types.
        $formatterRegistry = $instance->getFormatterRegistry();
        foreach (notification_type_info_get(APB_INFO_FORMATTER) as $type => $info) {
            $formatterRegistry->registerType($type, $info);
        }

        // Allow other modules to interact with the service in order to for
        // example register contextual subscribers.
        module_invoke_all('notification_init', $instance);

        return $instance;
    }
}
