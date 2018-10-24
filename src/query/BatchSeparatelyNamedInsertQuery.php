<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class BatchSeparatelyNamedInsertQuery extends BatchNamedInsertQuery
{
    /**
     * Build SQL query.
     *
     * @param array $params Query parameters
     *
     * @return int Number of affected records (1 if row was inserted)
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        $keys = array_shift($params);
        $rows = array_shift($params);

        $paramsPrepared = array();
        foreach ($rows as $row) {
            $paramsPrepared[] = array_combine($keys, $row);
        }

        return parent::run($paramsPrepared);
    }
}

