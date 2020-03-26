<?php

namespace queasy\db\query;

use PDO;

class TableSelectAllQuery extends TableQuery
{
    public function __construct(PDO $db, $tableName)
    {
        parent::__construct($db, $tableName, sprintf('
            SELECT  *
            FROM    `%s`',
            $tableName
        ));
    }

    /**
     * Execute SQL query and return selected row or null.
     *
     * @param array $params Query parameters
     *
     * @return array|null Row or null if row does not exist
     *
     * @throws DbException On error
     */
    public function run(array $params = array(), array $options = array())
    {
        return parent::run($params, $options)->fetchAll();
    }
}

