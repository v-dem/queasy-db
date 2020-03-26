<?php

namespace queasy\db\query;

use queasy\helper\Arrays;

use queasy\db\Db;
use queasy\db\DbException;

class BatchInsertQuery extends TableQuery
{
    /**
     * Execute multiple rows INSERT query.
     *
     * @param array $params Query parameters (array of arrays)
     *
     * @return int Number of inserted records
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        $values = Arrays::flatten($params);

        $rowsCount = count($params);
        $colsCount = (int) floor(count($values) / $rowsCount);

        $rowsString = rtrim(str_repeat('(%1$s), ', $rowsCount), ', ');
        $rowString = rtrim(str_repeat('?, ', $colsCount), ', ');

        $query = sprintf('
            INSERT  INTO `%s`
            VALUES  %s',
            $this->tableName(),
            sprintf($rowsString, $rowString)
        );

        $this->setQuery($query);

        return parent::run($values, $options);
    }
}

