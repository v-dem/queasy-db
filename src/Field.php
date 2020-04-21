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
        $this->logger = new NullLogger();

        $this->db = $db;
        $this->table = $table;
        $this->name = $name;
    }

    public function update($offset, $value, array $options = array())
    {
        if (null === $value) {
            return $this->delete($offset);
        } else {
            $query = new UpdateQuery($this->db, $this->table->name(), $this->name, $offset);
            $query->setLogger($this->logger());

            $statement = $query->run($value, $options);

            return $statement->rowCount();
        }
    }

    public function delete($offset, array $options = array())
    {
        $query = new DeleteQuery($this->db, $this->table->name(), $this->name, $offset);
        $query->setLogger($this->logger());

        $statement = $query->run(array(), $options);

        return $statement->rowCount();
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
        $this->update($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
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
        return $this->logger;
    }
}

