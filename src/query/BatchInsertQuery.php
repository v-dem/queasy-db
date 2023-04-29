<?php

namespace queasy\db\query;

use queasy\helper\Arrays;

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

        $sql = sprintf('
            INSERT  INTO "%s"
            VALUES  %s',
            $this->tableName(),
            sprintf($rowsString, $rowString)
        );

        $this->setSql($sql);

        return parent::run($values, $options);
    }
}

