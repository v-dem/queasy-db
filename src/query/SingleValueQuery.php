<?php

namespace queasy\db\query;

class SingleValueQuery extends GetQuery
{
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
            throw DbException::noValueSelected($query);
        } else {
            return array_shift($row);
        }
    }
}

