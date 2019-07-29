<?php

namespace queasy\db\query;

use queasy\db\Db;

abstract class TableQuery extends Query
{
    private $tableName;

    public function __construct(Db $pdo, $tableName)
    {
        $this->tableName = $tableName;

        parent::__construct($pdo, '');
    }

    protected function tableName()
    {
        return $this->tableName;
    }
}

