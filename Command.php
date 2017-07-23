<?php

namespace queasy\db;

class Command
{

    private $pdo;
    private $command;

    public function __construct(\PDO $pdo, $query, $name = 'default')
    {
        $this->pdo = $pdo;

        $this->command = CommandCache::getInstance($this->pdo(), $query, $name);
    }

    public function get()
    {
        return $this->command;
    }

    protected function pdo()
    {
        return $this->pdo;
    }

}

