<?php

namespace queasy\db\query;

use queasy\db\Db;
use queasy\db\DbException;

abstract class TableQuery extends Query
{
    private $tableName;

    public function __construct(Db $pdo, $tableName)
    {
        $this->tableName = $tableName;

        parent::__construct($pdo, '');
    }

    /**
     * Build SQL query.
     *
     * @param string $args Query arguments, can be an array or a list of function arguments
     *
     * @return int Number of inserted rows
     *
     * @throws DbException On error
     */
    public function run($args = null)
    {
    }

    protected function tableName()
    {
        return $this->tableName;
    }
}

