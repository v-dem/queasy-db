<?php

namespace queasy\db\query;

use PDO;
use queasy\db\DbException;

abstract class TableQuery extends Query
{
    private $table;

    public function __construct(PDO $pdo, $table)
    {
        $this->table = $table;

        parent::__construct($pdo, '');
    }

    /**
     * Build SQL query.
     *
     * @param string $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of affected rows or 0 for SELECT queries
     *
     * @throws DbException On error
     */
    public function execute($args = null)
    {
    }
}

