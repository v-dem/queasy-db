<?php

namespace queasy\db\query;

use queasy\db\Db;

class CountQuery extends SingleValueQuery
{
    private $tableName;

    /**
     * Constructor.
     *
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(Db $db, $tableName)
    {
        parent::__construct($db);

        $this->tableName = $tableName;
    }

    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return int Count of records found
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        if (count($params)) {
            $this->setQuery(sprintf('SELECT count(*) FROM `%s` WHERE `%s` = :%2$s', $this->tableName, key($params)));
        } else {
            $this->setQuery(sprintf('SELECT count(*) FROM `%s`', $this->tableName));
        }

        $row = parent::run($params);

        if (empty($row)) {
            throw DbException::noValueSelected($this->query());
        } else {
            return (int) $row;
        }
    }
}

