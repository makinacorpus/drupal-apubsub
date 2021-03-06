<?php
/**
 * This will always be wrapped into a #notifications div, for AJAX handling.
 * If you need to wrap this template into a div, use the div#notifications
 * directly instead, it will avoid you to pollute your own markup.
 */
?>
<div class="top">
  <?php if ($unread_count): ?>
  <div class="unread number" title="<?php echo $title; ?>">
    <?php echo $unread_count; ?>
  </div>
  <?php else: ?>
  <div class="number" title="<?php echo $title; ?>">
    <?php echo $unread_count; ?>
  </div>
  <?php endif; ?>
</div>
<div class="list">
  <ul>
    <li>
      <div class="text">
        <?php echo t("Notifications"); ?>
        <?php if ($total_count): ?>
        (<?php echo t("<strong>@a</strong> of @b", array(
          '@a' => $total_count,
          '@b' => $real_total,
        )); ?>)
        <?php endif; ?>
      </div>
    </li>
    <?php if (empty($list)): ?>
    <li class="empty">
      <div class="text">
        <?php echo t("You have no messages."); ?>
      </div>
    </li>
    <?php else: ?>
    <?php foreach ($list as $item): ?>
    <li class="notification-<?php echo $item['type']; ?>">
      <?php if ($item['link']): ?><a href="<?php echo $item['link']; ?>"><?php endif; ?>
      <div class="image">
        <?php echo render($item['image']); ?>
      </div>
      <div class="text">
        <?php if ($item['unread']): ?>
        <span class="unread">
        <?php echo $item['text']; ?>
        </span>
        <?php else: ?>
        <?php echo $item['text']; ?>
        <?php endif; ?>
        <br/>
        <span class="time">
          <?php echo format_interval(time() - $item['time']); ?>
        </span>
      </div>
      <?php if ($item['link']): ?></a><?php endif; ?>
    </li>
    <?php endforeach; ?>
    <?php endif; ?>
    <li>
      <div class="text">
        <?php echo $all_link; ?>
      </div>
    </li>
  </ul>
  <?php if ($read_form): ?>
    <?php echo render($read_form); ?>
  <?php endif; ?>
</div>