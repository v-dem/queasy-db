<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class BatchNamedInsertQuery extends TableQuery
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
        if (!count($params)) {
            throw new DbException('Query parameters are empty.');
        }

        $columnNames = array_keys($params[0]);
        $columnsString = '`' . implode('`, `', $columnNames) . '`';

        $paramsString = '';
        $values = array();
        $counter = 0;
        foreach ($params as $row) {
            $rowParams = array();
            $columnIndex = 0;
            foreach ($row as $value) {
                $rowParams[] = $paramName = ':' . $columnNames[$columnIndex] . $counter;
                $values[$paramName] = $value;
                $columnIndex++;
            }

            $paramsString .= '(' . implode(', ', $rowParams) . '), ';

            $counter++;
        }
        $paramsString = rtrim($paramsString, ', ');

        $query = sprintf('
            INSERT  INTO `%s` (%s)
            VALUES  %s',
            $this->tableName(),
            $columnsString,
            $paramsString
        );

        $this->setQuery($query);

        return parent::run($values);
    }
}

