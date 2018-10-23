<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class SingleInsertQuery extends TableQuery
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
        $query = sprintf('
            INSERT  INTO `%s`
            VALUES  (%s)',
            $this->tableName(),
            rtrim(str_repeat('?, ', count($params)), ', ')
        );

        $this->setQuery($query);

        parent::run($params);

        return $this->db()->id();
    }
}

