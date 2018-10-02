<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class BatchInsertQuery extends TableQuery
{
    /**
     * Build SQL query.
     *
     * @param array $params Query parameters as array of arrays
     *
     * @return int Number of affected records
     *
     * @throws DbException On error
     */
    public function run(array $params = array())
    {
        $query = sprintf('
            INSERT  INTO `%s`
            VALUES  (%s)',
            $this->tableName(),
            rtrim(str_repeat('?, ', count($params, COUNT_RECURSIVE)), ', ')
        );

        $this->setQuery($query);

        return parent::run($params);
    }
}

