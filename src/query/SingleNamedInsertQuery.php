<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

class SingleNamedInsertQuery extends TableQuery
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
        if (empty($params)) {
            $query = new SingleInsertQuery($this->db(), $this->tableName());

            return $query->run();
        }

        $query = sprintf('
            INSERT  INTO `%s` (%s)
            VALUES  (%s)',
            $this->tableName(),
            implode(', ',
                array_map(function($paramName) {
                    return '`' . $paramName . '`';
                },  array_keys($params))
            ),
            implode(', ',
                array_map(function($paramName) {
                    return ':' . $paramName;
                },  array_keys($params))
            )
        );

        $this->setQuery($query);

        return parent::run($params);
    }
}

