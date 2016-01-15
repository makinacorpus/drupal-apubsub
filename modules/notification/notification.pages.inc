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
  $subscriber  = $service->getSubscriberFor(ACCOUNT_SUB, $account->uid);
  $displayed   = 0;
  $total       = 0;
  $limit       = variable_get(APB_VAR_USER_PAGER_LIMIT, APB_DEF_USER_PAGER_LIMIT);
  $element     = PagerDefault::$maxElement++;
  $offset      = pager_find_page($element) * $limit;
  $unmarkAll   = variable_get(APB_VAR_UNREAD_UNFOLD_ALL, APB_DEF_UNREAD_UNFOLD);
  $unmark      = $unmarkAll || variable_get(APB_VAR_UNREAD_UNFOLD, APB_DEF_UNREAD_UNFOLD);

  $cursor = $subscriber
    ->fetch()
    ->addSort(
        Field::MSG_SENT,
        CursorInterface::SORT_DESC)
    ->setRange($limit, $offset);

  $total = $cursor->getTotalCount();
  pager_default_initialize($total, $limit);

  $form['#tree']  = TRUE;
  $form['#theme'] = 'notification_page';
  $form['list'] = array(
    '#type'    => 'checkboxes',
    '#options' => array(),
  );
  $form['uid'] = array(
    '#type'  => 'value',
    '#value' => $account->uid,
  );

  /* @var $cursor MakinaCorpus\APubSub\MessageInstanceInterface[] */
  foreach ($cursor as $message) {
    $notification = $service->getNotification($message);

    ++$displayed;

    if ($uri = $notification->getImageUri()) {
      $image = notification_image_render($uri);
    } else {
      $image = '';
    }

    $form['list']['#options'][$message->getId()] = array(
      'image'  => $image,
      'time'   => $message->getSendDate()->getTimestamp(),
      'unread' => $message->isUnread(),
      'text'   => $notification->format(),
      'type'   => $notification->getType(),
    );

    if (!$unmarkAll && $unmark && $message->isUnread()) {
      $message->setUnread(false);
    }
  }

  if ($unmarkAll) {
    $subscriber
      ->fetch()
      ->update(array(Field::MSG_UNREAD => 0));
  }

  $form['top']['#markup'] = format_plural(
    $total,
    "Displaying @displayed of @count notification",
    "Displaying @displayed of @count notifications",
    array('@displayed' => $displayed));

  $form['pager']['#markup'] = theme('pager');

  $form['actions'] = array(
    '#type'  => 'actions',
    'unread' => array(
      '#type'   => 'submit',
      '#value'  => t("Mark as unread"),
      '#submit' => array('notification_page_user_list_submit_unread'),
    ),
    'delete' => array(
      '#type'  => 'submit',
      '#value' => t("Delete"),
      '#submit' => array('notification_page_user_list_submit_delete'),
    ),
    'preferences' => array(
      '#type'  => 'link',
      '#title' => t("Preferences"),
      '#href'  => 'user/' . $account->uid . '/notifications/settings',
    ),
  );

  return $form;
}

/**
 * User notifications page mark as unread submit handler.
 */
function notification_page_user_list_submit_unread($form, &$form_state) {

  $service    = notification_service_get();
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $form_state['values']['uid']);
  $count      = 0;

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;

      $cursor = $subscriber
        ->fetch(array(
            Field::MSG_ID => $msgid,
        ));

      foreach ($cursor as $message) {
        $message->setUnread(false);
      }
    }
  }

  drupal_set_message(format_plural($count,
    "@count message marked as unread",
    "@count messages marked as unread"));
}

/**
 * User notifications page delete submit handler.
 */
function notification_page_user_list_submit_delete($form, &$form_state) {

  $service    = notification_service_get();
  $subscriber = $service->getSubscriberFor(ACCOUNT_SUB, $form_state['values']['uid']);
  $count      = 0;
  $list       = array();

  foreach ($form_state['values']['list'] as $msgid => $enabled) {
    if ($msgid == $enabled) {
      ++$count;
      $list[] = $msgid;
    }
  }

  if ($list) {
    $subscriber
      ->fetch(array(
        Field::MSG_ID => $list,
      ))
      ->delete();
  }

  drupal_set_message(format_plural($count,
    "@count message deleted",
    "@count messages deleted"));
}

/**
 * User settings for subscriptions
 */
function notification_page_user_settings($form, &$form_state, $account) {

  $form['uid'] = array(
    '#type'  => 'value',
    '#value' => $account->uid,
  );

  $form['actions'] = array(
    '#type'   => 'actions',
  );
  $form['actions']['save'] = array(
    '#type'   => 'submit',
    '#value'  => t("Save"),
  );

  return $form;
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
