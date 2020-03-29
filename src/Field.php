<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\CountQuery;
use queasy\db\query\GetQuery;
use queasy\db\query\SelectQuery;
use queasy\db\query\UpdateQuery;
use queasy\db\query\DeleteQuery;

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
        $query = new SelectQuery($this->db, $this->table->name());
        $query->setLogger($this->logger());

        $statement = $query->run(array($this->name => $offset));

        if (is_array($offset)) {
            return $statement->fetchAll();
        } else {
            return $statement->fetch();
        }
    }

    public function offsetSet($offset, $value)
    {
        if (null === $value) { // Delete
            unset($this[$offset]);
        } elseif (is_array($offset)) {
            $query = new UpdateQuery($this->db, $this->table->name(), $this->name, $offset);
            $query->setLogger($this->logger());

            $query->run($value);

        } else {
            $query = new UpdateQuery($this->db, $this->table->name(), $this->name, $offset);
            $query->setLogger($this->logger());

            $query->run($value);
        }
    }

    public function offsetUnset($offset)
    {
        if (is_array($offset)) {
            $query = new DeleteQuery($this->db, $this->table->name(), $this->name, $offset);
            $query->setLogger($this->logger());

            $query->run();
        } else {
            $query = new DeleteQuery($this->db, $this->table->name(), $this->name, $offset);
            $query->setLogger($this->logger());

            $query->run();
        }
    }

    public function __invoke($value)
    {
        return $this[$value];
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

