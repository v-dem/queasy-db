<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use queasy\db\query\CountQuery;
use queasy\db\query\TableGetQuery;
use queasy\db\query\TableInQuery;

class Field implements ArrayAccess
{
    private $db;

    private $table;

    private $name;

    private $logger;

    public function __construct(PDO $db, Table $table, $name)
    {
        $this->db = $db;
        $this->table = $table;
        $this->name = $name;
    }

    public function offsetExists($offset)
    {
        $query = new CountQuery($this->db, $this->table->name());
        $query->setLogger($this->logger());

        return $query->run(array($this->name => $offset)) > 0;
    }

    public function offsetGet($offset)
    {
        if (is_array($offset)) {
            $query = new TableInQuery($this->db, $this->table->name(), $this->name);
            $query->setLogger($this->logger());

            return $query->run($offset);
        } else {
            $query = new TableGetQuery($this->db, $this->table->name(), $this->name);
            $query->setLogger($this->logger());

            return $query->run(array($offset));
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($value)) { // Delete
            unset($this[$offset]);
        } elseif (is_array($offset)) {
            // UPDATE ... WHERE $this->name IN (...)
        } else {
            $query = new TableUpdateQuery($this->db, $this->table->name(), [ $this->name => $offset ]);
            $query->setLogger($this->logger());
            $query->run($value);
        }
    }

    public function offsetUnset($offset)
    {
        if (is_array($offset)) {
            // DELETE FROM ... WHERE $this->name IN (...)
        } else {
            // echo 'DELETE FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '` IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function logger()
    {
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}

