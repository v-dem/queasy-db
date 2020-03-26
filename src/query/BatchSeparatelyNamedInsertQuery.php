<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class BatchSeparatelyNamedInsertQuery extends BatchNamedInsertQuery
{
    /**
     * Execute INSERT query with column names array as the first $params item and rows as the second.
     *
     * @param array $params Query parameters (1st item is array with column names and 2nd is array of arrays)
     *
     * @return int Number of inserted records
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        $keys = array_shift($params);
        $rows = array_shift($params);

        $paramsPrepared = array();
        foreach ($rows as $row) {
            $paramsPrepared[] = array_combine($keys, $row);
        }

        return parent::run($paramsPrepared, $options);
    }
}

