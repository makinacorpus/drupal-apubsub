<?php

namespace MakinaCorpus\Drupal\APubSub\Notification;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\APubSub\Notification\AbstractFormatter;
use MakinaCorpus\APubSub\Notification\NotificationInterface;

/**
 * Base class for Drupal notification formatters, based upon the fact that
 * you will need a few Drupal helper to render text correctly.
 */
abstract class AbstractNotificationFormatter extends AbstractFormatter
{
    use StringTranslationTrait;

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
            list($singular, $plural) = $this->getTypeLabelVariations($count);
            return $this->formatPlural($count, $singular, $plural);
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

        // If the 'titles' variable is cached, rendering will be backend queries
        // free and fast enough to render, this is the whole goal behing this
        // class existence
        $titles = $this->getTitles($idList);

        $args = [
            '@id'     => $this->getTitleString($idList, $count),
            '@title'  => $this->getTitleString($titles, $count),
            '@count'  => $count,
        ];

        list($singular, $plural) = $this->getVariations($notification, $args);

        return $this->formatPlural($count, $singular, $plural, $args);
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
     * When there is no title to display, let's say for users for example, this
     * will be used to display "N persons" for example instead of the "John, Doe
     * and Smith"
     *
     * @return string[]
     *   First value is the singular form, second value is the plural form, both
     *   english strings
     */
    protected function getTypeLabelVariations($count)
    {
        // You are strongly advise to override that!
        return [
            "@count thing",
            "@count things",
        ];
    }

    /**
     * Get translation english plural forms sources for action
     *
     * @param NotificationInterface $notification
     * @param string[] $args
     *   Translation arguments, container per default:
     *     - '@id' : coma-separated list of identifiers
     *     - '@title' : plural formated string containing titles
     *     - '@count' : number of elements
     *   You may modify the array and add more, this will be directly used when
     *   calling the $this->t() method as the arguments parameter.
     *
     * @return string[]
     *   First key is singular, second is plural they must be english strings
     *   that may contain variables, per default @id and @title and @count will
     *   be directly translated, but you might want to add more, case in which
     *   you need to add them into $args
     */
    abstract protected function getVariations(NotificationInterface $notification, array &$args = []);
}
