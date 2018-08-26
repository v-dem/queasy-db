<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class TableInsertQuery extends TableQuery
{
    /**
     * Build SQL query.
     *
     * @param string $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of affected rows or 0 for SELECT queries
     *
     * @throws DbException On error
     */
    public function run()
    {
        if (0 === func_num_args()) {
            throw new DbException('No field values specified for insert.');
        }

        $count = count(func_get_arg(0));

        $str = str_repeat('? ', $count);

        $params = explode('?', $str);

        $paramsStr = rtrim(implode('?, ', $params), ', ');

        $query = sprintf('
            INSERT  INTO `%s`
            VALUES  (%s)',
            $this->tableName(),
            $paramsStr
        );

        $this->setQuery($query);

        parent::run(func_get_args());

        return $this->db()->id();
    }
}

