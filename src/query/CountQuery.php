<?php

namespace queasy\db\query;

use queasy\db\Db;

class CountQuery extends SingleValueQuery
{
    /**
     * Constructor.
     *
     * @param string $query Query string
     *
     * @throws DbException When query can't be prepared
     */
    public function __construct(Db $db, $tableName)
    {
        parent::__construct($db, sprintf('SELECT count(*) FROM `%s`', $tableName));
    }

    /**
     * Executes SQL query and returns all selected rows.
     *
     * @param array $params Query parameters
     *
     * @return array Returned data depends on query, usually it is an array (empty array for queries like INSERT, DELETE or UPDATE)
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        $row = parent::run($params);

        if (empty($row)) {
            throw DbException::noValueSelected($this->query());
        } else {
            return (int) $row;
        }
    }
}

