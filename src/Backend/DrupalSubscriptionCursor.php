<?php

namespace MakinaCorpus\Drupal\APubSub\Backend;

use MakinaCorpus\APubSub\Backend\DefaultSubscription;
use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\APubSub\Misc;

/**
 * Message cursor is a bit tricky: the query will be provided by the caller
 * and may change depending on the source (subscriber or subscription)
 */
class DrupalSubscriptionCursor extends AbstractDrupalCursor
{
    /**
     * @var boolean
     */
    private $distinct = true;

    public function getAvailableSorts()
    {
        return array(
            Field::SUB_ID,
            Field::SUB_STATUS,
            Field::SUB_CREATED_TS,
            Field::CHAN_ID,
        );
    }

    protected function applyConditions(array $conditions)
    {
        $ret = array();

        foreach ($conditions as $field => $value) {
            switch ($field) {

                case Field::SUB_ID:
                    $ret['s.id'] = $value;
                    break;

                case Field::SUB_STATUS:
                    $ret['s.status'] = $value;
                    break;

                case Field::SUB_CREATED_TS:
                    if ($value instanceof \DateTime) {
                        $value = $value->format(Misc::SQL_DATETIME);
                    }
                    $ret['s.created'] = $value;
                    break;

                case Field::CHAN_ID:
                    $ret['c.name'] = $value;
                    break;

                case Field::SUBER_NAME:
                    $ret['s.name'] = $value;
                    break;

                default:
                    trigger_error(sprintf("% does not support filter %d yet",
                        get_class($this), $field));
                    break;
            }
        }

        return $ret;
    }

    protected function applySorts(\SelectQueryInterface $query, array $sorts)
    {
        if (empty($sorts)) {
            $query->orderBy('s.id', 'ASC');
        } else {
            foreach ($sorts as $sort => $order) {

                if ($order === CursorInterface::SORT_DESC) {
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                switch ($sort)
                {
                    case Field::SUB_ID:
                        $query->orderBy('s.id', $direction);
                        break;

                    case Field::SUB_STATUS:
                        $query->orderBy('s.status', $direction);
                        break;

                    case Field::SUB_CREATED_TS:
                        $query->orderBy('s.created', $direction);
                        break;

                    case Field::CHAN_ID:
                        $query->orderBy('c.name', $direction);
                        break;

                    case Field::SUBER_NAME:
                        $query->orderBy('s.name', $direction);
                        break;

                    default:
                        throw new \InvalidArgumentException("Unsupported sort field");
                }
            }
        }
    }

    protected function createObjectInstance(\stdClass $record)
    {
        return new DefaultSubscription(
            $record->name,
            (int)$record->id,
            \DateTime::createFromFormat(Misc::SQL_DATETIME, $record->created),
            \DateTime::createFromFormat(Misc::SQL_DATETIME, $record->activated),
            \DateTime::createFromFormat(Misc::SQL_DATETIME, $record->deactivated),
            $record->accessed ? \DateTime::createFromFormat(Misc::SQL_DATETIME, $record->accessed) : null,
            (bool)$record->status,
            empty($record->name) ? null : $record->name,
            $this->backend
        );
    }

    protected function buildQuery()
    {
        $query = $this
            ->backend
            ->getConnection()
            ->select('apb_sub', 's')
            ->fields('s')
        ;

        // FIXME: Get rid of JOIN, right now must keep it for sort
        $query->join('apb_chan', 'c', 's.chan_id = c.id');
        $query->fields('c', array('name'));

        if ($this->distinct) {
            // Use GROUP BY for better PostgreSQL support
            $query->groupBy('s.id');
        }

        return $query;
    }

    /**
     * Create temporary table from current query
     *
     * @return string New temporary table name, filled in with query primary
     *                identifiers only
     */
    private function createTempTable()
    {
        $query = clone $this->getQuery();
        $query->distinct(false);

        // I am sorry but I have to be punished for I am writing this
        $selectFields = &$query->getFields();
        foreach ($selectFields as $key => $value) {
            unset($selectFields[$key]);
        }
        // Again.
        $tables = &$query->getTables();
        foreach ($tables as $key => $table) {
           unset($tables[$key]['all_fields']);
        }

        $query->fields('s', array('id'));

        // Create a temp table containing identifiers to update: this is
        // mandatory because you cannot use the apb_queue in the UPDATE
        // query subselect
        $cx = $this->backend->getConnection();
        $tempTableName = $cx->queryTemporary((string)$query, $query->getArguments());
        // @todo temporary deactivating this, PostgresSQL does not like it (I
        // have no idea why exactly) - I have to get rid of those temp tables
        // anyway.
        // $cx->schema()->addIndex($tempTableName, $tempTableName . '_idx', array('id'));

        return $tempTableName;
    }

    public function delete()
    {
        $cx = $this->backend->getConnection();
        $tx = null;

        try {
            $tx = $cx->startTransaction();

            // Deleting messages in queue implicates doing it using the queue id:
            // because the 'apb_queue' table is our primary FROM table (in most
            // cases) we need to proceed using a temporary table
            $tempTableName = $this->createTempTable();

            $cx->query("
                DELETE
                FROM {apb_sub}
                WHERE
                    id IN (
                        SELECT id
                        FROM {" . $tempTableName . "}
                    )
            ");

            $cx->query("DROP TABLE {" . $tempTableName . "}");

            unset($tx); // Explicit commit

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {}
            }

            throw $e;
        }
    }

    public function update(array $values)
    {
        if (empty($values)) {
            return;
        }

        $queryValues = array();

        // First build values and ensure the users don't do anything stupid
        foreach ($values as $key => $value) {
            switch ($key) {

                case Field::SUB_STATUS:
                    $queryValues['status'] = (int)$value;
                    break;

                case Field::SUB_ACTIVATED:
                    $queryValues['activated'] = (string)$value;
                    break;

                case Field::SUB_DEACTIVATED:
                    $queryValues['deactivated'] = (string)$value;
                    break;

                default:
                    throw new \RuntimeException(sprintf(
                        "%s field is unsupported for update",
                        $key
                    ));
            }
        }

        // Updating messages in queue implicates doing it using the queue id:
        // because the 'apb_queue' table is our primary FROM table (in most
        // cases) we need to proceed using a temporary table
        $tempTableName = $this->createTempTable();

        $cx = $this->backend->getConnection();

        $select = $cx
            ->select($tempTableName, 't')
            ->fields('t', array('id'));

        $cx
            ->update('apb_sub')
            ->fields($queryValues)
            ->condition('id', $select, 'IN')
            ->execute()
        ;

        $cx->query("DROP TABLE {" . $tempTableName . "}");
    }
}
