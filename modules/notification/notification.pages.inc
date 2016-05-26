<?php

/**
 * @file
 * User pages.
 */

use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Field;

/**
 * User notifications page.
 */
function notification_page_user_list($form, &$form_state, $account) {

  $service     = notification_service_get();
  $displayed   = 0;
  $total       = 0;
  $limit       = variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT);
  $element     = PagerDefault::$maxElement++;
  $offset      = pager_find_page($element) * $limit;
  $unmarkAll   = variable_get(APB_VAR_UNREAD_UNFOLD_ALL, APB_DEF_UNREAD_UNFOLD);
  $unmark      = $unmarkAll || variable_get(APB_VAR_UNREAD_UNFOLD, APB_DEF_UNREAD_UNFOLD);

  $cursor = $service
    ->fetchForSubscriber($account->uid)
  ;
  $cursor
    ->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC)
    ->setRange($limit, $offset)
  ;

  $total = $cursor->getTotalCount();
  pager_default_initialize($total, $limit);

  $form['#tree']  = true;
  $form['#theme'] = 'notification_page';
  $form['list'] = [
    '#type'    => 'checkboxes',
    '#options' => [],
  ];
  $form['uid'] = [
    '#type'  => 'value',
    '#value' => $account->uid,
  ];

  foreach ($cursor as $notification) {
    ++$displayed;

    if ($uri = $notification->getImageUri()) {
      $image = $notification->getImageURI();
    } else {
      $image = '';
    }

    if ($uri = $notification->getURI()) {
      $link = url($uri);
    } else {
      $link = null;
    }

    $message = $notification->getMessage();
    $form['list']['#options'][$message->getId()] = [
      'image'   => $image,
      'time'    => $message->getSendDate()->getTimestamp(),
      'unread'  => $message->isUnread(),
      'text'    => $notification->format(),
      'type'    => $message->getType(),
      'link'    => $link,
    ];

    if (!$unmarkAll && $unmark && $message->isUnread()) {
      $message->setUnread(false);
    }
  }

  if ($unmarkAll) {
    $cursor->update([Field::MSG_UNREAD => 0]);
  }

  $form['top']['#markup'] = format_plural(
    $total,
    "Displaying @displayed of @count notification",
    "Displaying @displayed of @count notifications",
    ['@displayed' => $displayed]
  );

  $form['pager']['#markup'] = theme('pager');

  $form['actions'] = [
    '#type'  => 'actions',
    'unread_all' => [
      '#type'   => 'submit',
      '#value'  => t("Unread ALL"),
      '#submit' => ['notification_page_user_list_submit_unread_all'],
    ],
    'unread' => [
      '#type'   => 'submit',
      '#value'  => t("Unread"),
      '#submit' => ['notification_page_user_list_submit_unread'],
    ],
    'delete' => [
      '#type'  => 'submit',
      '#value' => t("Delete"),
      '#submit' => ['notification_page_user_list_submit_delete'],
    ],
  ];

  return $form;
}

function notification_page_user_list_submit_unread_all($form, &$form_state) {
  notification_service_get()
    ->getSubscriber($form_state['values']['uid']->currentUser()->id())
    ->fetch()
    ->update([Field::MSG_UNREAD => false])
  ;
}

/**
 * User notifications page mark as unread submit handler.
 */
function notification_page_user_list_submit_unread($form, &$form_state) {

  $service    = notification_service_get();
  $subscriber = $service->getSubscriber($form_state['values']['uid']);
  $count      = 0;

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;

      $cursor = $subscriber->fetch([Field::MSG_ID => $msgid]);

      foreach ($cursor as $message) {
        $message->setUnread(false);
      }
    }
  }

  drupal_set_message(format_plural($count,
    "@count message marked as unread",
    "@count messages marked as unread"
  ));
}

/**
 * User notifications page delete submit handler.
 */
function notification_page_user_list_submit_delete($form, &$form_state) {

  $service    = notification_service_get();
  $subscriber = $service->getSubscriber($form_state['values']['uid']);
  $count      = 0;
  $list       = [];

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;
      $list[] = $msgid;
    }
  }

  if ($list) {
    $subscriber->fetch([Field::MSG_ID => $list])->delete();
  }

  drupal_set_message(format_plural($count,
    "@count message deleted",
    "@count messages deleted"
  ));
}

/**
 * Implements template_preprocess_HOOK().
 */
function template_preprocess_notification_page(&$variables) {

  $form = &$variables['form'];

  foreach (element_children($form['list']) as $key) {
    // Should have already been expanded.
    // Sad checkbox is sad.
    $item = &$form['list'][$key]['#title'];
    unset($form['list'][$key]['#title']);

    $item['checkbox'] = drupal_render($form['list'][$key]);

    $variables['list'][] = $item;
  }

  $variables['top']   = drupal_render($form['top']);
  $variables['pager'] = drupal_render($form['pager']);
  $variables['form']  = drupal_render_children($form);
}
