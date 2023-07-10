<?php

namespace queasy\db;

use PDO;
use ArrayAccess;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use queasy\db\query\CountQuery;
use queasy\db\query\SelectQuery;

class Field implements ArrayAccess, LoggerAwareInterface
{
    protected $pdo;

    protected $table;

    protected $name;

    protected $logger;

    public function __construct(PDO $pdo, Table $table, $name)
    {
        $this->logger = new NullLogger();

        $this->pdo = $pdo;
        $this->table = $table;
        $this->name = $name;
    }

    public function update($offset, $value, array $options = array())
    {
        if (null === $value) {
            return $this->delete($offset);
        }

        return $this->table->update($value, $this->name, $offset, $options);
    }

    public function delete($offset, array $options = array())
    {
        return $this->table->delete($this->name, $offset, $options);
    }

    public function select($value, array $options = array())
    {
        $query = new SelectQuery($this->pdo, $this->table->getName());
        $query->setLogger($this->logger);

        $statement = $query(array($this->name => $value), $options);

        return $statement;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $query = new CountQuery($this->pdo, $this->table->getName());
        $query->setLogger($this->logger);

        $statement = $query(array($this->name => $offset));

        $row = $statement->fetch();

        $count = array_shift($row);

        return $count > 0;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $rows = $this->select($offset)->fetchAll();

        if (is_array($offset)) {
            return $rows;
        }

        return array_shift($rows);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->update($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    public function __invoke($value, array $options = array())
    {
        return $this->select([$value], $options)->fetchAll();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}

