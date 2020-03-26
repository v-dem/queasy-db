<?php

namespace queasy\db\query;

class SingleValueQuery extends GetQuery
{
    /**
     * Executes SQL query that selects a single value.
     *
     * @param array $params Query parameters
     *
     * @return mixed Returned value
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        $row = parent::run($params, $options);

        if (empty($row)) {
            throw DbException::noValueSelected($query);
        } else {
            return array_shift($row);
        }
    }
}

