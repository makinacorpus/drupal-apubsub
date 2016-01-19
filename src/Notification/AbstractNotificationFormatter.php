<?php

namespace MakinaCorpus\Drupal\APubSub\Notification;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\APubSub\Notification\FormatterInterface;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Base class for Drupal notification formatters, based upon the fact that
 * you will need a few Drupal helper to render text correctly.
 */
abstract class AbstractNotificationFormatter implements FormatterInterface
{
    use StringTranslationTrait;

    /**
     * {inheritdoc}
     */
    public function getImageURI(NotificationInterface $notification)
    {
        // Default null implementation
    }

    /**
     * Return user account name for given uid
     *
     * @param NotificationInterface $notification
     * @param string $keyInData
     *   Key in notification data in which to search for user names
     *
     * @return string
     */
    protected function getUserAccountName(NotificationInterface $notification, $keyInData = 'uid')
    {
        $names = [];
        $count = 0;

        if (isset($notification[$keyInData])) {

            $uidList  = $notification[$keyInData];
            $count    = count($uidList);

            if (!is_array($uidList)) {
                $uidList = [$uidList];
            }

            $names = array_map('format_username', user_load_multiple($uidList));
        }

        if ($count) {
            return $this->getTitleString($names, $count);
        }
    }

    /**
     * Get maximum number of titles displayed, after which a placeholder
     * with "and X more" is being displayed
     *
     * @return int
     */
    protected function getLimit()
    {
        return 3;
    }

    /**
     * Get title string
     *
     * @param string $titles
     * @param int $count
     *
     * @return string
     */
    protected function getTitleString($titles, $count)
    {
        $limit      = $this->getLimit();
        $okCount    = count($titles);
        $missing    = $count - $okCount;
        $andCount   = max([0, $okCount - $limit]) + $missing;

        if (!$okCount) {
            return $count;
        }
        if ($count < 2) {
            return reset($titles);
        }
        if ($okCount === 2 && !$andCount) {
            return $this->t("@name1 and @name2", array_combine(['@name1', '@name2'], $titles));
        }

        if ($limit < $okCount) {
            $titles = array_slice($titles, 0, $limit);
        }

        $ret = implode(", ", $titles);

        if ($andCount) {
            $ret .= ' ' . $this->formatPlural($andCount, "and @count more", "and @count more");
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    final public function format(NotificationInterface $notification)
    {
        $idList = $notification->getResourceIdList();
        $count  = count($idList);

        $args = [
            '%id'     => $this->getTitleString($idList, $count),
            '%title'  => $this->getTitleString($this->getTitles($idList), $count),
            '@count'  => $count,
        ];

        if ($count < 2) {
            $string = $this->getSingleString($notification, $args);

            return $this->t($string, $args);

        } else {
            list($singular, $plural) = $this->getPluralString($notification, $args);

            return $this->formatPlural($count, $singular, $plural, $args);
        }
    }

    /**
     * Get title list
     *
     * Given the resource type (for example 'node') return a list of element
     * titles, whose keys are identifiers. If some elements are missing
     * from the database, just don't set them into the return array.
     *
     * @param scalar[] $idList
     *
     * @return string[]
     *   Keys are identifiers from the input array, values are
     */
    protected function getTitles($idList)
    {
        return [];
    }

    /**
     * Get translation english source for text when a single element is changed
     *
     * @param NotificationInterface $notification
     * @param string[] $args
     *   Translation arguments, container per default:
     *     - '%id' : resource identifier
     *     - '%title' : resource title returned by getTitles()
     *   You may modify the array and add more, this will be directly used when
     *   calling the $this->t() method as the arguments parameter.
     *
     * @return string
     *   Translatable english string that may contain variables, per default
     *   %id and %title will be directly translated, but you might want to add
     *   more, case in which you need to add them into $args
     */
    abstract protected function getSingleString(NotificationInterface $notification, array &$args = []);

    /**
     * Get translation english plural forms sources when multiple element have
     * changed
     *
     * @param NotificationInterface $notification
     * @param string[] $args
     *   Translation arguments, container per default:
     *     - '%id' : resource identifier
     *     - '%title' : plural formated string containing getTitles() return
     *     - '@count' : number of elements
     *   You may modify the array and add more, this will be directly used when
     *   calling the $this->t() method as the arguments parameter.
     *
     * @return string[]
     *   First key is singular, second is plural they must be english strings
     *   that may contain variables, per default %id and %title will be directly
     *   translated, but you might want to add more, case in which you need to
     *   add them into $args
     */
    abstract protected function getPluralString(NotificationInterface $notification, array &$args = []);
}
