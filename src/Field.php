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
    private $db;

    private $table;

    private $name;

    private $logger;

    public function __construct(PDO $db, Table $table, $name)
    {
        $this->logger = new NullLogger();

        $this->db = $db;
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
        $query = new SelectQuery($this->db, $this->table->name());
        $query->setLogger($this->logger);

        $statement = $query(array($this->name => $value), $options);

        if (is_array($value)) {
            return $statement->fetchAll();
        }

        return $statement->fetch();
    }

    public function offsetExists($offset)
    {
        $query = new CountQuery($this->db, $this->table->name());
        $query->setLogger($this->logger);

        $statement = $query(array($this->name => $offset));

        $row = $statement->fetch();

        $count = array_shift($row);

        return $count > 0;
    }

    public function offsetGet($offset)
    {
        return $this->select($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->update($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    public function __invoke($value, array $options = array())
    {
        return $this->select([$value], $options);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function table()
    {
        return $this->table;
    }

    protected function logger()
    {
        return $this->logger;
    }
}

