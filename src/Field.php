<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\CountQuery;
use queasy\db\query\SelectQuery;
use queasy\db\query\GetQuery;
use queasy\db\query\SelectInQuery;
use queasy\db\query\RemoveQuery;

class Field implements ArrayAccess, LoggerAwareInterface
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

        $statement = $query->run(array($this->name => $offset));

        $row = $statement->fetch();

        $count = array_shift($row);

        return $count > 0;
    }

    public function offsetGet($offset)
    {
        if (is_array($offset)) {
            $query = new SelectInQuery($this->db, $this->table->name(), $this->name);
            $query->setLogger($this->logger());

            return $query->run($offset);
        } else {
            $query = new GetQuery($this->db, $this->table->name(), $this->name);
            $query->setLogger($this->logger());

            return $query->run(array($this->name => $offset));
        }
    }

    public function offsetSet($offset, $value)
    {
        if (null === $value) { // Delete
            unset($this[$offset]);
        } elseif (is_array($offset)) {
            $this->logger()->debug('------------------');
            $this->logger()->debug($offset);
            // UPDATE ... WHERE $this->name IN (...)
        } else {
            $query = new UpdateQuery($this->db, $this->table->name(), array($this->name => $offset));
            $query->setLogger($this->logger());
            $query->run(array($value));
        }
    }

    public function offsetUnset($offset)
    {
        if (is_array($offset)) {
            // DELETE FROM ... WHERE $this->name IN (...)
        } else {
            // echo 'DELETE FROM `' . $this->tableName . '` WHERE `' . $this->name . (is_null($offset)? '` IS NULL': '` = \'' . $offset . '\'') . PHP_EOL;
            $query = new RemoveQuery($this->db, $this->table->name(), $this->name);
            $query->setLogger($this->logger());
            $query->run(array($this->name => $offset));
        }
    }

    public function __invoke($id)
    {
        $query = new SelectQuery($this->db, $this->table->name(), $this->name);
        $query->setLogger($this->logger());

        return $query->run(array($this->name => $id));
    }

    public function in(array $values)
    {
        
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function logger()
    {
        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}

